<?php

namespace app\api\model;

use think\Model;
use think\model\concern\SoftDelete;

class favorites extends Model
{
    use SoftDelete;

    protected $deleteTime = 'delete_time';

    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 关联文章
    public function article()
    {
        return $this->belongsTo('app\api\model\article', 'article_id');
    }

    // 关联用户
    public function user()
    {
        return $this->belongsTo('app\common\model\User', 'user_id');
    }
}