<?php

namespace app\api\model;

use think\Model;

class emailReceiver extends Model
{
    // 设置表名
    protected $name = 'email_receivers';

    // 设置主键
    protected $pk = 'id';

    // 自动时间戳
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'create_time';
    protected $updateTime = false; // 接收者记录不需要更新时间

    // 类型转换
    protected $type = [
        'id' => 'integer',
        'record_id' => 'integer',
        'user_id' => 'integer',
        'status' => 'integer',
        'send_time' => 'datetime',
        'create_time' => 'datetime'
    ];

    /**
     * 关联邮件记录
     */
    public function emailRecord()
    {
        return $this->belongsTo(emailRecord::class, 'record_id', 'id');
    }

    /**
     * 关联用户(可能为空)
     */
    public function user()
    {
        return $this->belongsTo(users::class, 'user_id', 'id')
            ->bind(['username' => 'username']);
    }

    /**
     * 接收者类型文本转换
     */
    public function getReceiverTypeTextAttr($value, $data)
    {
        // 通过user_id是否为空判断
        return empty($data['user_id']) ? '外部邮箱' : '系统用户';
    }

    /**
     * 发送状态文本转换
     */
    public function getStatusTextAttr($value, $data)
    {
        $status = [
            0 => '待发送',
            1 => '发送成功',
            2 => '发送失败'
        ];
        return $status[$data['status']] ?? '未知';
    }

    /**
     * 搜索器 - 邮件记录ID
     */
    public function searchRecordIdAttr($query, $value)
    {
        if ($value) {
            $query->where('record_id', $value);
        }
    }

    /**
     * 搜索器 - 用户ID
     */
    public function searchUserIdAttr($query, $value)
    {
        if ($value) {
            $query->where('user_id', $value);
        }
    }

    /**
     * 搜索器 - 邮箱地址
     */
    public function searchEmailAttr($query, $value)
    {
        if ($value) {
            $query->whereLike('email', '%' . $value . '%');
        }
    }

    /**
     * 搜索器 - 发送状态
     */
    public function searchStatusAttr($query, $value)
    {
        if ($value !== '' && $value !== null) {
            $query->where('status', $value);
        }
    }
}
