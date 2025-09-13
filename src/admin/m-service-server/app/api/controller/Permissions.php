<?php

namespace app\api\controller;

use app\api\services\PermissionService;
use app\BaseController;
use think\Request;

class Permissions extends BaseController
{
    /**
     * 获取权限列表
     * @param Request $request
     * @return \think\Response
     */
    public function index(Request $request)
    {
        // 获取请求参数
        $params = $request->param();
        
        // 是否返回树形结构
        $tree = isset($params['tree']) && $params['tree'] === 'true';
        
        // 获取分页参数
        $page = isset($params['page']) ? intval($params['page']) : 1;
        $limit = isset($params['limit']) ? intval($params['limit']) : 100;
        
        // 构建查询条件
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
        
        // 调用服务获取权限列表
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
     * @param Request $request
     * @return \think\Response
     */
    public function add(Request $request)
    {
        $data = $request->post();
        $result = PermissionService::addPermission($data);
        return json($result);
    }
    
    /**
     * 更新权限
     * @param Request $request
     * @return \think\Response
     */
    public function update(Request $request)
    {
        $data = $request->put();
        $id = isset($data['id']) ? intval($data['id']) : 0;
        
        if ($id <= 0) {
            return json(['code' => 400, 'msg' => '缺少必要参数ID']);
        }
        
        $result = PermissionService::updatePermission($id, $data);
        return json($result);
    }
    
    /**
     * 删除权限
     * @param Request $request
     * @return \think\Response
     */
    public function delete(Request $request)
    {
        $params = $request->param();
        $id = isset($params['id']) ? intval($params['id']) : 0;
        $real = isset($params['real']) && ($params['real'] === 'true' || $params['real'] === '1');
        
        if ($id <= 0) {
            return json(['code' => 400, 'msg' => '缺少必要参数ID']);
        }
        
        $result = PermissionService::deletePermission($id, $real);
        return json($result);
    }
    
    /**
     * 恢复已删除的权限
     * @param Request $request
     * @return \think\Response
     */
    public function restore(Request $request)
    {
        $params = $request->param();
        $id = isset($params['id']) ? intval($params['id']) : 0;
        
        if ($id <= 0) {
            return json(['code' => 400, 'msg' => '缺少必要参数ID']);
        }
        
        $result = PermissionService::restorePermission($id);
        return json($result);
    }
} 