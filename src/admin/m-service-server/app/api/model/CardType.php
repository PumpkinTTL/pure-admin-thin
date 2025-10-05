<?php

/**
 * 卡密类型模型
 * 
 * 管理卡密类型的增删改查操作
 * 
 * @author AI Assistant
 * @date 2025-10-04
 */

namespace app\api\model;

use think\Model;

class CardType extends Model
{
    // 设置表名（不含前缀）
    protected $name = 'card_types';

    // 设置表前缀
    protected $connection = 'mysql';

    // 自动时间戳类型
    protected $autoWriteTimestamp = true;

    // 定义时间字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 字段类型转换
    protected $type = [
        'id' => 'integer',
        'membership_duration' => 'integer',
        'price' => 'float',
        'available_days' => 'integer',
        'sort_order' => 'integer',
        'status' => 'integer',
        'create_time' => 'datetime',
        'update_time' => 'datetime',
    ];

    /**
     * 状态常量定义
     */
    const STATUS_DISABLED = 0;  // 停用
    const STATUS_ENABLED = 1;   // 启用

    /**
     * 状态文本映射
     */
    public static $statusMap = [
        self::STATUS_DISABLED => '停用',
        self::STATUS_ENABLED => '启用',
    ];

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
     * 判断是否需要会员时长
     * 
     * @return bool
     */
    public function needMembershipDuration(): bool
    {
        return $this->membership_duration !== null;
    }

    /**
     * 判断是否需要价格
     * 
     * @return bool
     */
    public function needPrice(): bool
    {
        return $this->price !== null;
    }

    /**
     * 判断是否有可用期限
     * 
     * @return bool
     */
    public function hasAvailableDays(): bool
    {
        return $this->available_days !== null;
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
     * 搜索器：按类型名称搜索
     * 
     * @param \think\db\Query $query
     * @param mixed $value
     * @return void
     */
    public function searchTypeNameAttr($query, $value)
    {
        if ($value) {
            $query->where('type_name', 'like', '%' . $value . '%');
        }
    }

    /**
     * 获取类型列表（带分页）
     * 
     * @param array $params 查询参数
     * @return array
     */
    public static function getList(array $params = []): array
    {
        $page = $params['page'] ?? 1;
        $limit = $params['limit'] ?? 10;

        $query = self::withSearch(['status', 'type_name'], $params)
            ->order('sort_order', 'asc')
            ->order('id', 'desc');

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
     * 获取所有启用的类型
     * 
     * @return array
     */
    public static function getEnabledTypes(): array
    {
        return self::where('status', self::STATUS_ENABLED)
            ->order('sort_order', 'asc')
            ->order('id', 'desc')
            ->select()
            ->toArray();
    }

    /**
     * 根据类型编码获取类型
     * 
     * @param string $typeCode
     * @return CardType|null
     */
    public static function getByCode(string $typeCode): ?CardType
    {
        return self::where('type_code', $typeCode)->find();
    }

    /**
     * 批量删除类型
     * 
     * @param array $ids 类型ID数组
     * @return int 删除数量
     */
    public static function batchDelete(array $ids): int
    {
        return self::whereIn('id', $ids)->delete();
    }
}

