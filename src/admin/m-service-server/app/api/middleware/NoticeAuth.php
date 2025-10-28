<?php

namespace app\api\middleware;

use app\api\services\LogService;
use utils\AuthUtil;

/**
 * 公告权限验证中间件
 * 使用 AuthUtil 工具类简化 Token 解析和用户信息获取
 * 功能：
 * 1. 解析 JWT Token 获取用户ID
 * 2. 查询用户角色信息（带缓存）
 * 3. 判断是否为管理员
 * 4. 将用户信息传递给控制器
 */
class NoticeAuth
{
    public function handle($request, \Closure $next)
    {
        LogService::log("[Middleware] NoticeAuth - 开始执行", [], 'info');

        try {
            // 使用 AuthUtil 工具类解析 Token 并获取认证信息（带缓存）
            $authInfo = AuthUtil::parseTokenAndGetAuthInfo(null, true);

            if ($authInfo['success']) {
                // 认证成功，提取信息
                $userId = $authInfo['user_id'];
                $userRoles = $authInfo['role_ids'];
                $isAdmin = $authInfo['is_admin'];

                error_log("[NoticeAuth] ✅ 认证成功: userId={$userId}, isAdmin=" . ($isAdmin ? 'true' : 'false'));
                LogService::log("[NoticeAuth] 用户ID: {$userId}, 角色: " . json_encode($userRoles) . ", 是否管理员: " . ($isAdmin ? 'true' : 'false'), [], 'info');
            } else {
                // 认证失败或未提供 Token，视为游客访问
                $userId = 0;
                $userRoles = [];
                $isAdmin = false;

                error_log("[NoticeAuth] ⚠️ " . $authInfo['msg'] . "，视为游客访问");
                LogService::log("[NoticeAuth] " . $authInfo['msg'] . "，视为游客访问", [], 'info');
            }

            // 将用户信息传递给控制器
            $request->currentUserId = $userId;
            $request->currentUserRoles = $userRoles;
            $request->isAdmin = $isAdmin;

            error_log("[NoticeAuth] 最终传递: userId={$userId}, roles=" . json_encode($userRoles) . ", isAdmin=" . ($isAdmin ? 'true' : 'false'));
        } catch (\Exception $e) {
            error_log("[NoticeAuth] ❌ 异常: " . $e->getMessage());
            LogService::error($e, ['context' => 'NoticeAuth 中间件处理异常']);

            // 异常情况下，设置默认值
            $request->currentUserId = 0;
            $request->currentUserRoles = [];
            $request->isAdmin = false;
        }

        return $next($request);
    }
}
