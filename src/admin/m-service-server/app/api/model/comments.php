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
                    // 第三层回复，加载用户信息和点赞统计
                    $q2->with(['user'])->withCount(['likes']);
                }])->withCount(['likes']);
            }])
            ->withCount(['likes'])
            ->whereNull('delete_time')
            ->order('create_time', 'desc');
    }

    // 简单子评论关联（不递归，用于懒加载）
    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')
            ->with(['user'])
            ->withCount(['likes'])
            ->whereNull('delete_time')
            ->order('create_time', 'desc');
    }

    // 点赞关联关系
    public function likes()
    {
        return $this->hasMany(likes::class, 'target_id')
            ->where('target_type', 'comment');
    }

    // 获取点赞数（动态属性）
    public function getLikeCountAttr()
    {
        return $this->likes()->count();
    }

    // 检查是否被指定用户点赞（动态方法）
    public function isLikedBy($userId)
    {
        if (!$userId) return false;
        return $this->likes()
                ->where('user_id', $userId)
                ->count() > 0;
    }
}