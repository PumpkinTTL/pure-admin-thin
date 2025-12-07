<?php

namespace app\api\model;

use think\Model;

class Api extends Model
{
    // 设置表名
    protected $name = 'api';
    
    // 设置主键
    protected $pk = 'id';
    
    // 关闭自动递增
    protected $isAutoIncrement = false;
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    // 允许批量赋值的字段
    protected $allowField = ['id', 'version', 'method', 'model', 'path', 'full_path', 'description', 'status', 'file_hash', 'check_mode', 'module', 'required_permission'];
    
    // 字段默认值
    protected $insert = [
        'check_mode' => 'manual',  // 默认使用手动模式
        'status' => self::STATUS_OPEN  // 默认开放状态
    ];
    
    // 状态常量
    const STATUS_MAINTENANCE = 0;  // 维护中
    const STATUS_OPEN = 1;         // 开放
    const STATUS_CLOSED = 2;       // 关闭
    
    /**
     * 状态描述映射
     * @var array
     */
    public static $statusMap = [
        self::STATUS_MAINTENANCE => '维护中',
        self::STATUS_OPEN => '开放',
        self::STATUS_CLOSED => '关闭'
    ];
    
    /**
     * 获取所有状态选项
     * @return array
     */
    public static function getStatusOptions()
    {
        $options = [];
        foreach (self::$statusMap as $value => $text) {
            $options[] = [
                'value' => $value,
                'text' => $text
            ];
        }
        return $options;
    }
    
    /**
     * 与权限的多对多关联
     * @return \think\model\relation\BelongsToMany
     */
    public function permissions()
    {
        return $this->belongsToMany(permissions::class, permissionsApi::class, 'api_id', 'permission_id');
    }
}