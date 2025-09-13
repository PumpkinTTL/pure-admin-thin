<?php

namespace app\api\model;

use think\Model;
use app\api\model\users;
class comments extends Model
{
    // 关联用户（必须指定完整命名空间）
// 关联用户
    public function user()
    {
        return $this->belongsTo(users::class, 'user_id', 'id')
            ->field('id,username,avatar');
    }

    // 无限级回复关联（核心方法）
    public function replies()
    {

        // return $this->hasMany(self::class, 'parent_id')
        // ->with(['user', 'replies']) // 关键点：递归加载
        // ->whereNull('delete_time')
        // ->order('create_time', 'desc');

        return $this->hasMany(self::class, 'parent_id')
        ->with(['user', 'replies' => function($q) {
            $q->with(['user']); // 只加载二级回复
        }])
        ->whereNull('delete_time')
        ->order('create_time', 'desc');
    }
}