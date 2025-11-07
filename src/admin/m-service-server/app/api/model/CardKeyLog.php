<?php

/**
 * 卡密使用记录模型
 * 
 * 管理卡密使用日志的增删改查操作
 * 
 * @author AI Assistant
 * @date 2025-10-01
 */

namespace app\api\model;

use think\Model;

class CardKeyLog extends Model
{
    // 设置表名（不含前缀）
    protected $name = 'card_key_logs';

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
        'card_key_id' => 'integer',
        'user_id' => 'integer',
        'related_id' => 'integer',
        'create_time' => 'datetime',
        'expire_time' => 'datetime',
    ];

    /**
     * 操作类型常量定义
     */
    const ACTION_USE = '使用';
    const ACTION_VERIFY = '验证';
    const ACTION_DISABLE = '禁用';
    const ACTION_ENABLE = '启用';

    /**
     * 使用类型常量定义
     */
    const USE_TYPE_MEMBERSHIP = 'membership';  // 兑换会员
    const USE_TYPE_DONATION = 'donation';      // 捐赠
    const USE_TYPE_REGISTER = 'register';      // 注册邀请
    const USE_TYPE_PRODUCT = 'product';        // 商品兑换
    const USE_TYPE_POINTS = 'points';          // 积分兑换
    const USE_TYPE_OTHER = 'other';            // 其他

    /**
     * 关联类型常量定义
     */
    const RELATED_TYPE_DONATION = 'donation';  // 捐赠记录
    const RELATED_TYPE_ORDER = 'order';        // 订单
    const RELATED_TYPE_USER = 'user';          // 用户
    const RELATED_TYPE_POINTS = 'points';      // 积分记录

    /**
     * 关联卡密
     * 
     * @return \think\model\relation\BelongsTo
     */
    public function cardKey()
    {
        return $this->belongsTo(CardKey::class, 'card_key_id', 'id');
    }

    /**
     * 关联用户
     * 
     * @return \think\model\relation\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(users::class, 'user_id', 'id');
    }

    /**
     * 搜索器：按卡密ID搜索
     * 
     * @param \think\db\Query $query
     * @param mixed $value
     * @return void
     */
    public function searchCardKeyIdAttr($query, $value)
    {
        if ($value) {
            $query->where('card_key_id', $value);
        }
    }

    /**
     * 搜索器：按用户ID搜索
     * 
     * @param \think\db\Query $query
     * @param mixed $value
     * @return void
     */
    public function searchUserIdAttr($query, $value)
    {
        if ($value) {
            $query->where('user_id', $value);
        }
    }

    /**
     * 搜索器：按操作类型搜索
     * 
     * @param \think\db\Query $query
     * @param mixed $value
     * @return void
     */
    public function searchActionAttr($query, $value)
    {
        if ($value) {
            $query->where('action', $value);
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
     * 记录卡密使用日志
     *
     * @param int $cardKeyId 卡密ID
     * @param int $userId 用户ID
     * @param string $action 操作类型
     * @param array $extra 额外信息
     * @return CardKeyLog|null
     */
    public static function addLog(int $cardKeyId, int $userId, string $action, array $extra = []): ?self
    {
        try {
            $data = [
                'card_key_id' => $cardKeyId,
                'user_id' => $userId,
                'action' => $action,
                'use_type' => $extra['use_type'] ?? self::USE_TYPE_MEMBERSHIP,
                'related_id' => $extra['related_id'] ?? null,
                'related_type' => $extra['related_type'] ?? null,
                'expire_time' => $extra['expire_time'] ?? null,
                'ip' => $extra['ip'] ?? request()->ip(),
                'user_agent' => $extra['user_agent'] ?? request()->header('user-agent'),
                'create_time' => date('Y-m-d H:i:s'),
                'remark' => $extra['remark'] ?? ''
            ];

            return self::create($data);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 获取卡密的使用记录列表
     * 
     * @param int $cardKeyId 卡密ID
     * @param int $page 页码
     * @param int $limit 每页数量
     * @return array
     */
    public static function getLogsByCardKey(int $cardKeyId, int $page = 1, int $limit = 10): array
    {
        $query = self::where('card_key_id', $cardKeyId)
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
     * 获取用户的操作记录列表
     * 
     * @param int $userId 用户ID
     * @param int $page 页码
     * @param int $limit 每页数量
     * @return array
     */
    public static function getLogsByUser(int $userId, int $page = 1, int $limit = 10): array
    {
        $query = self::where('user_id', $userId)
            ->with(['cardKey'])
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
     * 获取日志列表（带分页和筛选）
     * 
     * @param array $params 查询参数
     * @return array
     */
    public static function getList(array $params = []): array
    {
        $page = $params['page'] ?? 1;
        $limit = $params['limit'] ?? 10;

        $query = self::withSearch(['card_key_id', 'user_id', 'action', 'create_time'], $params)
            ->with(['cardKey', 'user'])
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
     * 获取操作统计
     * 
     * @param array $params 筛选参数
     * @return array
     */
    public static function getActionStats(array $params = []): array
    {
        $query = self::field('action, COUNT(*) as count')
            ->group('action');

        // 时间范围筛选
        if (!empty($params['start_time'])) {
            $query->where('create_time', '>=', $params['start_time']);
        }
        if (!empty($params['end_time'])) {
            $query->where('create_time', '<=', $params['end_time']);
        }

        return $query->select()->toArray();
    }

    /**
     * 清理过期日志
     * 
     * @param int $days 保留天数
     * @return int 删除数量
     */
    public static function cleanExpiredLogs(int $days = 90): int
    {
        $expireDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        return self::where('create_time', '<', $expireDate)->delete();
    }
}
