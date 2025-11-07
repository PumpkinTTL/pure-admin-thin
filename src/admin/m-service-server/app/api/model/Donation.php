<?php

namespace app\api\model;

use think\Model;
use think\model\concern\SoftDelete;

class Donation extends Model
{
    // 引入软删除特性
    use SoftDelete;

    // 设置表名（不含前缀）
    protected $name = 'donations';

    // 设置表前缀
    protected $connection = 'mysql';

    // 软删除字段设置
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = null;

    // 自动时间戳类型
    protected $autoWriteTimestamp = true;

    // 定义时间字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 字段类型转换
    protected $type = [
        'id' => 'integer',
        'user_id' => 'integer',
        'amount' => 'float',
        'status' => 'integer',
        'is_anonymous' => 'integer',
        'is_public' => 'integer',
        'card_key_id' => 'integer',
        'card_key_value' => 'float',
        'confirmation_count' => 'integer',
        'create_time' => 'datetime',
        'update_time' => 'datetime',
        'payment_time' => 'datetime',
        'confirm_time' => 'datetime',
        'delete_time' => 'datetime',
    ];

    /**
     * 捐赠渠道常量定义
     */
    const CHANNEL_WECHAT = 'wechat';      // 微信
    const CHANNEL_ALIPAY = 'alipay';      // 支付宝
    const CHANNEL_CRYPTO = 'crypto';      // 加密货币
    const CHANNEL_CARDKEY = 'cardkey';    // 卡密兑换

    /**
     * 状态常量定义
     */
    const STATUS_PENDING = 0;     // 待确认
    const STATUS_CONFIRMED = 1;   // 已确认
    const STATUS_COMPLETED = 2;   // 已完成
    const STATUS_CANCELLED = 3;   // 已取消

    /**
     * 渠道文本映射
     */
    public static $channelMap = [
        self::CHANNEL_WECHAT => '微信支付',
        self::CHANNEL_ALIPAY => '支付宝',
        self::CHANNEL_CRYPTO => '加密货币',
        self::CHANNEL_CARDKEY => '卡密兑换',
    ];

    /**
     * 状态文本映射
     */
    public static $statusMap = [
        self::STATUS_PENDING => '待确认',
        self::STATUS_CONFIRMED => '已确认',
        self::STATUS_COMPLETED => '已完成',
        self::STATUS_CANCELLED => '已取消',
    ];

    /**
     * 加密货币类型映射
     */
    public static $cryptoTypeMap = [
        'USDT' => 'USDT (泰达币)',
        'BTC' => 'BTC (比特币)',
        'ETH' => 'ETH (以太坊)',
        'TRX' => 'TRX (波场)',
    ];

    /**
     * 区块链网络映射
     */
    public static $cryptoNetworkMap = [
        'TRC20' => 'TRC20 (波场网络)',
        'ERC20' => 'ERC20 (以太坊网络)',
        'BTC' => 'BTC (比特币网络)',
        'BSC' => 'BSC (币安智能链)',
    ];

    /**
     * 关联用户
     *
     * @return \think\model\relation\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(users::class, 'user_id', 'id')
            ->field('id,nickname,avatar');  // 只查询3个字段，保护隐私
    }

    /**
     * 关联卡密
     * 
     * @return \think\model\relation\BelongsTo
     */
    public function cardKey()
    {
        return $this->belongsTo(CardKey::class, 'card_key_id', 'id')
            ->field('id,card_key,type_id,status')
            ->bind([
                'card_key_status' => 'status'
            ]);
    }

    /**
     * 获取捐赠列表
     * 
     * @param array $params 查询参数
     * @return array
     */
    public static function getList(array $params): array
    {
        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 100;

        // 构建查询
        $query = self::with(['user', 'cardKey']);

        // 搜索条件
        if (!empty($params['donation_no'])) {
            $query->where('donation_no', 'like', '%' . $params['donation_no'] . '%');
        }

        if (!empty($params['user_id'])) {
            $query->where('user_id', $params['user_id']);
        }

        if (!empty($params['donor_name'])) {
            $query->where('donor_name', 'like', '%' . $params['donor_name'] . '%');
        }

        if (isset($params['channel']) && $params['channel'] !== '') {
            $query->where('channel', $params['channel']);
        }

        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('status', $params['status']);
        }

        if (isset($params['is_anonymous']) && $params['is_anonymous'] !== '') {
            $query->where('is_anonymous', $params['is_anonymous']);
        }

        // 金额范围筛选
        if (!empty($params['min_amount'])) {
            $query->where('amount', '>=', $params['min_amount']);
        }

        if (!empty($params['max_amount'])) {
            $query->where('amount', '<=', $params['max_amount']);
        }

        // 时间范围筛选
        if (!empty($params['start_time'])) {
            $query->where('create_time', '>=', $params['start_time']);
        }

        if (!empty($params['end_time'])) {
            $query->where('create_time', '<=', $params['end_time']);
        }

        // 软删除筛选
        $queryDeleted = $params['query_deleted'] ?? 'not_deleted';
        if ($queryDeleted === 'only_deleted') {
            $query->onlyTrashed();
        } elseif ($queryDeleted === 'with_deleted') {
            $query->withTrashed();
        }

        // 排序
        $orderField = $params['order_field'] ?? 'create_time';
        $orderType = $params['order_type'] ?? 'desc';
        $query->order($orderField, $orderType);

        // 分页查询
        $result = $query->paginate([
            'list_rows' => $pageSize,
            'page' => $page,
        ]);

        // 处理数据
        $list = $result->items();
        foreach ($list as &$item) {
            $item = self::formatDonation($item);
        }

        return [
            'list' => $list,
            'total' => $result->total(),
            'page' => $page,
            'page_size' => $pageSize,
        ];
    }

    /**
     * 获取捐赠详情
     * 
     * @param int $id 捐赠ID
     * @return array|null
     */
    public static function getDetail(int $id): ?array
    {
        $donation = self::with(['user', 'cardKey'])->find($id);

        if (!$donation) {
            return null;
        }

        return self::formatDonation($donation);
    }

    /**
     * 格式化捐赠数据
     * 
     * @param mixed $donation 捐赠数据
     * @return array
     */
    private static function formatDonation($donation): array
    {
        $data = $donation->toArray();

        // 添加渠道文本
        $data['channel_text'] = self::$channelMap[$data['channel']] ?? $data['channel'];

        // 添加状态文本
        $data['status_text'] = self::$statusMap[$data['status']] ?? '未知';

        // 添加加密货币类型文本
        if (!empty($data['crypto_type'])) {
            $data['crypto_type_text'] = self::$cryptoTypeMap[$data['crypto_type']] ?? $data['crypto_type'];
        }

        // 添加区块链网络文本
        if (!empty($data['crypto_network'])) {
            $data['crypto_network_text'] = self::$cryptoNetworkMap[$data['crypto_network']] ?? $data['crypto_network'];
        }

        // 格式化金额
        if (isset($data['amount'])) {
            $data['amount_formatted'] = number_format($data['amount'], 2);
        }

        if (isset($data['card_key_value'])) {
            $data['card_key_value_formatted'] = number_format($data['card_key_value'], 2);
        }

        return $data;
    }

    /**
     * 批量删除
     * 
     * @param array $ids ID数组
     * @return int 删除数量
     */
    public static function batchDelete(array $ids): int
    {
        return self::destroy($ids);
    }

    /**
     * 生成捐赠单号
     * 
     * @return string
     */
    public static function generateDonationNo(): string
    {
        // 格式: DON + 年月日 + 4位随机数
        $prefix = 'DON' . date('Ymd');
        $random = str_pad((string)mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
        $donationNo = $prefix . $random;

        // 检查是否重复
        $exists = self::where('donation_no', $donationNo)->find();
        if ($exists) {
            // 如果重复，递归生成新的
            return self::generateDonationNo();
        }

        return $donationNo;
    }
}
