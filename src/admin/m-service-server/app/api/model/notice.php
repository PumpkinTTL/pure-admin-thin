<?php

namespace app\api\model;

use think\Model;
use think\model\concern\SoftDelete;

class notice extends Model
{
    // 引入软删除特性
    use SoftDelete;
    
    // 软删除字段设置
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = null;
    
    // 设置表名
    protected $name = 'notice';
    
    // 设置主键
    protected $pk = 'notice_id';
    
    // 自动时间戳
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    // 类型转换
    protected $type = [
        'notice_id' => 'integer',
        'notice_type' => 'integer',
        'category_type' => 'integer',
        'publisher_id' => 'integer',
        'status' => 'integer',
        'priority' => 'integer',
        'is_top' => 'boolean',
        'publish_time' => 'datetime',
        'expire_time' => 'datetime'
    ];
    
    // 公告类型转换为可读名称
    public function getNoticeTypeTextAttr($value, $data)
    {
        $types = [
            1 => '全体公告',
            2 => '部分用户公告',
            3 => '个人通知'
        ];
        return $types[$data['notice_type']] ?? '未知类型';
    }
    
    // 公告分类转换为可读名称
    public function getCategoryTypeTextAttr($value, $data)
    {
        $categories = [
            1 => '系统更新',
            2 => '账号安全',
            3 => '活动通知',
            4 => '政策公告',
            5 => '其他'
        ];
        return $categories[$data['category_type']] ?? '未知分类';
    }
    
    // 状态转换为可读名称
    public function getStatusTextAttr($value, $data)
    {
        $statuses = [
            0 => '草稿',
            1 => '已发布',
            2 => '已撤回'
        ];
        return $statuses[$data['status']] ?? '未知状态';
    }
    
    // 优先级转换为可读名称
    public function getPriorityTextAttr($value, $data)
    {
        $priorities = [
            0 => '普通',
            1 => '重要',
            2 => '紧急'
        ];
        return $priorities[$data['priority']] ?? '未知优先级';
    }
    
    // 关联发布人
    public function publisher()
    {
        return $this->belongsTo(users::class, 'publisher_id', 'id')
            ->field(['id', 'username', 'avatar']);
    }
    
    // 获取目标用户IDs数组
    public function getTargetUidsAttr($value, $data)
    {
        if (empty($data['target_uid'])) {
            return [];
        }
        return explode(',', $data['target_uid']);
    }
    
    // 设置目标用户IDs
    public function setTargetUidsAttr($value)
    {
        if (is_array($value)) {
            return implode(',', $value);
        }
        return $value;
    }
} 