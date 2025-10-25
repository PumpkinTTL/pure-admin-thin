<?php

namespace app\api\model;

use think\Model;
use think\model\concern\SoftDelete;

class emailRecord extends Model
{
    // 引入软删除特性
    use SoftDelete;

    // 软删除字段设置
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = null;

    // 设置表名
    protected $name = 'email_records';

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
        'sender_id' => 'integer',
        'receiver_type' => 'integer',
        'total_count' => 'integer',
        'success_count' => 'integer',
        'failed_count' => 'integer',
        'status' => 'integer',
        'send_time' => 'datetime',
        'create_time' => 'datetime',
        'update_time' => 'datetime',
        'delete_time' => 'datetime'
    ];

    /**
     * 关联发送者用户
     */
    public function sender()
    {
        return $this->belongsTo(users::class, 'sender_id', 'id')
            ->bind(['sender_name' => 'username', 'sender_email' => 'email']);
    }

    /**
     * 关联公告
     */
    public function notice()
    {
        return $this->belongsTo(notice::class, 'notice_id', 'notice_id')
            ->bind(['notice_title' => 'title']);
    }

    /**
     * 关联接收者列表
     */
    public function receivers()
    {
        return $this->hasMany(emailReceiver::class, 'record_id', 'id');
    }

    /**
     * 接收方式文本转换
     */
    public function getReceiverTypeTextAttr($value, $data)
    {
        $types = [
            1 => '全部用户',
            2 => '指定多个用户',
            3 => '单个用户',
            4 => '指定邮箱'
        ];
        return $types[$data['receiver_type']] ?? '未知';
    }

    /**
     * 发送状态文本转换
     */
    public function getStatusTextAttr($value, $data)
    {
        $status = [
            0 => '待发送',
            1 => '发送中',
            2 => '发送完成',
            3 => '部分失败',
            4 => '全部失败'
        ];
        return $status[$data['status']] ?? '未知';
    }

    /**
     * 成功率计算
     */
    public function getSuccessRateAttr($value, $data)
    {
        if ($data['total_count'] == 0) {
            return 0;
        }
        return round(($data['success_count'] / $data['total_count']) * 100, 2);
    }

    /**
     * 搜索器 - 标题
     */
    public function searchTitleAttr($query, $value)
    {
        if ($value) {
            $query->whereLike('title', '%' . $value . '%');
        }
    }

    /**
     * 搜索器 - 发送者
     */
    public function searchSenderIdAttr($query, $value)
    {
        if ($value) {
            $query->where('sender_id', $value);
        }
    }

    /**
     * 搜索器 - 状态
     */
    public function searchStatusAttr($query, $value)
    {
        if ($value !== '' && $value !== null) {
            $query->where('status', $value);
        }
    }

    /**
     * 搜索器 - 接收方式
     */
    public function searchReceiverTypeAttr($query, $value)
    {
        if ($value !== '' && $value !== null) {
            $query->where('receiver_type', $value);
        }
    }

    /**
     * 搜索器 - 公告ID
     */
    public function searchNoticeIdAttr($query, $value)
    {
        if ($value) {
            $query->where('notice_id', $value);
        }
    }

    /**
     * 搜索器 - 时间范围
     */
    public function searchCreateTimeAttr($query, $value)
    {
        if (is_array($value) && count($value) == 2) {
            $query->whereBetweenTime('create_time', $value[0], $value[1]);
        }
    }
}
