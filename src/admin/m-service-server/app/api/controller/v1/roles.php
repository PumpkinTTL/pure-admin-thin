<?php

namespace app\api\controller\v1;

use app\api\services\RoleServices;
use app\BaseController;

class roles extends BaseController
{
    /**
     * 获取角色列表
     * @return \think\Response
     */
    public function selectRolesAll()
    {
        $params = request()->param();
        
        // 处理分页参数
        $page = isset($params['page']) ? intval($params['page']) : 1;
        $pageSize = isset($params['page_size']) ? intval($params['page_size']) : 10;
        
        // 处理删除状态参数
        $queryDeleted = isset($params['query_deleted']) ? $params['query_deleted'] : 'not_deleted';
        
        // 处理排序参数
        if (isset($params['order_field'])) {
            $params['order_field'] = htmlspecialchars($params['order_field']);
        }
        if (isset($params['order_type'])) {
            $params['order_type'] = strtolower($params['order_type']) === 'asc' ? 'asc' : 'desc';
        }
        
        $result = RoleServices::getRoleList($params, $page, $pageSize, $queryDeleted);
        return json($result);
    }
    
    /**
     * 获取角色详情
     * @return \think\Response
     */
    public function detail()
    {
        $roleId = request()->param('id');
        if (empty($roleId)) {
            return json(['code' => 400, 'msg' => '角色ID不能为空']);
        }
        
        $result = RoleServices::getRoleDetail($roleId);
        return json($result);
    }
    
    /**
     * 创建角色
     * @return \think\Response
     */
    public function add()
    {
        $params = request()->param();
        
        // 角色数据
        $roleData = [
            'name' => $params['name'] ?? '',
            'iden' => $params['iden'] ?? '',
            'description' => $params['description'] ?? '',
        ];
        
        // 状态
        if (isset($params['status'])) {
            $roleData['status'] = intval($params['status']);
        }
        
        // 显示权重
        if (isset($params['show_weight'])) {
            $roleData['show_weight'] = intval($params['show_weight']);
        }
        
        // 权限IDs
        $permissionIds = isset($params['permission_ids']) ? (array)$params['permission_ids'] : [];
        
        $result = RoleServices::createRole($roleData, $permissionIds);
        return json($result);
    }
    
    /**
     * 更新角色
     * @return \think\Response
     */
    public function update()
    {
        $params = request()->param();
        
        // 角色ID
        $roleId = $params['id'] ?? 0;
        if (empty($roleId)) {
            return json(['code' => 400, 'msg' => '角色ID不能为空']);
        }
        
        // 角色数据
        $roleData = [];
        foreach (['name', 'iden', 'description'] as $field) {
            if (isset($params[$field])) {
                $roleData[$field] = $params[$field];
            }
        }
        
        // 状态
        if (isset($params['status'])) {
            $roleData['status'] = intval($params['status']);
        }
        
        // 显示权重
        if (isset($params['show_weight'])) {
            $roleData['show_weight'] = intval($params['show_weight']);
        }
        
        // 权限IDs，如果没有传入则不更新权限
        $permissionIds = isset($params['permission_ids']) ? (array)$params['permission_ids'] : null;
        
        $result = RoleServices::updateRole($roleId, $roleData, $permissionIds);
        return json($result);
    }
    
    /**
     * 删除角色
     * @return \think\Response
     */
    public function delete()
    {
        $params = request()->param();
        
        // 角色ID
        $roleId = $params['id'] ?? 0;
        if (empty($roleId)) {
            return json(['code' => 400, 'msg' => '角色ID不能为空']);
        }
        
        // 是否物理删除 - 检查real参数
        $realDelete = false;
        if (isset($params['real'])) {
            // 支持多种格式: 'true', true, '1', 1
            $realDelete = ($params['real'] === 'true' || $params['real'] === true || $params['real'] === '1' || $params['real'] === 1);
        }
        
        $result = RoleServices::deleteRole($roleId, $realDelete);
        return json($result);
    }
    
    /**
     * 恢复已删除的角色
     * @return \think\Response
     */
    public function restore()
    {
        $params = request()->param();
        
        // 角色ID
        $roleId = $params['id'] ?? 0;
        if (empty($roleId)) {
            return json(['code' => 400, 'msg' => '角色ID不能为空']);
        }
        
        $result = RoleServices::restoreRole($roleId);
        return json($result);
    }
    
    /**
     * 获取用户的角色和权限
     * @return \think\Response
     */
    public function selectRoleByUid()
    {
        $userId = request()->param('uid');
        if (empty($userId)) {
            return json(['code' => 400, 'msg' => '用户ID不能为空']);
        }
        
        $result = RoleServices::getUserRoles($userId);
        return json($result);
    }
    
    /**
     * 更新角色状态
     * @return \think\Response
     */
    public function updateStatus()
    {
        $params = request()->param();
        
        // 角色ID
        $roleId = $params['id'] ?? 0;
        if (empty($roleId)) {
            return json(['code' => 400, 'msg' => '角色ID不能为空']);
        }
        
        // 状态
        $status = isset($params['status']) ? intval($params['status']) : null;
        if ($status === null) {
            return json(['code' => 400, 'msg' => '状态参数不能为空']);
        }
        
        // 只更新状态字段
        $roleData = [
            'status' => $status
        ];
        
        $result = RoleServices::updateRole($roleId, $roleData, null);
        return json($result);
    }
    
    /**
     * 根据角色获取权限树
     * @return \think\Response
     */
    public function getPermissionsTree()
    {
        $roleId = request()->param('id');
        if (empty($roleId)) {
            return json(['code' => 400, 'msg' => '角色ID不能为空']);
        }
        
        $result = RoleServices::getRoleDetail($roleId);
        if ($result['code'] == 200) {
            // 只返回权限树
            return json([
                'code' => 200,
                'msg' => '查询成功',
                'data' => $result['data']['permissions_tree']
            ]);
        }
        
        return json($result);
    }
    
    /**
     * 设置角色权限（支持层级结构）
     * @return \think\Response
     */
    public function setPermissions()
    {
        $params = request()->param();
        
        // 角色ID
        $roleId = $params['id'] ?? 0;
        if (empty($roleId)) {
            return json(['code' => 400, 'msg' => '角色ID不能为空']);
        }
        
        // 权限IDs，如果是空数组则清空权限
        $permissionIds = isset($params['permission_ids']) ? (array)$params['permission_ids'] : [];
        
        // 更新角色的权限（只更新权限，不更新角色信息）
        $result = RoleServices::updateRole($roleId, [], $permissionIds);
        return json($result);
    }
    
    /**
     * 为角色分配权限（新API）
     * @return \think\Response
     */
    public function assignPermissions()
    {
        $params = request()->param();
        
        // 角色ID - 如果未提供，将自动生成
        $roleId = $params['role_id'] ?? null;
        
        // 权限ID数组
        $permissionIds = isset($params['permission_ids']) ? (array)$params['permission_ids'] : [];
        if (empty($permissionIds)) {
            return json(['code' => 400, 'msg' => '权限ID数组不能为空']);
        }
        
        // 调用服务层处理权限分配
        $result = RoleServices::assignPermissions($roleId, $permissionIds);
        return json($result);
    }
}