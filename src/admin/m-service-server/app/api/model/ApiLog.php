<?php

namespace app\api\model;

use think\Model;

class ApiLog extends Model
{
    // 设置表名
    protected $name = 'api_log';
    
    // 设置表前缀
    protected $prefix = 'bl_';
    
    // 开启自动写入时间戳
    protected $autoWriteTimestamp = true;
    
    // 设置时间字段格式
    protected $dateFormat = 'Y-m-d H:i:s';
    
    // 定义时间字段名
    protected $createTime = 'create_time';
    
    // 不需要更新时间
    protected $updateTime = false;
    
    // 允许写入的字段
    protected $field = [
        'id',
        'user_id',
        'api_key',
        'device_fingerprint',
        'http_method',
        'url_path',
        'request_params',
        'ip',
        'user_agent',
        'status_code',
        'error_code',
        'execution_time',
        'create_time'
    ];
    
    // 设置字段类型
    protected $type = [
        'id' => 'integer',
        'user_id' => 'integer',
        'execution_time' => 'integer',
        'status_code' => 'integer',
    ];
    
    // 用户关联
    public function user()
    {
        return $this->belongsTo(Users::class, 'user_id', 'id');
    }
    
    // 请求参数获取器（JSON解码）
    public function getRequestParamsAttr($value)
    {
        return $value ? json_decode($value, true) : null;
    }
    
    // 请求参数修改器（JSON编码）
    public function setRequestParamsAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }
} 