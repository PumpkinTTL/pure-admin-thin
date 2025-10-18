<?php

namespace app\api\middleware;

use app\api\model\users;
use app\api\services\LogService;
use utils\JWTUtil;

/**
 * 文章权限验证中间件
 * 功能：
 * 1. 解析 JWT Token 获取用户ID
 * 2. 查询用户角色信息
 * 3. 将用户ID和角色信息传递给控制器
 * 4. 如果无Token或Token过期，设置为未登录状态
 */
class ArticleAuth
{
    public function handle($request, \Closure $next)
    {
        // 默认未登录状态
        $userId = 0;
        $userRoles = [];
        LogService::log("[Middleware] ArticleAuth - 开始执行", [], 'info');

        try {
            // 获取请求头中的 Authorization
            $authHeader = request()->header('authorization');
            // 获取 cookie 中的 Authorization
            $cookieAuth = request()->cookie('Authorization');

            LogService::log("[Middleware] ArticleAuth - Authorization header: " . ($authHeader ? 'exists' : 'empty'), [], 'info');
            LogService::log("[Middleware] ArticleAuth - Authorization cookie: " . ($cookieAuth ? 'exists' : 'empty'), [], 'info');

            // 优先从请求头获取 token，如果没有则从 cookie 获取
            $token = null;
            if (!empty($authHeader)) {
                // 去掉 "Bearer " 前缀，获取纯 token
                $token = trim(str_replace(['Bearer ', 'bearer '], '', $authHeader));
                LogService::log("[Middleware] ArticleAuth - Token from header", [], 'info');
            } elseif (!empty($cookieAuth)) {
                // 从 cookie 获取 token
                $token = trim($cookieAuth);
                LogService::log("[Middleware] ArticleAuth - Token from cookie", [], 'info');
            }

            // 如果存在 Token
            if (!empty($token)) {
                error_log("[Middleware] Token 获取: " . substr($token, 0, 30) . "...");
                LogService::log("[Middleware] ArticleAuth - Token: " . substr($token, 0, 20) . '...', [], 'info');
                // 验证 Token
                $parseToken = JWTUtil::verifyToken($token);
                error_log("[Middleware] Token验证结果: " . json_encode($parseToken));
                // ✅ 修复：JWTUtil返回的是数组，结构为 ['code'=>200, 'data'=>[Token内容]]
                // Token内容又包含 ['data'=>['id'=>用户ID]]
                // 所以需要访问 $parseToken['data']['data']['id']
                if ($parseToken && $parseToken['code'] === 200 && isset($parseToken['data']['data']['id'])) {
                    $userId = (int)$parseToken['data']['data']['id'];
                    error_log("[Middleware] ✅ 成功从 Token 解析到 userId: {$userId}");
                    // 查询用户信息和角色
                    $user = users::with('roles')->find($userId);
                    error_log("[Middleware] 用户查询结果: " . ($user ? 'FOUND' : 'NOT FOUND'));
                    if ($user) {
                        error_log("[Middleware] 用户信息: ID={$user->id}, username={$user->username}");
                        error_log("[Middleware] 用户角色: " . ($user->roles ? json_encode($user->roles->toArray()) : 'NULL'));
                    }

                    if ($user && $user->roles) {
                        // 提取角色ID数组
                        $userRoles = $user->roles->column('id');
                        // ========== 强制调试输出 ==========
                        error_log("[Middleware] 提取的角色ID数组: " . json_encode($userRoles));
                        LogService::log("[Middleware] ArticleAuth - 用户ID: {$userId}", [], 'info');
                        LogService::log("[Middleware] ArticleAuth - 角色: " . json_encode($userRoles), [], 'info');
                        LogService::log("[Middleware] ArticleAuth - 角色数量: " . count($userRoles), [], 'info');
                        // ========================================
                    } else {
                        error_log("[Middleware] 用户没有角色 or 用户不存在");
                    }
                } else {
                    error_log("[Middleware] Token验证失败或缺少 user id");
                }
            } else {
                error_log("[Middleware] Token 不存在 - header和cookie中都没有找到");
            }
        } catch (\Exception $e) {
            // Token 解析失败或过期，保持默认未登录状态
            LogService::log("文章权限中间件: Token解析失败 - " . $e->getMessage(), [], 'warning');
        }

        // 将用户信息传递给控制器（通过 Request 属性）
        $request->currentUserId = $userId;
        $request->currentUserRoles = $userRoles;


        // ========== 强制输出调试信息 ==========
        error_log("[ArticleAuth] Final userId: {$userId}");
        error_log("[ArticleAuth] Final roles: " . json_encode($userRoles));
        error_log("[ArticleAuth] request->currentUserId: " . ($request->currentUserId ?? 'NULL'));
        error_log("[ArticleAuth] request->currentUserRoles: " . json_encode($request->currentUserRoles ?? 'NULL'));
        // ====================================

        return $next($request);
    }
}
