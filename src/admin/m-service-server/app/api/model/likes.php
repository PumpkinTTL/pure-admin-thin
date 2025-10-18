<?php

namespace app\api\model;

use think\Model;

class likes extends Model
{
    // 表名
    protected $name = 'likes';
    
    // 开启自动时间戳
    protected $autoWriteTimestamp = true;
    
    // 软删除字段
    protected $deleteTime = 'delete_time';
    
    // 关联用户
    public function user()
    {
        return $this->belongsTo('app\api\model\users', 'user_id', 'id')
            ->field('id,username,avatar');
    }
}
