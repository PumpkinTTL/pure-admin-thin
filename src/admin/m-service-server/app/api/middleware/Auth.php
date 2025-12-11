<?php

namespace app\api\middleware;

use think\facade\Log;
use utils\AuthUtil;
use utils\JWTUtil;
use utils\RedisUtil;
use utils\WhitelistManager;

class Auth
{
    // 用常量代替魔法字符串
    const TOKEN_ERROR = 'TOKEN_ERROR';
    const PERMISSION_DENIED = 'PERMISSION_DENIED';

    /**
     * 统一错误返回
     */
    protected function errorResponse(string $msg, int $code = 500, string $status = self::TOKEN_ERROR)
    {
        return json([
            'code' => $code,
            'msg' => $msg,
            'status' => $status
        ]);
    }

    /**
     * Handle the request & authenticate with permission check.
     * @param mixed $request 请求对象
     * @param \Closure $next 下一个中间件
     * @param string|null $permission 权限配置，格式：mode:requirements
     *
     * 权限配置示例：
     * - "admin"                    // 需要管理员权限
     * - "role:super_admin,admin"  // 需要指定角色之一
     * - "permission:user.edit"      // 需要指定权限
     * - "user"                      // 只需要登录（默认）
     */
    public function handle($request, \Closure $next, string $permission = 'user'): mixed
    {
        $uri = $request->url();
        $fullPath = $request->pathinfo();
        
        // 确保路径以 / 开头
        if ($fullPath[0] !== '/') {
            $fullPath = '/' . $fullPath;
        }
        
        // ✅ 使用 AuthUtil 统一获取 Token（支持 Header 和 Cookie）
        $token = AuthUtil::getTokenFromRequest($request);
        
        // Step0: 检查是否为公开接口（白名单）
        if (WhitelistManager::isPublic($fullPath)) {
            // 公开接口：如果有token，尝试解析（可选），但不强制要求
            if (!empty($token)) {
                $verifyToken = JWTUtil::verifyToken($token);
                if (is_array($verifyToken) && ($verifyToken['code'] ?? 0) === 200) {
                    $JWTUid = $verifyToken['data']['data']['id'] ?? null;
                    if (!empty($JWTUid)) {
                        // 设置用户ID（供后续中间件使用）
                        $request->JWTUid = $JWTUid;
                        $request->userId = $JWTUid;
                    }
                }
            }
            return $next($request);
        }

        // Step1: 检查Token是否存在（非公开接口必须有token）
        if (empty($token)) {
            return $this->errorResponse('请先登录再继续操作', 502);
        }

        // Step2: 验证Refresh Token的合法性
        $verifyToken = JWTUtil::verifyToken($token);

        if (!is_array($verifyToken) || ($verifyToken['code'] ?? 0) !== 200) {
            Log::error(json_encode([
                'url' => $uri,
                'msg' => $verifyToken['msg'] ?? 'token解析失败',
                'IP' => $request->ip(),
                'refreshToken' => $token
            ]));
            return $this->errorResponse($verifyToken['msg'] ?? '非法令牌', 5003);
        }

        // Step3: 获取 JWT 中的 uid
        $JWTUid = $verifyToken['data']['data']['id'] ?? null;
        if (empty($JWTUid)) {
            return $this->errorResponse('用户ID解析失败，请重新登录', 5004);
        }

        // Step4: 校验 Redis 中的 token 是否匹配，防止异地登录
        $redisKey = 'lt_' . $JWTUid;
        $redisJWT = RedisUtil::getString($redisKey);

        if ($redisJWT !== $token || empty($redisJWT)) {
            return $this->errorResponse('登录失效，请重新登录', 5005);
        }

        // Step5: 权限检查（新增）
        if ($permission && !$this->checkUserPermission($JWTUid, $token, $permission, $uri)) {
            return $this->errorResponse('权限不足', 5006, self::PERMISSION_DENIED);
        }

        // Step6: 注入用户ID到请求对象中
        $request->JWTUid = $JWTUid;
        $request->userId = $JWTUid;  // ✅ 同时设置 userId，供 PermissionCheck 使用
        // Step7: 放行
        return $next($request);
    }

    /**
     * 检查用户权限
     * @param int $userId 用户ID
     * @param string $token JWT Token
     * @param string $permission 权限配置
     * @param string $uri 请求URI（用于日志）
     * @return bool 是否有权限
     */
    private function checkUserPermission(int $userId, string $token, string $permission, string $uri): bool
    {
        try {
            // 使用AuthUtil获取用户完整权限信息
            $authInfo = AuthUtil::parseTokenAndGetAuthInfo($token, true);

            if (!$authInfo['success']) {
                Log::error("权限检查失败: 用户ID {$userId}, 错误: {$authInfo['msg']}, URI: {$uri}");
                return false;
            }

            // 解析权限配置
            [$mode, $requirements] = $this->parsePermission($permission);

            // 根据模式进行权限检查
            $hasPermission = $this->verifyPermission($authInfo, $mode, $requirements);

            // 记录权限检查日志（仅记录拒绝的情况）
            if (!$hasPermission) {
                Log::warning("权限不足: 用户ID {$userId}, 权限要求: {$permission}, 用户角色: " .
                    json_encode($authInfo['role_idens'] ?? []) .
                    ", 用户权限: " . json_encode($authInfo['permission_idens'] ?? []) .
                    ", URI: {$uri}");
            } 

            return $hasPermission;

        } catch (\Exception $e) {
            Log::error("权限检查异常: 用户ID {$userId}, 异常: " . $e->getMessage() . ", URI: {$uri}");
            return false;
        }
    }

    /**
     * 解析权限配置字符串
     * @param string $permission 权限配置
     * @return array [模式, 要求列表]
     */
    private function parsePermission(string $permission): array
    {
        if (strpos($permission, ':') === false) {
            // 简单模式，如 "admin", "user"
            return [$permission, []];
        }

        [$mode, $requirements] = explode(':', $permission, 2);
        $requirementsArray = empty($requirements) ? [] : explode(',', $requirements);

        return [trim($mode), array_map('trim', $requirementsArray)];
    }

    /**
     * 验证权限
     * @param array $authInfo 用户权限信息
     * @param string $mode 权限模式
     * @param array $requirements 要求列表
     * @return bool 是否有权限
     */
    private function verifyPermission(array $authInfo, string $mode, array $requirements): bool
    {
        switch ($mode) {
            case 'user':
                // 只要是登录用户即可
                return true;

            case 'admin':
                // 需要管理员权限
                return $authInfo['is_admin'] ?? false;

            case 'role':
                // 需要指定角色之一
                if (empty($requirements)) {
                    return false;
                }
                return AuthUtil::hasRole($authInfo['role_idens'] ?? [], $requirements);

            case 'permission':
                // 需要指定权限之一
                if (empty($requirements)) {
                    return false;
                }
                return AuthUtil::hasPermission($authInfo['permission_idens'] ?? [], $requirements);

            default:
                // 未知模式，拒绝访问
                Log::warning("未知的权限模式: {$mode}");
                return false;
        }
    }
}