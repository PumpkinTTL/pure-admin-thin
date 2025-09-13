<?php

namespace app\api\model;

use think\Model;
use think\model\concern\SoftDelete;

class category extends Model
{
    // 引入软删除特性
    use SoftDelete;

    // 设置数据表名称
    protected $table = 'bl_category';

    // 软删除字段设置
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = null;

    // 设置时间戳格式为日期时间格式
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 分类属于的用户
    public function author()
    {
        return $this->belongsTo(users::class, 'user_id', 'id');
    }

    // 父分类关联
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id', 'id');
    }

    // 子分类关联
    public function children()
    {
        return $this->hasMany(self::class, 'parent_id', 'id');
    }

    // 获取分类类型文本
    public function getCategoryTypeTextAttr($value, $data)
    {
        return $data['parent_id'] == 0 ? '大类别' : '标签';
    }

    // status访问器：转换为布尔值
    public function getStatusAttr($value)
    {
        return $value ? true : false;
    }

    // status修改器：处理前端传来的布尔值
    public function setStatusAttr($value)
    {
        return $value ? 1 : 0;
    }
}