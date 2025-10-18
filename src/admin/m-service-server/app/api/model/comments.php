<?php

namespace app\api\model;

use think\Model;
use app\api\model\users;
class comments extends Model
{
    // 表名
    protected $name = 'comments';
    
    // 开启自动时间戳
    protected $autoWriteTimestamp = true;
    
    // 软删除字段
    protected $deleteTime = 'delete_time';
    
    // 关联用户（必须指定完整命名空间）
    public function user()
    {
        return $this->belongsTo(users::class, 'user_id', 'id')
            ->field('id,username,avatar');
    }

    // 无限级回复关联（核心方法）
    // 支持3层递归：顶级评论 -> 一级回复 -> 二级回复
    public function replies()
    {
        return $this->hasMany(self::class, 'parent_id')
            ->with(['user', 'replies' => function($q) {
                // 第二层回复，再加载一层子回复
                $q->with(['user', 'replies' => function($q2) {
                    // 第三层回复，只加载用户信息，不再继续递归
                    $q2->with(['user']);
                }]);
            }])
            ->whereNull('delete_time')
            ->order('create_time', 'desc');
    }

    // 简单子评论关联（不递归，用于懒加载）
    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')
            ->with(['user'])
            ->whereNull('delete_time')
            ->order('create_time', 'desc');
    }
}