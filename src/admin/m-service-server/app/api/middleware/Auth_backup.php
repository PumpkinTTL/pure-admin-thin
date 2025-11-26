<?php

namespace app\api\middleware;

use think\facade\Log;
use utils\AuthUtil;
use utils\JWTUtil;
use utils\RedisUtil;

class Auth
{
    // 用常量代替魔法字符串
    const TOKEN_ERROR = 'TOKEN_ERROR';

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
     * Handle the request & authenticate.
     */
    public function handle($request, \Closure $next): mixed
    {
        // ✅ 使用 AuthUtil 统一获取 Token（支持 Header 和 Cookie）
        $token = AuthUtil::getTokenFromRequest($request);
        $uri = $request->url();

        // Step1: 检查Token是否存在
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


        // Step5: 注入用户ID到请求对象中
        $request->JWTUid = $JWTUid;
        // Step7: 放行
        return $next($request);
    }
}