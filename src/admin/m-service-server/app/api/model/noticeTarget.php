<?php

namespace app\api\model;

use think\Model;

/**
 * 公告目标关联模型
 * 管理公告与用户/角色的多对多关系
 */
class noticeTarget extends Model
{
    // 设置表名
    protected $name = 'notice_target';

    // 设置主键
    protected $pk = 'id';

    // 自动时间戳
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 类型转换
    protected $type = [
        'id' => 'integer',
        'notice_id' => 'integer',
        'target_type' => 'integer',
        'target_id' => 'integer',
        'read_status' => 'boolean',
        'read_time' => 'datetime'
    ];

    // 关联公告
    public function notice()
    {
        return $this->belongsTo(notice::class, 'notice_id', 'notice_id');
    }

    // 关联用户（当 target_type=1 时）
    public function user()
    {
        return $this->belongsTo(users::class, 'target_id', 'id');
    }

    // 关联角色（当 target_type=2 时）
    public function role()
    {
        return $this->belongsTo(roles::class, 'target_id', 'id');
    }
}
