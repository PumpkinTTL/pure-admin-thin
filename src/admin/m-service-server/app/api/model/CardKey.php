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
        'type_id' => 'integer',
        'status' => 'integer',
        'user_id' => 'integer',
        'create_time' => 'datetime',
        'use_time' => 'datetime',
        'expire_time' => 'datetime',  // 卡密本身的过期时间
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
     * 关联卡密类型
     * 
     * @return \think\model\relation\BelongsTo
     */
    public function cardType()
    {
        return $this->belongsTo(CardType::class, 'type_id', 'id');
    }

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
     * 获取价格（从type_id关联读取）
     * 
     * @return float|null
     */
    public function getPrice(): ?float
    {
        if (!$this->cardType) {
            return null;
        }
        return $this->cardType->price;
    }

    /**
     * 获取会员时长（从type_id关联读取）
     * 
     * @return int|null
     */
    public function getMembershipDuration(): ?int
    {
        if (!$this->cardType) {
            return null;
        }
        return $this->cardType->membership_duration;
    }

    /**
     * 获取会员时长文本（从type_id关联读取）
     * 
     * @return string
     */
    public function getMembershipDurationText(): string
    {
        $minutes = $this->getMembershipDuration();

        if ($minutes === null) {
            return '不需要会员时长';
        }

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
     * 检查卡密本身是否过期（基于expire_time或类型表的available_days）
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        // 已使用的卡密不算过期
        if ($this->status == self::STATUS_USED) {
            return false;
        }

        // 优先检查卡密本身的expire_time
        if ($this->expire_time) {
            return strtotime($this->expire_time) < time();
        }

        // 其次检查类型表的available_days
        if ($this->cardType && $this->cardType->available_days !== null) {
            $expireTime = strtotime($this->create_time) + ($this->cardType->available_days * 86400);
            return $expireTime < time();
        }

        // 都没有设置，永久可用
        return false;
    }

    /**
     * 获取会员到期时间（基于use_time + 类型表的membership_duration）
     *
     * @return string|null
     */
    public function getMemberExpireTime(): ?string
    {
        // 未使用
        if ($this->status != self::STATUS_USED) {
            return null;
        }

        $duration = $this->getMembershipDuration();

        // 不需要会员时长或永久会员
        if ($duration === null || $duration == 0) {
            return null;
        }

        // 计算会员到期时间
        $expireTimestamp = strtotime($this->use_time) + ($duration * 60);
        return date('Y-m-d H:i:s', $expireTimestamp);
    }

    /**
     * 获取剩余会员时间（秒）
     * 
     * @return int|null
     */
    public function getRemainingTime(): ?int
    {
        if ($this->status != self::STATUS_USED) {
            return null;
        }

        $duration = $this->getMembershipDuration();

        if ($duration === null || $duration == 0) {
            return null;
        }

        $expireTime = strtotime($this->use_time) + ($duration * 60);
        $remaining = $expireTime - time();

        return $remaining > 0 ? $remaining : 0;
    }

    /**
     * 搜索器：按类型ID搜索
     * 
     * @param \think\db\Query $query
     * @param mixed $value
     * @return void
     */
    public function searchTypeIdAttr($query, $value)
    {
        if ($value) {
            $query->where('type_id', $value);
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
    public function searchCardKeyAttr($query, $value)
    {
        if ($value) {
            $query->where('card_key', 'like', '%' . $value . '%');
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
     * 获取所有卡密类型ID列表（已废弃，请使用CardType::getEnabledTypes()）
     * 
     * @deprecated
     * @return array
     */
    public static function getTypeList(): array
    {
        return self::field('type_id')
            ->group('type_id')
            ->order('create_time', 'desc')
            ->column('type_id');
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

        $query = self::withSearch(['type_id', 'status', 'card_key', 'create_time'], $params)
            ->with(['cardType', 'user'])
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
        $cardKey = self::with(['cardType', 'user', 'logs.user'])->find($id);

        if (!$cardKey) {
            return null;
        }

        $data = $cardKey->toArray();
        $data['status_text'] = self::$statusMap[$data['status']] ?? '未知';
        $data['is_expired'] = $cardKey->isExpired();
        $data['member_expire_time'] = $cardKey->getMemberExpireTime();
        $data['remaining_time'] = $cardKey->getRemainingTime();
        $data['price'] = $cardKey->getPrice();
        $data['membership_duration'] = $cardKey->getMembershipDuration();
        $data['membership_duration_text'] = $cardKey->getMembershipDurationText();

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
