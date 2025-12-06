<?php

namespace app\api\middleware;

use think\facade\Db;
use think\Response;

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
        $fullPath = $request->pathinfo();
        $method = $request->method();
        $userId = $request->userId ?? null;
        
        // 如果没有用户ID，说明没有通过 Auth 中间件，直接拒绝
        if (!$userId) {
            return json(['code' => 401, 'msg' => '未登录或登录已过期']);
        }
        
        // 2. 查询 API 配置
        $api = Db::table('bl_api')
            ->where('full_path', $fullPath)
            ->where(function($query) use ($method) {
                $query->where('method', $method)
                      ->whereOr('method', 'ANY');
            })
            ->find();
        
        // 如果 API 不存在
        if (!$api) {
            return json(['code' => 404, 'msg' => 'API不存在']);
        }
        
        // 3. 检查 API 状态
        if ($api['status'] == 0) {
            return json(['code' => 503, 'msg' => 'API维护中，暂时无法访问']);
        }
        
        if ($api['status'] == 3) {
            return json(['code' => 403, 'msg' => 'API已关闭']);
        }
        
        // 4. 根据 check_mode 执行不同的权限检查
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
     * 自动权限检查
     */
    private function checkAutoPermission($request, $api, $next): Response
    {
        // 1. 提取模块和方法
        $module = $api['module'];
        $method = $api['method'];
        
        // 2. 映射 HTTP 方法到操作
        $action = $this->mapMethodToAction($method);
        
        // 3. 构建所需权限列表（按优先级）
        $requiredPermissions = [
            "{$module}:{$action}:all",   // 最高权限：查看所有
            "{$module}:{$action}:dept",  // 中等权限：查看部门
            "{$module}:{$action}:own",   // 最低权限：查看自己
            "{$module}:*",               // 模块所有权限
            "*:*:*"                      // 超级管理员
        ];
        
        // 4. 获取用户权限
        $userPermissions = $this->getUserPermissions($request->userId);
        
        // 5. 匹配权限（按优先级）
        foreach ($requiredPermissions as $perm) {
            if (in_array($perm, $userPermissions)) {
                // 匹配成功！
                
                // 6. 提取数据权限范围
                $scope = $this->extractScope($perm);
                
                // 7. 注入到请求中，供业务逻辑使用
                $request->dataScope = $scope;
                $request->matchedPermission = $perm;
                
                // 8. 继续执行业务逻辑
                return $next($request);
            }
        }
        
        // 9. 没有匹配的权限
        return json([
            'code' => 403,
            'msg' => '无权限访问',
            'data' => [
                'required_permissions' => $requiredPermissions,
                'hint' => '请联系管理员分配相应权限'
            ]
        ]);
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
     * 检查是否有权限（支持通配符）
     */
    private function hasPermission(string $required, array $userPermissions): bool
    {
        // 1. 精确匹配
        if (in_array($required, $userPermissions)) {
            return true;
        }
        
        // 2. 检查通配符权限
        foreach ($userPermissions as $perm) {
            // 超级管理员（匹配所有）
            if ($perm === '*' || $perm === '*:*' || $perm === '*:*:*') {
                return true;
            }
            
            // 通配符匹配
            if (strpos($perm, '*') !== false) {
                // 将通配符权限转换为正则表达式
                // 'user:*' → '^user:[^:]+$'
                // 'user:*:*' → '^user:[^:]+:[^:]+$'
                $pattern = '/^' . str_replace('*', '[^:]+', preg_quote($perm, '/')) . '$/';
                if (preg_match($pattern, $required)) {
                    return true;
                }
            }
        }
        
        return false;
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
     * 提取权限范围
     */
    private function extractScope(string $permission): string
    {
        // 'user:view:dept' → ['user', 'view', 'dept']
        $parts = explode(':', $permission);
        
        // 取最后一个部分
        $scope = end($parts);
        
        // 如果是 * 或其他特殊情况，默认为 all
        if ($scope === '*' || empty($scope)) {
            return 'all';
        }
        
        return $scope;
    }
    
    /**
     * 获取用户权限列表
     */
    private function getUserPermissions(int $userId): array
    {
        // 查询用户的所有权限标识
        $permissions = Db::table('bl_permissions')
            ->alias('p')
            ->join('bl_roles_permissions rp', 'p.id = rp.permission_id')
            ->join('bl_users_roles ur', 'rp.role_id = ur.role_id')
            ->where('ur.user_id', $userId)
            ->where('p.delete_time', null)
            ->column('p.iden');
        
        // 去重并返回
        return array_unique($permissions);
    }
}
