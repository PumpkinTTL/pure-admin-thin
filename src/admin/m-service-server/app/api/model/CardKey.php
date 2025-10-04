<?php

/**
 * 卡密模型
 * 
 * 管理卡密数据的增删改查操作
 * 
 * @author AI Assistant
 * @date 2025-10-01
 */

namespace app\api\model;

use think\Model;

class CardKey extends Model
{
    // 设置表名（不含前缀）
    protected $name = 'card_keys';

    // 设置表前缀
    protected $connection = 'mysql';

    // 自动时间戳类型
    protected $autoWriteTimestamp = false;

    // 定义时间字段名
    protected $createTime = 'create_time';
    protected $updateTime = false;

    // 字段类型转换
    protected $type = [
        'id' => 'integer',
        'status' => 'integer',
        'price' => 'float',
        'membership_duration' => 'integer',  // 兑换后获得的会员时长(分钟)
        'user_id' => 'integer',
        'create_time' => 'datetime',
        'use_time' => 'datetime',
        'available_time' => 'datetime',  // 卡密可兑换截止时间
    ];

    /**
     * 状态常量定义
     */
    const STATUS_UNUSED = 0;    // 未使用
    const STATUS_USED = 1;      // 已使用
    const STATUS_DISABLED = 2;  // 已禁用

    /**
     * 状态文本映射
     */
    public static $statusMap = [
        self::STATUS_UNUSED => '未使用',
        self::STATUS_USED => '已使用',
        self::STATUS_DISABLED => '已禁用',
    ];

    /**
     * 关联使用者（用户表）
     * 
     * @return \think\model\relation\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(users::class, 'user_id', 'id');
    }

    /**
     * 关联使用记录
     * 
     * @return \think\model\relation\HasMany
     */
    public function logs()
    {
        return $this->hasMany(CardKeyLog::class, 'card_key_id', 'id');
    }

    /**
     * 获取状态文本
     * 
     * @param mixed $value
     * @param array $data
     * @return string
     */
    public function getStatusTextAttr($value, $data)
    {
        return self::$statusMap[$data['status']] ?? '未知';
    }

    /**
     * 获取会员时长文本（兑换后获得的会员时长）
     * 
     * @param mixed $value
     * @param array $data
     * @return string
     */
    public function getMembershipDurationTextAttr($value, $data)
    {
        $minutes = $data['membership_duration'] ?? 0;

        if ($minutes == 0) {
            return '永久会员';
        } elseif ($minutes < 60) {
            return $minutes . '分钟';
        } elseif ($minutes < 1440) {
            return floor($minutes / 60) . '小时';
        } else {
            return floor($minutes / 1440) . '天';
        }
    }

    /**
     * 向后兼容：保留旧方法名
     * 
     * @deprecated 请使用 getMembershipDurationTextAttr
     */
    public function getValidTextAttr($value, $data)
    {
        return $this->getMembershipDurationTextAttr($value, $data);
    }

    /**
     * 检查卡密本身是否过期（基于available_time）
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        // 已使用的卡密不算过期
        if ($this->status == self::STATUS_USED) {
            return false;
        }

        // 没有设置可用期限，永久可用
        if (!$this->available_time) {
            return false;
        }

        // 检查是否超过可用期限
        return strtotime($this->available_time) < time();
    }

    /**
     * 获取会员到期时间（基于use_time + membership_duration）
     *
     * @return string|null
     */
    public function getExpireTime(): ?string
    {
        // 未使用或永久会员
        if ($this->status != self::STATUS_USED || $this->membership_duration == 0) {
            return null;
        }

        // 计算会员到期时间
        $expireTimestamp = strtotime($this->use_time) + ($this->membership_duration * 60);
        return date('Y-m-d H:i:s', $expireTimestamp);
    }

    /**
     * 获取剩余会员时间（秒）
     * 
     * @return int|null
     */
    public function getRemainingTime(): ?int
    {
        if ($this->status != self::STATUS_USED || $this->membership_duration == 0) {
            return null;
        }

        $expireTime = strtotime($this->use_time) + ($this->membership_duration * 60);
        $remaining = $expireTime - time();

        return $remaining > 0 ? $remaining : 0;
    }

    /**
     * 搜索器：按类型搜索
     * 
     * @param \think\db\Query $query
     * @param mixed $value
     * @return void
     */
    public function searchTypeAttr($query, $value)
    {
        if ($value) {
            $query->where('type', $value);
        }
    }

    /**
     * 搜索器：按状态搜索
     * 
     * @param \think\db\Query $query
     * @param mixed $value
     * @return void
     */
    public function searchStatusAttr($query, $value)
    {
        if ($value !== '' && $value !== null) {
            $query->where('status', $value);
        }
    }

    /**
     * 搜索器：按卡密码搜索
     * 
     * @param \think\db\Query $query
     * @param mixed $value
     * @return void
     */
    public function searchCodeAttr($query, $value)
    {
        if ($value) {
            $query->where('code', 'like', '%' . $value . '%');
        }
    }

    /**
     * 搜索器：按创建时间范围搜索
     * 
     * @param \think\db\Query $query
     * @param mixed $value
     * @return void
     */
    public function searchCreateTimeAttr($query, $value)
    {
        if (is_array($value) && count($value) == 2) {
            $query->whereBetweenTime('create_time', $value[0], $value[1]);
        }
    }

    /**
     * 获取所有卡密类型列表
     * 
     * @return array
     */
    public static function getTypeList(): array
    {
        return self::field('type')
            ->group('type')
            ->order('create_time', 'desc')
            ->column('type');
    }

    /**
     * 获取卡密列表（带分页）
     * 
     * @param array $params 查询参数
     * @return array
     */
    public static function getList(array $params = []): array
    {
        $page = $params['page'] ?? 1;
        $limit = $params['limit'] ?? 10;

        $query = self::withSearch(['type', 'status', 'code', 'create_time'], $params)
            ->with(['user'])
            ->order('create_time', 'desc');

        $total = $query->count();
        $list = $query->page($page, $limit)->select();

        return [
            'total' => $total,
            'list' => $list,
            'page' => $page,
            'limit' => $limit
        ];
    }

    /**
     * 获取卡密详情（包含使用记录）
     * 
     * @param int $id 卡密ID
     * @return array|null
     */
    public static function getDetail(int $id): ?array
    {
        $cardKey = self::with(['user', 'logs.user'])->find($id);

        if (!$cardKey) {
            return null;
        }

        $data = $cardKey->toArray();
        $data['status_text'] = self::$statusMap[$data['status']] ?? '未知';
        $data['is_expired'] = $cardKey->isExpired();
        $data['expire_time'] = $cardKey->getExpireTime();
        $data['remaining_time'] = $cardKey->getRemainingTime();

        return $data;
    }

    /**
     * 批量删除卡密
     * 
     * @param array $ids 卡密ID数组
     * @return int 删除数量
     */
    public static function batchDelete(array $ids): int
    {
        return self::whereIn('id', $ids)->delete();
    }
}
