<?php

namespace app\api\services;

use think\Collection;
use app\api\model\Menus as MenusModel;

class MenuServices
{
    /**
     * 获取树形菜单结构
     * @param array $conditions 查询条件
     * @param bool $treeMode 是否返回树形结构
     * @return Collection
     */
    public static function selectMenuByMap(array $conditions = [], bool $treeMode = true)
    {
        // 构建查询条件
        $query = MenusModel::with($treeMode ? ['children'] : []);

        // ID精确匹配
        if (!empty($conditions['id'])) {
            $query->where('id', '=', $conditions['id']);
        }

        // 名称模糊搜索
        if (!empty($conditions['name'])) {
            $query->where('name', 'like', '%' . $conditions['name'] . '%');
        }

        // 路径模糊搜索
        if (!empty($conditions['path'])) {
            $query->where('path', 'like', '%' . $conditions['path'] . '%');
        }

        // 按rank升序，update_time降序
        $query->order('rank', 'asc')->order('update_time', 'desc');

        // 根据模式返回不同结构
        return $treeMode
            ? $query->where('parent_id', 0)->select() // 树形只查顶级节点
            : $query->select(); // 扁平结构查全部
    }

    /**
     * 获取完整的菜单树形结构
     * @return Collection
     */
    public static function getMenuTree()
    {
        return MenusModel::with(['children'])
            ->where('parent_id', -1)
            ->select();
    }

}