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
        try {
            // 使用 AuthUtil 工具类解析 Token 并获取认证信息（带缓存）
            $authInfo = AuthUtil::parseTokenAndGetAuthInfo(null, true);

            if ($authInfo['success']) {
                // ✅ 认证成功，提取用户信息
                $userId = $authInfo['user_id'];
                $userRoles = $authInfo['role_ids'];
                $isAdmin = $authInfo['is_admin'];
            } else {
                // ⚠️ Token 验证失败或未提供 Token，视为游客访问
                // 公告模块允许游客查看公开内容，所以不阻止请求
                $userId = 0;
                $userRoles = [];
                $isAdmin = false;
                
                // 记录 Token 失败日志（用于调试）
                LogService::log("公告模块-游客访问: {$authInfo['msg']}", [
                    'ip' => $request->ip(),
                    'url' => $request->url()
                ]);
            }

            // 将用户信息传递给控制器（游客时 userId=0）
            $request->currentUserId = $userId;
            $request->currentUserRoles = $userRoles;
            $request->isAdmin = $isAdmin;
        } catch (\Exception $e) {
            LogService::error($e, ['context' => 'NoticeAuth 中间件处理异常']);

            // 异常情况下，也视为游客访问（不阻止请求）
            $request->currentUserId = 0;
            $request->currentUserRoles = [];
            $request->isAdmin = false;
        }

        return $next($request);
    }
}
