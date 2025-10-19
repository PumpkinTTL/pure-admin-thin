<?php

namespace app\api\model;

use think\Model;

class favorites extends Model
{
    // 收藏不使用软删除，直接物理删除
    // use SoftDelete;
    // protected $deleteTime = 'delete_time';

    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 关联用户
    public function user()
    {
        return $this->belongsTo('app\api\model\users', 'user_id', 'id')
            ->field('id,username,avatar');
    }
}
