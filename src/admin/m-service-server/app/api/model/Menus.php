<?php

namespace app\api\model;

use think\Model;

class Menus extends Model
{
    // 设置JSON自动转换字段
    protected $json = ['meta'];

    // 设置JSON字段的数组类型
    protected $jsonAssoc = true;

    // 定义子路由关联（已优化）
    public function children()
    {
        return $this->hasMany(self::class, 'parent_id', 'id')
            ->order('rank', 'asc')
            ->visible(['id', 'name', 'path', 'component', 'meta', 'rank']); // 控制返回字段
    }

    // 添加获取器处理meta字段（可选）
    public function getMetaAttr($value)
    {
        return is_array($value) ? $value : json_decode($value, true);
    }
}