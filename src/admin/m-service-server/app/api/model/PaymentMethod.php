<?php

namespace app\api\model;

use think\Model;

class PaymentMethod extends Model
{

    // 设置时间戳格式为日期时间格式
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 主键设置
    protected $pk = 'id';

    // 设置数据表名称
    protected $table = 'bl_payment_methods';
    
    // 类型常量
    const TYPE_TRADITIONAL = 1; // 传统支付
    const TYPE_CRYPTO = 2;      // 加密货币
    const TYPE_DIGITAL_WALLET = 3; // 数字钱包
    
    // 状态常量
    const STATUS_DISABLED = 0;  // 禁用
    const STATUS_ENABLED = 1;   // 启用
    
    /**
     * 类型描述映射
     * @var array
     */
    public static $typeMap = [
        self::TYPE_TRADITIONAL => '传统支付',
        self::TYPE_CRYPTO => '加密货币',
        self::TYPE_DIGITAL_WALLET => '数字钱包'
    ];
    
    /**
     * 状态描述映射
     * @var array
     */
    public static $statusMap = [
        self::STATUS_DISABLED => '禁用',
        self::STATUS_ENABLED => '启用'
    ];
    
    /**
     * 获取类型文本描述
     * @param $value
     * @param $data
     * @return string
     */
    public function getTypeTextAttr($value, $data)
    {
        return self::$typeMap[$data['type']] ?? '未知类型';
    }
    
    /**
     * 获取状态文本描述
     * @param $value
     * @param $data
     * @return string
     */
    public function getStatusTextAttr($value, $data)
    {
        return self::$statusMap[$data['status']] ?? '未知状态';
    }
    
    /**
     * 获取是否为加密货币文本描述
     * @param $value
     * @param $data
     * @return string
     */
    public function getIsCryptoTextAttr($value, $data)
    {
        return $data['is_crypto'] ? '是' : '否';
    }
    
    /**
     * 获取是否为默认支付方式文本描述
     * @param $value
     * @param $data
     * @return string
     */
    public function getIsDefaultTextAttr($value, $data)
    {
        return $data['is_default'] ? '是' : '否';
    }
    
    /**
     * 搜索器：根据名称搜索
     * @param $query
     * @param $value
     */
    public function searchNameAttr($query, $value)
    {
        $query->whereLike('name', '%' . $value . '%');
    }

    /**
     * 搜索器：根据代码搜索
     * @param $query
     * @param $value
     */
    public function searchCodeAttr($query, $value)
    {
        $query->whereLike('code', '%' . $value . '%');
    }

    /**
     * 搜索器：根据类型搜索
     * @param $query
     * @param $value
     */
    public function searchTypeAttr($query, $value)
    {
        $query->where('type', $value);
    }

    /**
     * 搜索器：根据状态搜索
     * @param $query
     * @param $value
     */
    public function searchStatusAttr($query, $value)
    {
        $query->where('status', $value);
    }

    /**
     * 搜索器：根据是否为加密货币搜索
     * @param $query
     * @param $value
     */
    public function searchIsCryptoAttr($query, $value)
    {
        $query->where('is_crypto', $value);
    }

    /**
     * 搜索器：根据货币代码搜索
     * @param $query
     * @param $value
     */
    public function searchCurrencyCodeAttr($query, $value)
    {
        $query->where('currency_code', $value);
    }
}

