<?php

namespace app\api\middleware;

use think\facade\Db;
use think\Response;
use utils\WhitelistManager;

/**
 * 权限检查中间件
 */
class PermissionCheck
{
    /**
     * 处理请求
     */
    public function handle($request, \Closure $next): Response
    {
        // 1. 获取请求信息
        $pathinfo = $request->pathinfo();  // ThinkPHP多应用模式下，已去掉 /api 前缀
        $method = $request->method();
        
        // 确保路径以 / 开头
        if ($pathinfo[0] !== '/') {
            $pathinfo = '/' . $pathinfo;
        }
        
        // 🔍 调试：记录实际请求的路径
        error_log("[PermissionCheck] pathinfo: {$pathinfo}, 方法: {$method}");
        
        // 2. 检查是否为公开接口（无需权限检查）
        // 白名单使用 pathinfo（不含 /api）
        if (WhitelistManager::isPublic($pathinfo)) {
            return $next($request);
        }
        
        // 3. 检查是否为只需登录的接口（不需要权限检查）
        if (WhitelistManager::isAuthOnly($pathinfo)) {
            return $next($request);
        }
        
        // 4. 获取用户ID（应该已经由 Auth 中间件设置）
        $userId = $request->userId ?? $request->JWTUid ?? null;
        
        // 5. 如果没有用户ID，说明没有通过 Auth 中间件，直接拒绝
        if (!$userId) {
            return json(['code' => 401, 'msg' => '未登录或登录已过期']);
        }
        
        // 6. 查询 API 配置（支持路径参数匹配）
        // 数据库中存储的是完整路径（含 /api），需要添加前缀
        $fullPath = '/api' . $pathinfo;
        $api = $this->findMatchingApi($fullPath, $method);
        
        // ⚠️ 安全策略：如果 API 不存在于数据库中，默认拒绝访问
        if (!$api) {
            error_log("[PermissionCheck] 未找到API配置: {$fullPath}");
            return json([
                'code' => 403,
                'msg' => 'API未配置权限，请联系管理员',
                'data' => [
                    'path' => $fullPath,
                    'method' => $method,
                    'hint' => '该接口尚未在系统中注册，请先同步接口配置'
                ]
            ]);
        }
        
        error_log("[PermissionCheck] 找到API配置: {$api['full_path']} (模块: {$api['module']})");
        
        // 7. 检查 API 状态
        if ($api['status'] == 0) {
            return json(['code' => 503, 'msg' => 'API维护中，暂时无法访问']);
        }
        
        if ($api['status'] == 3) {
            return json(['code' => 403, 'msg' => 'API已关闭']);
        }
        
        // 8. 根据 check_mode 执行不同的权限检查
        switch ($api['check_mode']) {
            case 'none':
                // 公开接口，不检查权限，直接放行
                return $next($request);
                
            case 'manual':
                // 手动模式，检查指定的权限
                return $this->checkManualPermission($request, $api, $next);
                
            case 'auto':
            default:
                // 自动模式，根据 module + method 自动构建权限
                return $this->checkAutoPermission($request, $api, $next);
        }
    }
    
    /**
     * 自动权限检查（改进版 - 修复通配符权限提升漏洞）
     */
    private function checkAutoPermission($request, $api, $next): Response
    {
        // 1. 提取模块和方法
        $module = $api['module'];
        $method = $api['method'];
        
        // 2. 映射 HTTP 方法到操作
        $action = $this->mapMethodToAction($method);
        
        // 3. 获取用户权限
        $userPermissions = $this->getUserPermissions($request->userId);
        
        // 4. 检查超级管理员权限（最高优先级）
        if (in_array('*:*:*', $userPermissions) || in_array('*', $userPermissions)) {
            $request->dataScope = 'all';
            $request->matchedPermission = '*:*:*';
            return $next($request);
        }
        
        // 5. 按优先级检查精确权限（带数据范围）
        $scopePriority = ['all', 'dept', 'own'];
        foreach ($scopePriority as $scope) {
            $exactPermission = "{$module}:{$action}:{$scope}";
            if (in_array($exactPermission, $userPermissions)) {
                // 精确匹配成功
                $request->dataScope = $scope;
                $request->matchedPermission = $exactPermission;
                return $next($request);
            }
        }
        
        // 6. 检查通配符权限（需要更严格的验证）
        foreach ($userPermissions as $perm) {
            if (strpos($perm, '*') === false) {
                continue; // 跳过非通配符权限
            }
            
            // 检查是否匹配当前模块和操作
            $matchResult = $this->matchWildcardPermission($perm, $module, $action);
            if ($matchResult['matched']) {
                $request->dataScope = $matchResult['scope'];
                $request->matchedPermission = $perm;
                return $next($request);
            }
        }
        
        // 7. 没有匹配的权限
        return json([
            'code' => 403,
            'msg' => '无权限访问',
            'data' => [
                'required_permissions' => [
                    "{$module}:{$action}:all",
                    "{$module}:{$action}:dept",
                    "{$module}:{$action}:own"
                ],
                'hint' => '请联系管理员分配相应权限'
            ]
        ]);
    }
    
    /**
     * 匹配通配符权限（改进版 - 更安全的通配符处理）
     * @param string $wildcardPerm 用户的通配符权限（如：user:*:*, user:view:*, *:view:*）
     * @param string $module 需要的模块（如：user）
     * @param string $action 需要的操作（如：view）
     * @return array ['matched' => bool, 'scope' => string]
     */
    private function matchWildcardPermission(string $wildcardPerm, string $module, string $action): array
    {
        $parts = explode(':', $wildcardPerm);
        
        // 确保权限格式正确（至少2段）
        if (count($parts) < 2) {
            return ['matched' => false, 'scope' => 'none'];
        }
        
        $permModule = $parts[0] ?? '';
        $permAction = $parts[1] ?? '';
        $permScope = $parts[2] ?? '';
        
        // 1. 检查模块是否匹配
        if ($permModule !== '*' && $permModule !== $module) {
            return ['matched' => false, 'scope' => 'none'];
        }
        
        // 2. 检查操作是否匹配
        if ($permAction !== '*' && $permAction !== $action) {
            return ['matched' => false, 'scope' => 'none'];
        }
        
        // 3. 确定数据范围
        $scope = 'all'; // 默认最高权限
        
        if ($permScope !== '' && $permScope !== '*') {
            // 如果明确指定了范围，使用指定的范围
            $scope = $permScope;
        } else if ($permModule === '*' || $permAction === '*') {
            // 如果模块或操作是通配符，给予最高权限
            $scope = 'all';
        }
        
        return ['matched' => true, 'scope' => $scope];
    }
    
    /**
     * 手动权限检查
     */
    private function checkManualPermission($request, $api, $next): Response
    {
        // 1. 获取指定的权限
        $requiredPermission = $api['required_permission'];
        
        if (empty($requiredPermission)) {
            return json([
                'code' => 500,
                'msg' => 'API配置错误：manual模式必须指定required_permission'
            ]);
        }
        
        // 2. 获取用户权限
        $userPermissions = $this->getUserPermissions($request->userId);
        
        // 3. 检查用户是否有该权限（支持通配符）
        if ($this->hasPermission($requiredPermission, $userPermissions)) {
            // 有权限，继续执行
            $request->matchedPermission = $requiredPermission;
            // 提取并注入数据范围（供业务逻辑使用）
            $request->dataScope = $this->extractScopeFromPermission($requiredPermission);
            return $next($request);
        }
        
        // 4. 没有权限
        return json([
            'code' => 403,
            'msg' => '无权限访问',
            'data' => [
                'required_permission' => $requiredPermission,
                'hint' => '该操作需要特殊权限，请联系管理员'
            ]
        ]);
    }
    
    /**
     * 检查是否有权限（支持通配符 - 改进版）
     * 用于手动模式的权限检查
     */
    private function hasPermission(string $required, array $userPermissions): bool
    {
        // 1. 精确匹配（最优先）
        if (in_array($required, $userPermissions)) {
            return true;
        }
        
        // 2. 检查超级管理员权限
        if (in_array('*', $userPermissions) || 
            in_array('*:*', $userPermissions) || 
            in_array('*:*:*', $userPermissions)) {
            return true;
        }
        
        // 3. 检查通配符权限（更严格的匹配）
        foreach ($userPermissions as $perm) {
            // 跳过非通配符权限
            if (strpos($perm, '*') === false) {
                continue;
            }
            
            // 使用改进的通配符匹配逻辑
            if ($this->matchWildcardPattern($perm, $required)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 通配符模式匹配（改进版 - 更安全且灵活）
     * @param string $pattern 通配符模式（如：user:*:*, user:*, article:*:*:*）
     * @param string $required 需要的权限（如：user:view:all）
     * @return bool
     */
    private function matchWildcardPattern(string $pattern, string $required): bool
    {
        // 将通配符权限和需要的权限都拆分成段
        $patternParts = explode(':', $pattern);
        $requiredParts = explode(':', $required);
        
        // 获取最小段数（用于匹配）
        $minLength = min(count($patternParts), count($requiredParts));
        
        // 逐段匹配（只匹配到最短的那个）
        for ($i = 0; $i < $minLength; $i++) {
            $patternPart = $patternParts[$i];
            $requiredPart = $requiredParts[$i];
            
            // 如果是通配符，跳过这一段
            if ($patternPart === '*') {
                continue;
            }
            
            // 如果不是通配符，必须精确匹配
            if ($patternPart !== $requiredPart) {
                return false;
            }
        }
        
        // 如果pattern比required短，检查pattern的最后一段是否是通配符
        if (count($patternParts) < count($requiredParts)) {
            // 最后一段必须是通配符才能匹配更长的权限
            $lastPart = end($patternParts);
            if ($lastPart !== '*') {
                return false;
            }
        }
        
        // 如果pattern比required长，检查多余的段是否都是通配符
        if (count($patternParts) > count($requiredParts)) {
            for ($i = count($requiredParts); $i < count($patternParts); $i++) {
                if ($patternParts[$i] !== '*') {
                    return false;
                }
            }
        }
        
        return true;
    }
    
    /**
     * 映射 HTTP 方法到操作
     */
    private function mapMethodToAction(string $method): string
    {
        return match(strtoupper($method)) {
            'GET' => 'view',
            'POST' => 'add',
            'PUT', 'PATCH' => 'edit',
            'DELETE' => 'delete',
            default => 'unknown'
        };
    }
    
    /**
     * 从权限字符串中提取数据范围
     * @param string $permission 权限字符串（如：user:view:dept）
     * @return string 数据范围（all/dept/own）
     */
    private function extractScopeFromPermission(string $permission): string
    {
        // 'user:view:dept' → ['user', 'view', 'dept']
        $parts = explode(':', $permission);
        
        // 取最后一个部分
        $scope = end($parts);
        
        // 如果是 * 或其他特殊情况，默认为 all
        if ($scope === '*' || empty($scope)) {
            return 'all';
        }
        
        // 验证是否是有效的范围
        if (in_array($scope, ['all', 'dept', 'own'])) {
            return $scope;
        }
        
        // 如果不是标准范围，默认返回 own（最保守）
        return 'own';
    }
    
    /**
     * 查找匹配的API配置（支持路径参数）
     * @param string $requestPath 请求路径，如 /v1/cardkey/delete/123
     * @param string $method HTTP方法
     * @return array|null API配置
     */
    private function findMatchingApi(string $requestPath, string $method): ?array
    {
        // 标准化路径（移除末尾斜杠）
        $normalizedPath = rtrim($requestPath, '/');
        
        // 1. 先尝试精确匹配（性能最优）
        // 尝试两种格式：带斜杠和不带斜杠
        $api = Db::table('bl_api')
            ->where(function($query) use ($normalizedPath) {
                $query->where('full_path', $normalizedPath)
                      ->whereOr('full_path', $normalizedPath . '/');
            })
            ->where(function($query) use ($method) {
                $query->where('method', $method)
                      ->whereOr('method', 'ANY');
            })
            ->find();
        
        if ($api) {
            return $api;
        }
        
        // 2. 精确匹配失败，尝试模式匹配（处理路径参数）
        // 获取所有可能匹配的API（同一版本和模块）
        $pathParts = explode('/', trim($requestPath, '/'));
        if (count($pathParts) < 2) {
            return null;
        }
        
        // 提取版本和模块
        $version = $pathParts[0]; // v1, v2...
        $module = $pathParts[1] ?? '';
        
        // 查询同版本、同模块的所有API
        $candidates = Db::table('bl_api')
            ->where('full_path', 'like', "/{$version}/{$module}/%")
            ->where(function($query) use ($method) {
                $query->where('method', $method)
                      ->whereOr('method', 'ANY');
            })
            ->select();
        
        // 3. 逐个匹配路径模式
        foreach ($candidates as $candidate) {
            if ($this->matchPathPattern($candidate['full_path'], $requestPath)) {
                return $candidate;
            }
        }
        
        return null;
    }
    
    /**
     * 匹配路径模式
     * @param string $pattern 模式路径，如 /v1/cardkey/delete/{id}
     * @param string $path 实际路径，如 /v1/cardkey/delete/123
     * @return bool
     */
    private function matchPathPattern(string $pattern, string $path): bool
    {
        // 将模式转换为正则表达式
        // {id} -> [^/]+
        // {user_id} -> [^/]+
        $regex = preg_replace('/\{[^}]+\}/', '[^/]+', $pattern);
        $regex = '#^' . $regex . '$#';
        
        return preg_match($regex, $path) === 1;
    }
    
    /**
     * 获取用户权限列表
     */
    private function getUserPermissions(int $userId): array
    {
        // 查询用户的所有权限标识
        $permissions = Db::table('bl_permissions')
            ->alias('p')
            ->join('bl_role_permissions rp', 'p.id = rp.permission_id')
            ->join('bl_user_roles ur', 'rp.role_id = ur.role_id')
            ->where('ur.user_id', $userId)
            ->where('p.delete_time', null)
            ->column('p.iden');
        
        // 去重并返回
        return array_unique($permissions);
    }
}
