<?php

namespace app\api\controller\v1;

use app\api\services\PermissionService;
use app\BaseController;

class permissions extends BaseController
{
    /**
     * 获取权限列表
     * @return \think\Response
     */
    public function index()
    {
        $params = request()->param();
        
        // 是否返回树形结构
        $tree = isset($params['tree']) && ($params['tree'] === 'true' || $params['tree'] === true || $params['tree'] === '1' || $params['tree'] === 1);
        
        // 获取分页参数
        $page = isset($params['page']) ? intval($params['page']) : 1;
        $limit = isset($params['limit']) ? intval($params['limit']) : 100;
        
        // 提取查询条件
        $conditions = [];
        
        // 筛选条件：ID、名称、描述、标识符
        foreach (['id', 'name', 'description', 'iden'] as $field) {
            if (isset($params[$field]) && $params[$field] !== '') {
                $conditions[$field] = $params[$field];
            }
        }
        
        // 删除状态
        if (isset($params['delete_status'])) {
            $conditions['delete_status'] = $params['delete_status'];
        }
        
        $result = PermissionService::getPermissionList($conditions, $tree, $page, $limit);
        return json($result);
    }
    
    /**
     * 获取权限树
     * @return \think\Response
     */
    public function tree()
    {
        $result = PermissionService::getPermissionTree();
        return json($result);
    }
    
    /**
     * 添加权限
     * @return \think\Response
     */
    public function add()
    {
        $params = request()->param();
        
        $result = PermissionService::addPermission($params);
        return json($result);
    }
    
    /**
     * 更新权限
     * @return \think\Response
     */
    public function update()
    {
        $params = request()->param();
        
        $id = $params['id'] ?? 0;
        if (empty($id)) {
            return json(['code' => 400, 'msg' => '权限ID不能为空']);
        }
        
        $result = PermissionService::updatePermission($id, $params);
        return json($result);
    }
    
    /**
     * 删除权限
     * @return \think\Response
     */
    public function delete()
    {
        $params = request()->param();
        $id = $params['id'] ?? 0;
        
        if (empty($id)) {
            return json(['code' => 400, 'msg' => '权限ID不能为空']);
        }
        
        // 检查是否为物理删除
        $real = isset($params['real']) && ($params['real'] === 'true' || $params['real'] === true || $params['real'] === '1' || $params['real'] === 1);
        
        $result = PermissionService::deletePermission($id, $real);
        return json($result);
    }
    
    /**
     * 恢复已删除的权限
     * @return \think\Response
     */
    public function restore()
    {
        $params = request()->param();
        $id = $params['id'] ?? 0;
        
        if (empty($id)) {
            return json(['code' => 400, 'msg' => '权限ID不能为空']);
        }
        
        $result = PermissionService::restorePermission($id);
        return json($result);
    }
    
    /**
     * 获取父级权限列表
     * @return \think\Response
     */
    public function parents()
    {
        // 获取所有parent_id为0的权限
        $params = ['parent_id' => 0];
        $result = PermissionService::getPermissionList($params);
        return json($result);
    }
    
    /**
     * 获取子权限列表
     * @return \think\Response
     */
    public function children()
    {
        $parentId = request()->param('parent_id');
        if (empty($parentId)) {
            return json(['code' => 400, 'msg' => '父级权限ID不能为空']);
        }
        
        $params = ['parent_id' => $parentId];
        $result = PermissionService::getPermissionList($params);
        return json($result);
    }
    
    /**
     * 获取权限级别
     * @return \think\Response
     */
    public function levels()
    {
        // 获取所有权限
        $permissions = PermissionService::getPermissionList([])['data'];
        
        // 计算各权限级别
        $permissionsWithLevels = PermissionService::getPermissionLevels($permissions);
        
        return json([
            'code' => 200,
            'msg' => '查询成功',
            'data' => $permissionsWithLevels
        ]);
    }
    
    /**
     * 获取权限分类
     * @return \think\Response
     */
    public function categories()
    {
        // 获取所有顶级权限（parent_id = 0）
        $params = ['parent_id' => 0];
        $result = PermissionService::getPermissionList($params);
        return json($result);
    }
    
    /**
     * 获取完整权限树
     * @return \think\Response
     */
    public function fullTree()
    {
        // 获取所有权限，按树形结构返回
        $result = PermissionService::getPermissionTree();
        return json($result);
    }
    
    /**
     * 分配API权限
     * @return \think\Response
     */
    public function assignApis()
    {
        $params = request()->param();
        $permissionId = $params['permission_id'] ?? 0;
        $apiIds = $params['api_ids'] ?? [];
        
        if (empty($permissionId)) {
            return json(['code' => 400, 'msg' => '权限ID不能为空']);
        }
        
        // 确保apiIds是数组
        if (!is_array($apiIds)) {
            if (is_string($apiIds) && !empty($apiIds)) {
                $apiIds = explode(',', $apiIds);
            } else {
                $apiIds = [];
            }
        }
        
        $result = PermissionService::assignApis($permissionId, $apiIds);
        return json($result);
    }
    
    /**
     * 获取权限关联的API列表
     * @return \think\Response
     */
    public function getApis()
    {
        $permissionId = request()->param('permission_id', 0);
        
        if (empty($permissionId)) {
            return json(['code' => 400, 'msg' => '权限ID不能为空']);
        }
        
        $result = PermissionService::getPermissionApis($permissionId);
        return json($result);
    }
} 