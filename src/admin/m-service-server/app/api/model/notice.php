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
        'category_type' => 'integer',
        'publisher_id' => 'integer',
        'status' => 'integer',
        'priority' => 'integer',
        'is_top' => 'boolean',
        'publish_time' => 'datetime',
        'expire_time' => 'datetime',
        'visibility' => 'string'
    ];

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

    // ========================================
    // 关联方法（用于权限控制）
    // ========================================

    // 关联目标用户（通过中间表）
    public function targetUsers()
    {
        return $this->hasMany(noticeTarget::class, 'notice_id', 'notice_id')
            ->where('target_type', 1)  // 1=用户
            ->with('user');
    }

    // 关联目标角色（通过中间表）
    public function targetRoles()
    {
        return $this->hasMany(noticeTarget::class, 'notice_id', 'notice_id')
            ->where('target_type', 2)  // 2=角色
            ->with('role');
    }

    // ========================================
    // 权限验证方法（参考文章模块）
    // ========================================

    /**
     * 检查用户是否有权访问该公告
     * @param int $userId 用户ID
     * @param array $userRoleIds 用户角色ID数组
     * @return bool
     */
    public function canAccessBy($userId, $userRoleIds = [])
    {
        // 1. 公开公告，所有人可见
        if ($this->visibility === 'public') {
            return true;
        }

        // 2. 发布人始终可以访问自己的公告
        if ($this->publisher_id == $userId) {
            return true;
        }

        // 3. 登录可见，检查是否已登录
        if ($this->visibility === 'login_required') {
            return $userId > 0;
        }

        // 4. 指定用户，检查用户是否在目标列表中
        if ($this->visibility === 'specific_users') {
            if ($userId <= 0) {
                return false;
            }
            return noticeTarget::where('notice_id', $this->notice_id)
                ->where('target_type', 1)
                ->where('target_id', $userId)
                ->count() > 0;
        }

        // 5. 指定角色，检查用户角色是否在目标列表中
        if ($this->visibility === 'specific_roles') {
            if (empty($userRoleIds)) {
                return false;
            }
            return noticeTarget::where('notice_id', $this->notice_id)
                ->where('target_type', 2)
                ->whereIn('target_id', $userRoleIds)
                ->count() > 0;
        }

        // 默认不可见
        return false;
    }
}
