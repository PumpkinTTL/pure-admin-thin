<?php

namespace app\api\services;

use app\api\model\roles;
use app\api\model\userRoles;
use app\api\model\rolePermissions;
use think\Collection;
use app\api\model\users as UsersModel;
use think\facade\Db;
use app\api\model\permissions;
class RoleServices
{
    /**
     * 获取角色列表，支持条件查询和分页
     * @param array $conditions 查询条件
     * @param int $page 当前页码
     * @param int $pageSize 每页记录数
     * @param string $queryDeleted 删除状态：only_deleted-只查询已删除，not_deleted-只查询未删除，null-查询所有
     * @return array
     */
    public static function getRoleList(array $conditions = [], int $page = 1, int $pageSize = 10, string $queryDeleted = 'not_deleted'): array
    {
        try {
            // 构建查询条件
            $query = roles::alias('r');
            
            // 状态筛选
            if (isset($conditions['status']) && $conditions['status'] !== '') {
                $query->where('r.status', '=', intval($conditions['status']));
            }
            
            // ID精确匹配
            if (isset($conditions['id']) && $conditions['id'] !== '') {
                $query->where('r.id', '=', $conditions['id']);
            }

            // 模糊匹配字段
            foreach (['name', 'description', 'iden'] as $field) {
                if (!empty($conditions[$field])) {
                    $query->where('r.'.$field, 'like', '%' . $conditions[$field] . '%');
                }
            }

        
            
            // 根据删除状态筛选
            if ($queryDeleted === 'only_deleted') {
                // 只查询已删除的角色
                $query->onlyTrashed();
            } else if ($queryDeleted === 'not_deleted') {
                // 只查询未删除的角色
                // 不使用withTrashed，这样默认就是未删除的角色
            } else {
                // 查询所有角色（包括已删除）
                $query->withTrashed();
            }
            
            // 排序方式
            $orderField = $conditions['order_field'] ?? 'show_weight';
            $orderType = $conditions['order_type'] ?? 'desc';
            $validOrderFields = ['id', 'name', 'create_time', 'update_time', 'show_weight', 'status'];
            
            if (in_array($orderField, $validOrderFields)) {
                $query->order('r.' . $orderField, $orderType);
            } else {
                // 默认按权重和更新时间排序
                $query->order('r.show_weight', 'desc')->order('r.update_time', 'desc');
            }
            
            // 分页查询
            $total = $query->count(); // 总记录数
            
            // 先查询角色列表
            $list = $query->field('r.*')
                ->page($page, $pageSize)
                ->select()
                ->append(['status_text']);
            
            // 查询每个角色的权限
            foreach ($list as &$role) {
                // 加载角色的权限
                $permissions =  permissions::alias('p')
                    ->join('role_permissions rp', 'p.id = rp.permission_id')
                    ->where('rp.role_id', $role['id'])
                    ->field('p.id, p.name, p.iden, p.description')
                    ->select();
                
                $role->permissions = $permissions;
                
                // 组织权限树
                $role->permissions_tree = self::organizePermissionTree($permissions);
            }
            
            // 记录操作日志
            LogService::log("查询角色列表，条件：" . json_encode($conditions) . "，页码：{$page}，每页：{$pageSize}");
            
            // 返回分页数据
            return [
                'code' => 200,
                'msg' => '查询成功',
                'data' => [
                    'list' => $list,
                    'pagination' => [
                        'total' => $total,
                        'current' => $page,
                        'page_size' => $pageSize,
                        'pages' => ceil($total / $pageSize)
                    ]
                ]
            ];
        } catch (\Exception $e) {
            LogService::error($e);
            return ['code' => 500, 'msg' => '查询失败：' . $e->getMessage()];
        }
    }

    /**
     * 根据ID获取角色详情
     * @param int $roleId 角色ID
     * @return array
     */
    public static function getRoleDetail(int $roleId): array
    {
        try {
            // 查询角色信息
            $role = roles::find($roleId);
            
            if (!$role) {
                LogService::log("查询角色详情失败，角色ID不存在：{$roleId}", [], 'warning');
                return ['code' => 404, 'msg' => '角色不存在'];
            }
            
            // 添加状态文本
            $role->append(['status_text']);
            
            // 加载角色的权限
            $permissions =  permissions::alias('p')
                ->join('role_permissions rp', 'p.id = rp.permission_id')
                ->where('rp.role_id', $roleId)
                ->field('p.id, p.name, p.iden, p.description')
                ->select();
            
            // 查询使用该角色的用户数量
            $userCount = userRoles::where('role_id', $roleId)->count();
            
            // 查询使用该角色的用户列表（最多10个）
            $users = UsersModel::alias('u')
                ->join('user_roles ur', 'u.id = ur.user_id')
                ->where('ur.role_id', $roleId)
                ->field('u.id, u.username, u.nickname, u.email, u.avatar')
                ->limit(10)
                ->select();
            
            // 将权限组织为树形结构
            $permissionsTree = [];
            if (!empty($permissions)) {
                $permissionsTree = self::organizePermissionTree($permissions);
            }
            
            LogService::log("查询角色详情，角色ID：{$roleId}");
            
            return [
                'code' => 200,
                'msg' => '查询成功',
                'data' => [
                    'role' => $role,
                    'permissions' => $permissions,  // 原始权限列表
                    'permissions_tree' => $permissionsTree, // 按iden分组的权限树
                    'user_count' => $userCount,
                    'users' => $users
                ]
            ];
        } catch (\Exception $e) {
            LogService::error($e);
            return ['code' => 500, 'msg' => '查询失败：' . $e->getMessage()];
        }
    }

    /**
     * 创建角色
     * @param array $roleData 角色数据
     * @param array $permissionIds 权限ID数组
     * @return array
     */
    public static function createRole(array $roleData, array $permissionIds = []): array
    {
        // 验证必填字段
        if (empty($roleData['name']) || empty($roleData['iden'])) {
            return ['code' => 400, 'msg' => '角色名称和标识符不能为空'];
        }
        
        // 检查角色标识符是否已存在
        $existingRole = roles::where('iden', $roleData['iden'])->find();
        if ($existingRole) {
            LogService::log("创建角色失败，标识符已存在：{$roleData['iden']}", [], 'warning');
            return ['code' => 400, 'msg' => '角色标识符已存在'];
        }
        
        // 设置默认值
        if (!isset($roleData['status'])) {
            $roleData['status'] = 1; // 默认启用
        }
        
        if (!isset($roleData['show_weight'])) {
            // 获取当前最大权重值并加1
            $maxWeight = roles::max('show_weight');
            $roleData['show_weight'] = ($maxWeight ? $maxWeight + 1 : 500);
        }
        
        // 添加创建和更新时间
        $now = date('Y-m-d H:i:s');
        $roleData['create_time'] = $now;
        $roleData['update_time'] = $now;
        
        Db::startTrans();
        try {
            // 创建角色
            $role = new roles();
            $role->save($roleData);
            $roleId = $role->id;
            
            // 分配权限
            if (!empty($permissionIds)) {
                $role->permissions()->attach($permissionIds);
            }
            
            Db::commit();
            
            LogService::log("创建角色成功，角色ID：{$roleId}，角色名称：{$roleData['name']}");
            
            // 重新查询角色详情，包含权限
            $newRole = roles::with(['permissions' => function($query) {
                $query->alias('p')
                      ->field('p.id,p.name,p.iden,p.description');
            }])->find($roleId)->append(['status_text']);
            
            return [
                'code' => 200,
                'msg' => '创建成功',
                'data' => $newRole
            ];
        } catch (\Exception $e) {
            Db::rollback();
            LogService::error($e);
            return ['code' => 500, 'msg' => '创建失败：' . $e->getMessage()];
        }
    }

    /**
     * 更新角色
     * @param int $roleId 角色ID
     * @param array $roleData 角色数据
     * @param array $permissionIds 权限ID数组
     * @return array
     */
    public static function updateRole(int $roleId, array $roleData, array $permissionIds = null): array
    {
        // 查询角色是否存在
        $role = roles::find($roleId);
        if (!$role) {
            LogService::log("更新角色失败，角色ID不存在：{$roleId}", [], 'warning');
            return ['code' => 404, 'msg' => '角色不存在'];
        }
        
        // 如果更新标识符，检查是否与其他角色冲突
        if (isset($roleData['iden']) && $roleData['iden'] !== $role->iden) {
            $existingRole = roles::where('iden', $roleData['iden'])->where('id', '<>', $roleId)->find();
            if ($existingRole) {
                LogService::log("更新角色失败，标识符已存在：{$roleData['iden']}", [], 'warning');
                return ['code' => 400, 'msg' => '角色标识符已存在'];
            }
        }
        
        // 更新时间
        $roleData['update_time'] = date('Y-m-d H:i:s');
        
        // 检查是否只更新状态
        $isStatusUpdateOnly = count($roleData) == 2 && isset($roleData['status']) && isset($roleData['update_time']);
        
        Db::startTrans();
        try {
            // 更新角色信息
            $role->save($roleData);
            
            // 如果提供了权限ID数组，则更新权限
            if ($permissionIds !== null) {
                // 使用模型关联方法更新权限
                $role->permissions()->detach(); // 先解除所有权限
                
                if (!empty($permissionIds)) {
                    $role->permissions()->attach($permissionIds); // 重新添加权限
                }
            }
            
            Db::commit();
            
            // 日志记录
            if ($isStatusUpdateOnly) {
                $statusText = $roleData['status'] == 1 ? '启用' : '禁用';
                LogService::log("更新角色状态成功，角色ID：{$roleId}，状态：{$statusText}");
            } else {
                $roleName = isset($roleData['name']) ? $roleData['name'] : $role->name;
                LogService::log("更新角色成功，角色ID：{$roleId}，角色名称：{$roleName}");
            }
            
            // 查询更新后的角色信息，包含权限
            $updatedRole = roles::with(['permissions' => function($query) {
                $query->alias('p')
                      ->field('p.id,p.name,p.iden,p.description');
            }])->find($roleId)->append(['status_text']);
            
            return [
                'code' => 200,
                'msg' => $isStatusUpdateOnly ? '状态更新成功' : '更新成功',
                'data' => $updatedRole
            ];
        } catch (\Exception $e) {
            Db::rollback();
            LogService::error($e);
            return ['code' => 500, 'msg' => '更新失败：' . $e->getMessage()];
        }
    }

    /**
     * 删除角色
     * @param int $roleId 角色ID
     * @param bool $realDelete 是否真实删除（物理删除）
     * @return array
     */
    public static function deleteRole(int $roleId, bool $realDelete = false): array
    {
        // 查询角色是否存在
        $role = roles::withTrashed()->find($roleId);
        if (!$role) {
            LogService::log("删除角色失败，角色ID不存在：{$roleId}", [], 'warning');
            return ['code' => 404, 'msg' => '角色不存在'];
        }
        
        // 检查是否有用户在使用该角色
        $userCount = userRoles::where('role_id', $roleId)->count();
        
        // 如果是物理删除且有关联用户，则禁止删除
        if ($userCount > 0 && $realDelete) {
            LogService::log("物理删除角色失败，角色ID：{$roleId}，有 {$userCount} 个用户正在使用此角色", [], 'warning');
            return ['code' => 400, 'msg' => "无法删除此角色，有 {$userCount} 个用户正在使用。请先移除这些用户的角色分配。"];
        }
        
        Db::startTrans();
        try {
            // 如果是物理删除
            if ($realDelete) {
                // 记录详细信息，便于日志追踪
                $roleName = $role->name;
                $roleIden = $role->iden;
                
                // 1. 解除与权限的关联
                $role->permissions()->detach();
                
                // 2. 删除用户-角色关联（如果允许）
                if ($userCount > 0) {
                    userRoles::where('role_id', $roleId)->delete(true);
                }
                
                // 3. 物理删除角色
                $role->delete(true);
                
                LogService::log("物理删除角色成功，角色ID：{$roleId}，角色名称：{$roleName}，标识符：{$roleIden}");
            } else {
                // 软删除
                roles::destroy($roleId);
                LogService::log("软删除角色成功，角色ID：{$roleId}，角色名称：{$role->name}");
            }
            
            Db::commit();
            
            return [
                'code' => 200,
                'msg' => $realDelete ? '角色已永久删除' : '角色已删除'
            ];
        } catch (\Exception $e) {
            Db::rollback();
            LogService::error($e);
            return ['code' => 500, 'msg' => '删除失败：' . $e->getMessage()];
        }
    }

    /**
     * 恢复已删除的角色
     * @param int $roleId 角色ID
     * @return array
     */
    public static function restoreRole(int $roleId): array
    {
        // 查询已删除的角色
        $role = roles::onlyTrashed()->find($roleId);
        if (!$role) {
            LogService::log("恢复角色失败，角色ID不存在或未被删除：{$roleId}", [], 'warning');
            return ['code' => 404, 'msg' => '角色不存在或未被删除'];
        }
        
        try {
            // 恢复角色
            $role->restore();
            
            LogService::log("恢复角色成功，角色ID：{$roleId}，角色名称：{$role->name}");
            
            return [
                'code' => 200,
                'msg' => '角色已恢复',
                'data' => $role
            ];
        } catch (\Exception $e) {
            LogService::error($e);
            return ['code' => 500, 'msg' => '恢复失败：' . $e->getMessage()];
        }
    }

    /**
     * 获取用户的角色和权限信息
     * @param int $userId 用户ID
     * @return array
     */
    public static function getUserRoles(int $userId): array
    {
        try {
            // 查询用户信息
            $user = UsersModel::find($userId);
            
            if (!$user) {
                return ['code' => 404, 'msg' => '用户不存在'];
            }
            
            // 查询用户的所有角色
            $roles = roles::alias('r')
                ->join('user_roles ur', 'r.id = ur.role_id')
                ->where('ur.user_id', $userId)
                ->field('r.id, r.name, r.iden, r.description')
                ->select();
            
            // 整理用户的所有权限，并去重
            $allPermissions = [];
            $permissionIdsMap = []; // 用于权限去重
            
            // 获取用户所有角色的权限
            foreach ($roles as $role) {
                // 查询该角色下的所有权限
                $permissions = permissions::alias('p')
                    ->join('role_permissions rp', 'p.id = rp.permission_id')
                    ->where('rp.role_id', $role['id'])
                    ->field('p.id, p.name, p.iden, p.description')
                    ->select();
                
                foreach ($permissions as $permission) {
                    // 如果该权限ID还未添加到结果中
                    if (!isset($permissionIdsMap[$permission['id']])) {
                        $permissionIdsMap[$permission['id']] = true;
                        $allPermissions[] = $permission;
                    }
                }
            }
            
            // 将权限组织为树形结构
            $permissionsTree = self::organizePermissionTree($allPermissions);
            
            LogService::log("查询用户角色和权限信息，用户ID：{$userId}");
            
            return [
                'code' => 200,
                'msg' => '查询成功',
                'data' => [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'roles' => $roles,
                    'permissions' => $allPermissions, // 扁平权限列表
                    'permissions_tree' => $permissionsTree // 按iden分组的权限树
                ]
            ];
        } catch (\Exception $e) {
            LogService::error($e);
            return ['code' => 500, 'msg' => '查询失败：' . $e->getMessage()];
        }
    }

    /**
     * 旧版方法，保留向后兼容
     */
    public static function selectRoleByMap(array $conditions = [])
    {
        // 标准TP6多维数组条件构造
        $map = [];

        // ID精确匹配
        if (isset($conditions['id']) && $conditions['id'] !== '') {
            $map[] = ['id', '=', $conditions['id']];
        }

        // 模糊匹配字段
        foreach (['name', 'description', 'iden'] as $field) {
            if (!empty($conditions[$field])) {
                $map[] = [$field, 'like', '%' . $conditions[$field] . '%'];
            }
        }

        // 官方标准查询写法
        return roles::where($map)
            ->order('update_time', 'desc')
            ->select();
    }

    /**
     * 旧版方法，保留向后兼容
     */
    public static function selectRoleByUid($uid)
    {
        return UsersModel::with(['roles.permissions' => function ($query) {
            $query->field('id,name,iden'); // 只查询需要的字段
        }])->find($uid);
    }

    /**
     * 旧版方法，保留向后兼容
     */
    public static function deleteRolesByUid($uid): bool
    {
        return Db::name('user_roles')->where('user_id', $uid)->delete();
    }

    /**
     * 旧版方法，保留向后兼容
     */
    public static function insertRoleBatch(array $data): int
    {
        return Db::name('user_roles')->insertAll($data);
    }

    /**
     * 将权限列表组织为树形结构
     * 根据权限的iden字段分组
     * @param array|Collection $permissions 权限列表
     * @return array
     */
    protected static function organizePermissionTree($permissions): array
    {
        if (empty($permissions)) {
            return [];
        }
        
        // 按权限类型分组
        $groupedPermissions = [];
        
        // 遍历权限
        foreach ($permissions as $permission) {
            // 兼容数组和对象两种格式
            $id = is_array($permission) ? $permission['id'] : $permission->id;
            $name = is_array($permission) ? $permission['name'] : $permission->name;
            $iden = is_array($permission) ? $permission['iden'] : $permission->iden;
            $description = is_array($permission) ? $permission['description'] : $permission->description;
            
            // 确保该分组存在
            if (!isset($groupedPermissions[$iden])) {
                $groupedPermissions[$iden] = [];
            }
            
            // 添加到对应分组
            $groupedPermissions[$iden][] = [
                'id' => $id,
                'name' => $name,
                'iden' => $iden,
                'description' => $description
            ];
        }
        
        return $groupedPermissions;
    }

    /**
     * 分配权限给角色
     * @param mixed $roleId 角色ID，如果未提供则生成5位数ID
     * @param array $permissionIds 权限ID数组
     * @return array
     */
    public static function assignPermissions($roleId = null, array $permissionIds = []): array
    {
        Db::startTrans();
        try {
            // 如果未提供角色ID，生成一个5位数的ID
            if (empty($roleId)) {
                $roleId = rand(10000, 99999);
                // 确保ID不重复
                while (Db::name('role_permissions')->where('id', $roleId)->find()) {
                    $roleId = rand(10000, 99999);
                }
            }
            
            // 查询当前角色已有的权限
            $existingPermissions = Db::name('role_permissions')
                ->where('role_id', $roleId)
                ->select()
                ->toArray();
            
            $existingPermissionIds = array_column($existingPermissions, 'permission_id');
            
            // 判断前端传来的权限与数据库中已有权限的差异
            $toAddPermissionIds = array_diff($permissionIds, $existingPermissionIds); // 需要新增的权限
            $toRemovePermissionIds = array_diff($existingPermissionIds, $permissionIds); // 需要删除的权限
            
            // 删除需要移除的权限
            if (!empty($toRemovePermissionIds)) {
                Db::name('role_permissions')
                    ->where('role_id', $roleId)
                    ->whereIn('permission_id', $toRemovePermissionIds)
                    ->delete();
            }
            
            // 添加新的权限
            if (!empty($toAddPermissionIds)) {
                $now = date('Y-m-d H:i:s');
                $insertData = [];
                foreach ($toAddPermissionIds as $permissionId) {
                    $insertData[] = [
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                        'create_time' => $now,
                        'update_time' => $now
                    ];
                }
                Db::name('role_permissions')->insertAll($insertData);
            }
            
            Db::commit();
            
            // 查询更新后的权限列表
            $newPermissions = Db::name('role_permissions')
                ->where('role_id', $roleId)
                ->select()
                ->toArray();
            
            $newPermissionIds = array_column($newPermissions, 'permission_id');
            
            // 确保最终的权限列表与前端传来的一致
            if (count($newPermissionIds) != count($permissionIds) || 
                count(array_diff($newPermissionIds, $permissionIds)) > 0 || 
                count(array_diff($permissionIds, $newPermissionIds)) > 0) {
                throw new \Exception("权限分配失败：最终权限列表与预期不符");
            }
            
            LogService::log("角色权限分配成功，角色ID：{$roleId}，共分配 " . count($permissionIds) . " 个权限");
            
            return [
                'code' => 200,
                'msg' => '权限分配成功',
                'data' => [
                    'role_id' => $roleId,
                    'permission_count' => count($newPermissionIds),
                    'permissions' => $newPermissions
                ]
            ];
        } catch (\Exception $e) {
            Db::rollback();
            LogService::error($e);
            return ['code' => 500, 'msg' => '权限分配失败：' . $e->getMessage()];
        }
    }
}