<?php

namespace app\api\model;

use think\Model;
use think\model\Pivot;

class permissionsApi extends Pivot
{
    // 设置表名（不包含前缀，ThinkPHP会自动添加）
    protected $name = 'permission_api';

    // 设置主键
    protected $pk = 'id';

    // 开启自动递增
    protected $isAutoIncrement = true;

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 设置字段信息
    protected $schema = [
        'id'           => 'int',
        'api_id'       => 'int',
        'permission_id' => 'int',
        'create_time'  => 'datetime',
        'update_time'  => 'datetime'
    ];

    // 允许批量赋值的字段（不包含id，让数据库自动生成）
    protected $field = ['api_id', 'permission_id', 'create_time', 'update_time'];
}