<?php

namespace app\api\middleware;

use think\facade\Db;
use think\facade\Log;
use utils\JWTUtil;
use utils\SecretUtil;
use app\api\services\LogService;

class PermissionCheck
{
    /**
     * 权限检查中间件
     * @param $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, \Closure $next): mixed
    {
        try {
            // 1. 获取请求头中的accessToken
            $accessToken = $request->header('accessToken');
            $refreshToken = $request->header('refreshToken');
            
            if (empty($accessToken) || empty($refreshToken)) {
                return json(['code' => 401, 'msg' => '缺少认证信息', 'status' => 'AUTH_ERROR']);
            }
            
            // 2. 验证JWT并获取用户ID
            $verifyResult = JWTUtil::verifyToken($refreshToken);
            if ($verifyResult['code'] != 200) {
                LogService::log("权限检查失败，JWT验证失败：{$verifyResult['msg']}", [
                    'url' => $request->url(),
                    'ip' => $request->ip()
                ], 'warning');
                return json(['code' => 401, 'msg' => 'JWT验证失败：' . $verifyResult['msg'], 'status' => 'TOKEN_ERROR']);
            }
            
            // 3. 从JWT载荷中获取用户ID
            $userId = $verifyResult['data']['data']['id'] ?? null;
            if (!$userId) {
                LogService::log("权限检查失败，无法获取用户ID", [
                    'url' => $request->url(),
                    'ip' => $request->ip()
                ], 'warning');
                return json(['code' => 401, 'msg' => '无法获取用户信息', 'status' => 'USER_ERROR']);
            }
            
            // 4. 验证accessToken
            $parseAccessToken = SecretUtil::parseAccessToken($accessToken);
            if (!$parseAccessToken) {
                LogService::log("权限检查失败，accessToken解析失败", [
                    'user_id' => $userId,
                    'url' => $request->url(),
                    'ip' => $request->ip()
                ], 'warning');
                return json(['code' => 401, 'msg' => 'accessToken无效', 'status' => 'TOKEN_ERROR']);
            }
            
            // 5. 获取当前请求的接口路径和方法
            $currentPath = $request->pathinfo();
            $currentMethod = strtoupper($request->method());
            $fullPath = '/api/v1/' . $currentPath;
            
            // 6. 查询用户的角色信息
            $userRoles = $this->getUserRoles($userId);
            if (empty($userRoles)) {
                LogService::log("权限检查失败，用户无角色", [
                    'user_id' => $userId,
                    'url' => $request->url(),
                    'ip' => $request->ip()
                ], 'warning');
                return json(['code' => 403, 'msg' => '用户未分配角色，无权限访问', 'status' => 'PERMISSION_DENIED']);
            }
            
            // 7. 根据角色查询权限
            $roleIds = array_column($userRoles, 'id');
            $permissions = $this->getRolePermissions($roleIds);
            if (empty($permissions)) {
                LogService::log("权限检查失败，角色无权限", [
                    'user_id' => $userId,
                    'role_ids' => $roleIds,
                    'url' => $request->url(),
                    'ip' => $request->ip()
                ], 'warning');
                return json(['code' => 403, 'msg' => '角色未分配权限，无权限访问', 'status' => 'PERMISSION_DENIED']);
            }
            
            // 8. 根据权限查询可访问的API接口
            $permissionIds = array_column($permissions, 'id');
            $allowedApis = $this->getPermissionApis($permissionIds);
            
            // 9. 检查当前请求的接口是否在允许的API列表中
            $hasPermission = $this->checkApiPermission($fullPath, $currentMethod, $allowedApis);
            
            if (!$hasPermission) {
                LogService::log("权限检查失败，无接口访问权限", [
                    'user_id' => $userId,
                    'requested_path' => $fullPath,
                    'requested_method' => $currentMethod,
                    'url' => $request->url(),
                    'ip' => $request->ip()
                ], 'warning');
                return json(['code' => 403, 'msg' => '无权限访问此接口', 'status' => 'PERMISSION_DENIED']);
            }
            
            // 10. 权限检查通过，记录成功日志并继续执行
            LogService::log("权限检查通过", [
                'user_id' => $userId,
                'requested_path' => $fullPath,
                'requested_method' => $currentMethod,
                'url' => $request->url(),
                'ip' => $request->ip()
            ]);
            
            // 将用户ID添加到请求中，供后续使用
            $request->userId = $userId;
            
            return $next($request);
            
        } catch (\Exception $e) {
            LogService::error($e);
            return json(['code' => 500, 'msg' => '权限检查异常：' . $e->getMessage(), 'status' => 'SYSTEM_ERROR']);
        }
    }
    
    /**
     * 获取用户的角色信息
     * @param int $userId
     * @return array
     */
    private function getUserRoles(int $userId): array
    {
        return Db::name('user_roles')
            ->alias('ur')
            ->join('roles r', 'ur.role_id = r.id')
            ->where('ur.user_id', $userId)
            ->where('r.status', 1) // 只查询启用的角色
            ->whereNull('r.delete_time') // 排除已删除的角色
            ->field('r.id, r.name, r.iden, r.description')
            ->select()
            ->toArray();
    }
    
    /**
     * 根据角色ID获取权限信息
     * @param array $roleIds
     * @return array
     */
    private function getRolePermissions(array $roleIds): array
    {
        return Db::name('role_permissions')
            ->alias('rp')
            ->join('permissions p', 'rp.permission_id = p.id')
            ->where('rp.role_id', 'in', $roleIds)
            ->whereNull('p.delete_time') // 排除已删除的权限
            ->field('p.id, p.name, p.iden, p.description')
            ->group('p.id') // 去重
            ->select()
            ->toArray();
    }
    
    /**
     * 根据权限ID获取可访问的API接口
     * @param array $permissionIds
     * @return array
     */
    private function getPermissionApis(array $permissionIds): array
    {
        return Db::name('permission_api')
            ->alias('pa')
            ->join('api a', 'pa.api_id = a.id')
            ->where('pa.permission_id', 'in', $permissionIds)
            ->where('a.status', 1) // 只查询开放状态的API
            ->field('a.id, a.full_path, a.method, a.description')
            ->select()
            ->toArray();
    }
    
    /**
     * 检查当前请求的API是否有权限访问
     * @param string $requestPath 请求路径
     * @param string $requestMethod 请求方法
     * @param array $allowedApis 允许访问的API列表
     * @return bool
     */
    private function checkApiPermission(string $requestPath, string $requestMethod, array $allowedApis): bool
    {
        foreach ($allowedApis as $api) {
            // 路径匹配（支持精确匹配和通配符匹配）
            if ($this->pathMatches($requestPath, $api['full_path'])) {
                // 方法匹配（如果API没有指定方法或方法匹配）
                if (empty($api['method']) || strtoupper($api['method']) === $requestMethod) {
                    return true;
                }
            }
        }
        return false;
    }
    
    /**
     * 路径匹配检查（支持通配符）
     * @param string $requestPath 请求路径
     * @param string $apiPath API路径
     * @return bool
     */
    private function pathMatches(string $requestPath, string $apiPath): bool
    {
        // 精确匹配
        if ($requestPath === $apiPath) {
            return true;
        }
        
        // 通配符匹配（如果API路径包含*）
        if (strpos($apiPath, '*') !== false) {
            $pattern = str_replace('*', '.*', preg_quote($apiPath, '/'));
            return preg_match('/^' . $pattern . '$/', $requestPath);
        }
        
        return false;
    }
}
