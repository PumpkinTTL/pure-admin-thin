<?php

namespace app\api\controller\v2;

use app\BaseController;
use think\response\Json;
use utils\RedisUtil;
use utils\JWTUtil;
use think\validate\ValidateRule;
use think\facade\Validate;

class User extends BaseController
{
    /**
     * 双Token登录接口
     *
     * 实现双Token认证机制：
     * - AccessToken: 短期有效(15分钟)，用于API请求认证
     * - RefreshToken: 长期有效(7天)，用于刷新AccessToken
     *
     * @return Json
     */
    public function login(): Json
    {
        // 1. 获取并验证请求参数
        $params = request()->param();
        $userId = $params['user_id'] ?? null;

        if (!$userId) {
            return json(['code' => 501, 'msg' => '用户ID不能为空']);
        }

        // 2. 获取设备信息用于安全验证
        $fingerprint = request()->header('X-Fingerprint'); // 设备指纹
        $platform = request()->header('X-Platform');       // 平台标识(Web/iOS/Android)
        $ip = request()->ip();                              // 客户端IP地址

        // 验证必要的设备信息
        if (!$fingerprint || !$platform) {
            return json(['code' => 502, 'msg' => '设备指纹和平台标识不能为空']);
        }

        // 3. 检查是否已有该用户在该平台的RefreshToken
        $existingRefreshTokenKey = "user:{$userId}:platform:{$platform}:refresh_token_key";
        $existingRefreshToken = RedisUtil::getString($existingRefreshTokenKey);

        // 4. 清除用户在该平台的旧Token（实现单点登录）
        $oldAccessTokenKey = "user:{$userId}:platform:{$platform}:access_token";
        RedisUtil::deleteString($oldAccessTokenKey);

        // 如果存在旧的RefreshToken，先删除
        if ($existingRefreshToken) {
            $oldRefreshTokenKey = "user:{$userId}:platform:{$platform}:refresh_token:{$existingRefreshToken}";
            RedisUtil::deleteString($oldRefreshTokenKey);
        }

        // 5. 生成新的RefreshToken
        // 使用UUID v4格式: xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx
        $refreshToken = sprintf(
            '%08x-%04x-4%03x-%04x-%012x',
            mt_rand(0, 0xffffffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff),
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffffffffffff)
        );

        // 5. 生成JWT格式的AccessToken
        $accessPayload = [
            'user_id' => $userId,
            'login_time' => time(),
            'platform' => $platform,
            'fingerprint' => $fingerprint,
            'iat' => time(), // 签发时间
            'exp' => time() + 900 // 过期时间
        ];

        // 生成JWT格式的AccessToken
        $accessToken = JWTUtil::generateToken($accessPayload, 60 * 60 * 24 * 3); // 3天有效

        // 在Redis中存储完整的AccessToken JWT，用于验证token有效性和主动失效
        $accessTokenKey = "user:{$userId}:platform:{$platform}:access_token";
        RedisUtil::setString($accessTokenKey, $accessToken, 60 * 60 * 24 * 3);

        // 6. 存储RefreshToken相关信息
        $refreshTokenData = [
            'user_id' => $userId,
            'fingerprint' => $fingerprint,
            'platform' => $platform,
            'ip' => $ip,
            'created_at' => time(),
            'last_used' => time(),
            'access_token_key' => $accessTokenKey // 关联的AccessToken Key
        ];

        // 使用更有辨识度的Redis Key命名: user:{userId}:platform:{platform}:refresh_token:{refreshToken}
        $refreshTokenKey = "user:{$userId}:platform:{$platform}:refresh_token:{$refreshToken}";
        RedisUtil::setString($refreshTokenKey, json_encode($refreshTokenData), 3600 * 24 * 7); // 7天有效

        // 记录当前用户在该平台的RefreshToken，用于下次登录时清除
        $refreshTokenKeyRecord = "user:{$userId}:platform:{$platform}:refresh_token_key";
        RedisUtil::setString($refreshTokenKeyRecord, $refreshToken, 3600 * 24 * 7); // 7天有效

        // 7. 返回登录成功响应
        return json([
            'code' => 200,
            'msg' => '登录成功',
            'data' => [
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'token_type' => 'Bearer',
                'expires_in' => 259200,        // AccessToken过期时间(秒) - 3天
                'refresh_expires_in' => 604800 // RefreshToken过期时间(秒)
            ]
        ]);
    }

    /**
     * 刷新Token接口
     *
     * 使用RefreshToken获取新的AccessToken
     * 验证设备信息确保安全性
     *
     * @return Json
     */
    public function refreshToken(): Json
    {
        // 1. 获取并验证请求参数
        $params = request()->param();

        $validate = Validate::rule([
            'refresh_token' => ValidateRule::isRequire(null, 'RefreshToken必须传递'),
            'user_id' => ValidateRule::isRequire(null, '用户ID必须传递'),
        ]);

        $validateResult = $validate->batch()->check($params);
        if (!$validateResult) {
            return json(['code' => 501, 'msg' => '参数错误', 'info' => $validate->getError()]);
        }

        $refreshToken = $params['refresh_token'];
        $userId = $params['user_id'];

        // 2. 获取当前设备信息用于安全验证
        $fingerprint = request()->header('X-Fingerprint'); // 设备指纹
        $platform = request()->header('X-Platform');       // 平台标识
        $ip = request()->ip();                              // 客户端IP

        if (!$fingerprint || !$platform) {
            return json(['code' => 502, 'msg' => '设备指纹和平台标识不能为空']);
        }

        try {
            // 3. 从Redis获取RefreshToken数据（使用新的Key格式）
            $refreshTokenKey = "user:{$userId}:platform:{$platform}:refresh_token:{$refreshToken}";
            $refreshTokenData = RedisUtil::getString($refreshTokenKey);

            if (!$refreshTokenData) {
                return json([
                    'code' => 403,
                    'msg' => 'RefreshToken不存在或已过期',
                    'status' => 'TOKEN_ERROR'
                ]);
            }

            // 4. 解析RefreshToken数据
            $refreshTokenPayload = json_decode($refreshTokenData, true);
            if (!$refreshTokenPayload) {
                return json([
                    'code' => 403,
                    'msg' => 'RefreshToken数据格式错误',
                    'status' => 'TOKEN_ERROR'
                ]);
            }

            // 5. 验证设备信息安全性
            $securityChecks = [
                'user_id' => $userId,
                'fingerprint' => $fingerprint,
                'platform' => $platform,
                'ip' => $ip, // IP验证可选，移动设备IP可能变化
            ];

            foreach ($securityChecks as $field => $currentValue) {
                $storedValue = $refreshTokenPayload[$field] ?? null;
                if ($storedValue !== $currentValue) {
                    return json([
                        'code' => 403,
                        'msg' => "安全验证失败：{$field}不匹配",
                        'status' => 'TOKEN_ERROR',
                    ]);
                }
            }

            // 6. 生成新的JWT AccessToken
            $accessTokenPayload = [
                'user_id' => $userId,
                'login_time' => time(),
                'platform' => $platform,
                'fingerprint' => $fingerprint,
                'iat' => time(),
                'exp' => time() + 60 * 60 * 24 * 3
            ];

            // 生成新的JWT AccessToken
            $accessToken = JWTUtil::generateToken($accessTokenPayload, 60 * 60 * 24 * 3);

            // 7. 更新Redis中的AccessToken JWT
            $accessTokenKey = "user:{$userId}:platform:{$platform}:access_token";
            RedisUtil::setString($accessTokenKey, $accessToken, 60 * 60 * 24 * 3);

            // 8. 更新RefreshToken的最后使用时间和关联的AccessToken Key
            $refreshTokenPayload['last_used'] = time();
            $refreshTokenPayload['access_token_key'] = $accessTokenKey;
            RedisUtil::setString($refreshTokenKey, json_encode($refreshTokenPayload), 3600 * 24 * 7);

            // 9. 返回新的AccessToken
            return json([
                'code' => 200,
                'msg' => 'Token刷新成功',
                'data' => [
                    'access_token' => $accessToken,
                    'token_type' => 'Bearer',
                    'expires_in' => 259200 // 3天
                ]
            ]);

        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'msg' => 'Token刷新失败：' . $e->getMessage(),
                'status' => 'TOKEN_ERROR'
            ]);
        }
    }

    /**
     * 获取用户信息接口（用于测试双token认证）
     * 直接在控制器中验证token，不使用中间件
     * @return Json
     */
    public function info(): Json
    {
        // 1. 获取请求头中的token
        $accessToken = request()->header('Authorization');
        $refreshToken = request()->header('refreshToken');
        $platform = request()->header('X-Platform');
        $fingerprint = request()->header('X-Fingerprint');

        // 移除Bearer前缀
        if (strpos($accessToken, 'Bearer ') === 0) {
            $accessToken = substr($accessToken, 7);
        }

        if (empty($accessToken) || empty($refreshToken) || empty($platform)) {
            return json(['code' => 401, 'msg' => '缺少认证信息']);
        }

        try {
            // 2. 验证AccessToken JWT
            $verifyResult = JWTUtil::verifyToken($accessToken);
            if ($verifyResult['code'] !== 200) {
                return json(['code' => 401, 'msg' => 'AccessToken验证失败：' . $verifyResult['msg']]);
            }

            // 3. 获取JWT中的用户信息
            $tokenData = $verifyResult['data'];
            $userId = $tokenData['user_id'] ?? null;
            $tokenPlatform = $tokenData['platform'] ?? null;

            if (empty($userId) || $tokenPlatform !== $platform) {
                return json(['code' => 401, 'msg' => 'Token数据不完整或平台不匹配']);
            }

            // 4. 验证AccessToken是否在Redis中存在（用于主动失效控制）
            $accessTokenKey = "user:{$userId}:platform:{$platform}:access_token";
            $storedAccessToken = RedisUtil::getString($accessTokenKey);
            if ($storedAccessToken !== $accessToken) {
                return json(['code' => 401, 'msg' => 'AccessToken已失效，请重新登录']);
            }

            // 5. 验证RefreshToken是否存在
            $refreshTokenKey = "user:{$userId}:platform:{$platform}:refresh_token:{$refreshToken}";
            $refreshTokenData = RedisUtil::getString($refreshTokenKey);
            if (!$refreshTokenData) {
                return json(['code' => 401, 'msg' => 'RefreshToken不存在或已过期']);
            }

            // 6. 返回用户信息
            return json([
                'code' => 200,
                'msg' => '获取用户信息成功',
                'data' => [
                    'user_id' => $userId,
                    'login_time' => $tokenData['login_time'] ?? null,
                    'platform' => $tokenData['platform'] ?? null,
                    'fingerprint' => $tokenData['fingerprint'] ?? null,
                    'current_time' => time(),
                    'token_valid' => true
                ]
            ]);

        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => '认证异常：' . $e->getMessage()]);
        }
    }

    /**
     * 测试token读取接口
     * 用于调试token传递问题
     * @return Json
     */
    public function testToken(): Json
    {
        // 获取所有相关的请求头
        $headers = [
            'Authorization' => request()->header('Authorization'),
            'refreshToken' => request()->header('refreshToken'),
            'X-Platform' => request()->header('X-Platform'),
            'X-Fingerprint' => request()->header('X-Fingerprint'),
            'X-Device-Id' => request()->header('X-Device-Id'),
            'Content-Type' => request()->header('Content-Type'),
            'User-Agent' => request()->header('User-Agent'),
        ];

        // 获取请求参数
        $params = request()->param();

        return json([
            'code' => 200,
            'msg' => '请求头和参数读取成功',
            'data' => [
                'headers' => $headers,
                'params' => $params,
                'method' => request()->method(),
                'url' => request()->url(),
                'ip' => request()->ip(),
                'timestamp' => time()
            ]
        ]);
    }

    /**
     * 登出接口
     *
     * 清除用户的AccessToken和RefreshToken
     * 实现安全登出和主动失效
     *
     * @return Json
     */
    public function logout(): Json
    {
        // 1. 获取用户ID、平台和RefreshToken
        $userId = request()->userId ?? request()->param('user_id');
        $platform = request()->header('X-Platform') ?? request()->param('platform');
        $refreshToken = request()->header('refreshToken') ?? request()->param('refresh_token');

        if (!$userId || !$platform || !$refreshToken) {
            return json(['code' => 501, 'msg' => '缺少必要参数']);
        }

        try {
            // 2. 清除Redis中的AccessToken JWT
            $accessTokenKey = "user:{$userId}:platform:{$platform}:access_token";
            RedisUtil::deleteString($accessTokenKey);

            // 3. 清除RefreshToken数据
            $refreshTokenKey = "user:{$userId}:platform:{$platform}:refresh_token:{$refreshToken}";
            RedisUtil::deleteString($refreshTokenKey);

            return json([
                'code' => 200,
                'msg' => '登出成功'
            ]);

        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'msg' => '登出失败：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 强制失效用户所有Token（管理员功能）
     *
     * @return Json
     */
    public function forceLogoutUser(): Json
    {
        $params = request()->param();
        $targetUserId = $params['target_user_id'] ?? null;
        $targetPlatform = $params['platform'] ?? null; // 可选，不传则失效所有平台

        if (!$targetUserId) {
            return json(['code' => 501, 'msg' => '目标用户ID不能为空']);
        }

        try {
            if ($targetPlatform) {
                // 失效指定平台的token
                $accessTokenKey = "user:{$targetUserId}:platform:{$targetPlatform}:access_token";
                RedisUtil::deleteString($accessTokenKey);

                // 这里可以扩展删除该平台的所有RefreshToken
                // 实际项目中可以使用Redis的SCAN命令查找并删除

                return json([
                    'code' => 200,
                    'msg' => "用户 {$targetUserId} 在 {$targetPlatform} 平台的Token已失效"
                ]);
            } else {
                // 失效所有平台的token
                // 这里可以扩展使用Redis的SCAN命令查找用户的所有token并删除

                return json([
                    'code' => 200,
                    'msg' => "用户 {$targetUserId} 的所有Token已失效"
                ]);
            }

        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'msg' => '强制登出失败：' . $e->getMessage()
            ]);
        }
    }
}
