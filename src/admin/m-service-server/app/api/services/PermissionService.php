<?php

namespace app\api\services;

use app\api\model\permissions;
use think\facade\Db;

/**
 * 权限服务类
 */
class PermissionService
{
    /**
     * 获取权限列表
     * @param array $conditions 查询条件
     * @param bool $tree 是否返回树形结构
     * @param int $page 页码，默认1
     * @param int $limit 每页条数，默认100
     * @return array
     */
    public static function getPermissionList(array $conditions = [], bool $tree = false, int $page = 1, int $limit = 100): array
    {
        try {
            // 构建查询条件
            $query = permissions::alias('p');
            
            // ID精确匹配
            if (isset($conditions['id']) && $conditions['id'] !== '') {
                $query->where('p.id', '=', $conditions['id']);
            }
            
            // 模糊匹配字段
            foreach (['name', 'description', 'iden'] as $field) {
                if (!empty($conditions[$field])) {
                    $query->where('p.'.$field, 'like', '%' . $conditions[$field] . '%');
                }
            }

            // 删除状态查询
            if (isset($conditions['delete_status'])) {
                if ($conditions['delete_status'] === 'only_deleted') {
                    $query->onlyTrashed(); // 仅查询已删除
                } else if ($conditions['delete_status'] === 'with_deleted') {
                    $query->withTrashed(); // 包含已删除
                }
                // 默认不使用withTrashed，查询未删除的数据
            }
            
            // 按更新时间排序
            $query->order('p.update_time', 'desc');
            
            // 查询总记录数
            $total = $query->count();
            
            // 分页查询
            if (!$tree) {
                $permissions = $query->page($page, $limit)->select();
            } else {
                // 树形结构需要获取所有数据
                $permissions = $query->select();
            }
            
            // 是否组织为树形结构（按iden分组）
            if ($tree) {
                return [
                    'code' => 200,
                    'msg' => '查询成功',
                    'data' => self::organizePermissionsTree($permissions)
                ];
            }
            
            // 返回列表结构 - 修改为与其他服务层一致的格式
            return [
                'code' => 200,
                'msg' => '查询成功',
                'data' => [
                    'list' => $permissions,
                    'pagination' => [
                        'total' => $total,
                        'current' => $page,
                        'page_size' => $limit,
                        'pages' => ceil($total / $limit)
                    ]
                ]
            ];
        } catch (\Exception $e) {
            LogService::error($e);
            return ['code' => 500, 'msg' => '查询失败：' . $e->getMessage()];
        }
    }
    
    /**
     * 获取权限树
     * @return array
     */
    public static function getPermissionTree(): array
    {
        try {
            // 获取所有权限
            $permissions = permissions::select();
            
            // 使用iden字段分组构建权限树
            $tree = self::organizePermissionsTree($permissions);
            
            return [
                'code' => 200,
                'msg' => '查询成功',
                'data' => $tree
            ];
        } catch (\Exception $e) {
            LogService::error($e);
            return ['code' => 500, 'msg' => '查询失败：' . $e->getMessage()];
        }
    }
    
    /**
     * 添加权限
     * @param array $data 权限数据
     * @return array
     */
    public static function addPermission(array $data): array
    {
        try {
            // 验证必填字段
            if (empty($data['name']) || empty($data['iden'])) {
                return ['code' => 400, 'msg' => '权限名称和标识符不能为空'];
            }
            
            // 检查名称是否已存在
            $exists = permissions::where('name', $data['name'])->find();
            if ($exists) {
                LogService::log("添加权限失败，名称已存在：{$data['name']}", [], 'warning');
                return ['code' => 400, 'msg' => '权限名称已存在'];
            }
            
            // 创建权限 (模型的$disuse属性会自动过滤real字段)
            $permission = new permissions();
            $permission->save($data);
            
            LogService::log("添加权限成功，ID：{$permission->id}，名称：{$data['name']}");
            
            return [
                'code' => 200,
                'msg' => '添加成功',
                'data' => $permission
            ];
        } catch (\Exception $e) {
            LogService::error($e);
            return ['code' => 500, 'msg' => '添加失败：' . $e->getMessage()];
        }
    }
    
    /**
     * 更新权限
     * @param int $id 权限ID
     * @param array $data 权限数据
     * @return array
     */
    public static function updatePermission(int $id, array $data): array
    {
        try {
            // 查询权限是否存在
            $permission = permissions::find($id);
            if (!$permission) {
                LogService::log("更新权限失败，ID不存在：{$id}", [], 'warning');
                return ['code' => 404, 'msg' => '权限不存在'];
            }
            
            // 如果更新名称，检查是否与其他权限冲突
            if (isset($data['name']) && $data['name'] !== $permission->name) {
                $exists = permissions::where('name', $data['name'])->where('id', '<>', $id)->find();
                if ($exists) {
                    LogService::log("更新权限失败，名称已存在：{$data['name']}", [], 'warning');
                    return ['code' => 400, 'msg' => '权限名称已存在'];
                }
            }
            
            // 更新权限 (模型的$disuse属性会自动过滤real字段)
            $permission->save($data);
            
            LogService::log("更新权限成功，ID：{$id}，名称：{$permission->name}");
            
            return [
                'code' => 200,
                'msg' => '更新成功',
                'data' => $permission
            ];
        } catch (\Exception $e) {
            LogService::error($e);
            return ['code' => 500, 'msg' => '更新失败：' . $e->getMessage()];
        }
    }
    
    /**
     * 删除权限
     * @param int $id 权限ID
     * @param bool $real 是否物理删除，默认为false(软删除)
     * @return array
     */
    public static function deletePermission(int $id, bool $real = false): array
    {
        try {
            // 查询权限是否存在
            $permission = permissions::withTrashed()->find($id);
            if (!$permission) {
                LogService::log("删除权限失败，ID不存在：{$id}", [], 'warning');
                return ['code' => 404, 'msg' => '权限不存在'];
            }
            
            // 检查是否有角色使用此权限
            $roleCount = Db::name('role_permissions')->where('permission_id', $id)->count();
            if ($roleCount > 0) {
                LogService::log("删除权限失败，已被角色使用：{$id}", [], 'warning');
                return ['code' => 400, 'msg' => "该权限已被 {$roleCount} 个角色使用，请先解除关联"];
            }
            
            // 根据real参数决定删除方式
            if ($real === true) {
                // 物理删除
                if ($permission->delete_time !== null) {
                    // 如果已经软删除，则需要先恢复
                    $permission->restore();
                }
                $permission->force()->delete();
                LogService::log("物理删除权限成功，ID：{$id}，名称：{$permission->name}");
            } else {
                // 软删除
                $permission->delete();
                LogService::log("软删除权限成功，ID：{$id}，名称：{$permission->name}");
            }
            
            return [
                'code' => 200,
                'msg' => '删除成功'
            ];
        } catch (\Exception $e) {
            LogService::error($e);
            return ['code' => 500, 'msg' => '删除失败：' . $e->getMessage()];
        }
    }
    
    /**
     * 获取权限级别
     * @param array $permissions 权限列表
     * @return array 带有级别信息的权限列表
     */
    public static function getPermissionLevels(array $permissions): array
    {
        // 使用ID为键重新组织权限数组
        $permissionsById = [];
        foreach ($permissions as $permission) {
            $id = $permission['id'];
            $permissionsById[$id] = $permission;
            
            // 添加级别字段，默认为1（顶级权限）
            $permissionsById[$id]['level'] = 1;
        }
        
        return array_values($permissionsById);
    }

    /**
     * 将权限列表按照iden字段分组组织为树形结构
     * @param array|Collection $permissions 权限列表
     * @return array
     */
    protected static function organizePermissionsTree($permissions): array
    {
        if (empty($permissions)) {
            return [];
        }
        
        // 按权限类型分组
        $groupedPermissions = [];
        
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
     * 恢复已删除的权限
     * @param int $id 权限ID
     * @return array
     */
    public static function restorePermission(int $id): array
    {
        try {
            // 查询已删除的权限
            $permission = permissions::onlyTrashed()->find($id);
            if (!$permission) {
                LogService::log("恢复权限失败，ID不存在或未被删除：{$id}", [], 'warning');
                return ['code' => 404, 'msg' => '权限不存在或未被删除'];
            }
            
            // 恢复权限
            $permission->restore();
            
            LogService::log("恢复权限成功，ID：{$id}，名称：{$permission->name}");
            
            return [
                'code' => 200,
                'msg' => '恢复成功',
                'data' => $permission
            ];
        } catch (\Exception $e) {
            LogService::error($e);
            return ['code' => 500, 'msg' => '恢复失败：' . $e->getMessage()];
        }
    }

    /**
     * 分配API权限
     * @param int $permissionId 权限ID
     * @param array $apiIds API ID数组
     * @return array
     */
    public static function assignApis(int $permissionId, array $apiIds): array
    {
        try {
            // 开始事务
            Db::startTrans();
            
            // 查询权限是否存在
            $permission = permissions::find($permissionId);
            if (!$permission) {
                LogService::log("分配API权限失败，权限ID不存在：{$permissionId}", [], 'warning');
                return ['code' => 404, 'msg' => '权限不存在'];
            }
            
            // 检查API是否存在
            if (!empty($apiIds)) {
                $existingApis = \app\api\model\Api::where('id', 'in', $apiIds)->column('id');
                $nonExistingApis = array_diff($apiIds, $existingApis);
                
                if (!empty($nonExistingApis)) {
                    LogService::log("分配API权限失败，部分API ID不存在：".json_encode($nonExistingApis), [], 'warning');
                    return ['code' => 400, 'msg' => '部分API不存在'];
                }
            }
            
            // 清除现有的API关联
            Db::name('permission_api')->where('permission_id', $permissionId)->delete();

            // 建立新的API关联
            if (!empty($apiIds)) {
                // 手动生成ID并插入数据
                $insertData = [];
                foreach ($apiIds as $apiId) {
                    // 检查是否已存在，避免重复插入
                    $exists = Db::name('permission_api')
                        ->where('permission_id', $permissionId)
                        ->where('api_id', $apiId)
                        ->find();

                    if (!$exists) {
                        $insertData[] = [
                            'id' => \utils\NumUtil::generateNumberCode(1, 5),
                            'permission_id' => $permissionId,
                            'api_id' => $apiId,
                            'create_time' => date('Y-m-d H:i:s'),
                            'update_time' => date('Y-m-d H:i:s')
                        ];
                    }
                }

                // 批量插入（如果有数据）
                if (!empty($insertData)) {
                    Db::name('permission_api')->insertAll($insertData);
                }
            }
            
            // 提交事务
            Db::commit();
            
            LogService::log("分配API权限成功，权限ID：{$permissionId}，API数量：".count($apiIds));
            
            return [
                'code' => 200,
                'msg' => '分配成功',
                'data' => [
                    'permission_id' => $permissionId,
                    'api_count' => count($apiIds),
                    'api_ids' => $apiIds
                ]
            ];
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            LogService::error($e);
            return ['code' => 500, 'msg' => '分配失败：' . $e->getMessage()];
        }
    }
    
    /**
     * 获取权限关联的API列表
     * @param int $permissionId 权限ID
     * @return array
     */
    public static function getPermissionApis(int $permissionId): array
    {
        try {
            // 查询权限是否存在
            $permission = permissions::find($permissionId);
            if (!$permission) {
                LogService::log("获取权限API失败，权限ID不存在：{$permissionId}", [], 'warning');
                return ['code' => 404, 'msg' => '权限不存在'];
            }

            // 直接查询关联的API列表
            $apis = Db::name('permission_api')
                ->alias('pa')
                ->join('api a', 'pa.api_id = a.id')
                ->where('pa.permission_id', $permissionId)
                ->field('a.id, a.full_path, a.method, a.description, a.status')
                ->select();

            return [
                'code' => 200,
                'msg' => '查询成功',
                'data' => [
                    'permission_id' => $permissionId,
                    'permission_name' => $permission->name,
                    'apis' => $apis,
                    'api_count' => count($apis)
                ]
            ];
        } catch (\Exception $e) {
            LogService::error($e);
            return ['code' => 500, 'msg' => '查询失败：' . $e->getMessage()];
        }
    }
} 