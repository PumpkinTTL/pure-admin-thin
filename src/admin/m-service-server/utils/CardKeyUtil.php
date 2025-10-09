<?php

/**
 * 卡密生成工具类
 * 全部使用 card_key 字段
 */

namespace utils;

use app\api\model\CardKey;
use app\api\model\CardType;

class CardKeyUtil
{
    /**
     * 批量生成卡密
     * 
     * @param int $count 生成数量
     * @param int $typeId 类型ID
     * @param array $options 其他选项（expire_time, remark, salt）
     * @return array
     */
    public static function batchGenerate(int $count, int $typeId, array $options = []): array
    {
        try {
            // 获取类型信息
            $cardType = CardType::find($typeId);
            if (!$cardType) {
                return [
                    'success' => false,
                    'message' => '卡密类型不存在'
                ];
            }

            // 准备批量插入数据
            $data = [];
            $generatedCodes = [];
            $salt = $options['salt'] ?? '';
            $remark = $options['remark'] ?? '';
            $expireTime = $options['expire_time'] ?? null;

            // 生成唯一卡密码
            for ($i = 0; $i < $count; $i++) {
                $cardKey = self::generateUniqueCode($salt);
                
                // 确保唯一性
                $retry = 0;
                while (self::codeExists($cardKey) && $retry < 10) {
                    $cardKey = self::generateUniqueCode($salt);
                    $retry++;
                }

                if ($retry >= 10) {
                    return [
                        'success' => false,
                        'message' => '生成唯一卡密失败，请稍后重试'
                    ];
                }

                $generatedCodes[] = $cardKey;

                $data[] = [
                    'card_key' => $cardKey,  // 使用新字段名
                    'type_id' => $typeId,
                    'status' => CardKey::STATUS_UNUSED,
                    'expire_time' => $expireTime,
                    'remark' => $remark,
                    'create_time' => date('Y-m-d H:i:s')
                ];
            }

            // 批量插入数据库
            $model = new CardKey();
            $result = $model->saveAll($data);

            if ($result) {
                return [
                    'success' => true,
                    'message' => "成功生成{$count}个卡密",
                    'data' => [
                        'total' => $count,
                        'codes' => $generatedCodes
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'message' => '批量生成失败'
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '生成失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 生成唯一的卡密码
     * 
     * @param string $salt 盐值
     * @return string
     */
    private static function generateUniqueCode(string $salt = ''): string
    {
        // 生成16位随机字符串（大写字母+数字，去除易混淆字符）
        $characters = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $code = '';
        
        for ($i = 0; $i < 16; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }

        // 每4位添加一个分隔符（可选）
        $code = substr($code, 0, 4) . '-' . 
                substr($code, 4, 4) . '-' . 
                substr($code, 8, 4) . '-' . 
                substr($code, 12, 4);

        return $code;
    }

    /**
     * 检查卡密码是否已存在
     * 
     * @param string $cardKey 卡密码
     * @return bool
     */
    private static function codeExists(string $cardKey): bool
    {
        return CardKey::where('card_key', $cardKey)->count() > 0;
    }

    /**
     * 验证卡密是否可用
     * 
     * @param string $cardKeyString 卡密码
     * @return array
     */
    public static function verify(string $cardKeyString): array
    {
        $cardKey = CardKey::with('cardType')->where('card_key', $cardKeyString)->find();

        if (!$cardKey) {
            return [
                'success' => false,
                'message' => '卡密不存在'
            ];
        }

        if ($cardKey->status == CardKey::STATUS_USED) {
            return [
                'success' => false,
                'message' => '卡密已被使用'
            ];
        }

        if ($cardKey->status == CardKey::STATUS_DISABLED) {
            return [
                'success' => false,
                'message' => '卡密已被禁用'
            ];
        }

        if ($cardKey->isExpired()) {
            return [
                'success' => false,
                'message' => '卡密已过期'
            ];
        }

        return [
            'success' => true,
            'message' => '卡密可用',
            'data' => $cardKey
        ];
    }

    /**
     * 禁用卡密
     * 
     * @param string $cardKeyString 卡密码
     * @param int $userId 操作者ID
     * @param string $reason 禁用原因
     * @return array
     */
    public static function disable(string $cardKeyString, int $userId, string $reason = ''): array
    {
        try {
            $cardKey = CardKey::where('card_key', $cardKeyString)->find();

            if (!$cardKey) {
                return [
                    'success' => false,
                    'message' => '卡密不存在'
                ];
            }

            if ($cardKey->status == CardKey::STATUS_DISABLED) {
                return [
                    'success' => false,
                    'message' => '卡密已经被禁用'
                ];
            }

            // 更新为禁用状态
            $cardKey->status = CardKey::STATUS_DISABLED;
            $cardKey->save();

            // TODO: 记录禁用日志
            // CardKeyLog::create([
            //     'card_key_id' => $cardKey->id,
            //     'user_id' => $userId,
            //     'action' => 'disable',
            //     'remark' => $reason,
            //     'create_time' => date('Y-m-d H:i:s')
            // ]);

            return [
                'success' => true,
                'message' => '禁用成功'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '禁用失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 按ID禁用卡密
     * 
     * @param int $id 卡密ID
     * @param int $userId 操作者ID
     * @param string $reason 禁用原因
     * @return array
     */
    public static function disableById(int $id, int $userId, string $reason = ''): array
    {
        try {
            $cardKey = CardKey::find($id);

            if (!$cardKey) {
                return [
                    'success' => false,
                    'message' => '卡密不存在'
                ];
            }

            if ($cardKey->status == CardKey::STATUS_DISABLED) {
                return [
                    'success' => false,
                    'message' => '卡密已经被禁用'
                ];
            }

            // 更新为禁用状态
            $cardKey->status = CardKey::STATUS_DISABLED;
            $cardKey->save();

            // TODO: 记录禁用日志

            return [
                'success' => true,
                'message' => '禁用成功'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '禁用失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 导出卡密
     * 
     * @param array $params 筛选参数
     * @return array
     */
    public static function exportCodes(array $params = []): array
    {
        try {
            // 获取符合条件的卡密
            $query = CardKey::with('cardType');

            // 应用筛选条件
            if (!empty($params['type_id'])) {
                $query->where('type_id', $params['type_id']);
            }
            if (isset($params['status']) && $params['status'] !== '') {
                $query->where('status', $params['status']);
            }
            if (!empty($params['card_key'])) {
                $query->where('card_key', 'like', '%' . $params['card_key'] . '%');
            }

            $list = $query->select();

            // 生成CSV内容
            $csv = "ID,卡密码,类型,状态,生成时间,过期时间\n";
            foreach ($list as $item) {
                $statusText = '';
                if ($item->status == CardKey::STATUS_UNUSED) $statusText = '未使用';
                elseif ($item->status == CardKey::STATUS_USED) $statusText = '已使用';
                elseif ($item->status == CardKey::STATUS_DISABLED) $statusText = '已禁用';

                $csv .= implode(',', [
                    $item->id,
                    $item->card_key,
                    $item->cardType ? $item->cardType->type_name : '-',
                    $statusText,
                    $item->create_time,
                    $item->expire_time ?: '永久可用'
                ]) . "\n";
            }

            return [
                'success' => true,
                'data' => $csv,
                'filename' => 'cardkeys_' . date('YmdHis') . '.csv'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '导出失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 获取统计数据
     * 
     * @return array
     */
    public static function getStatistics(): array
    {
        try {
            // 基础统计
            $total = CardKey::count();
            $unused = CardKey::where('status', CardKey::STATUS_UNUSED)->count();
            $used = CardKey::where('status', CardKey::STATUS_USED)->count();
            $disabled = CardKey::where('status', CardKey::STATUS_DISABLED)->count();
            
            // 过期统计（未使用且已过期）
            $expired = CardKey::where('status', CardKey::STATUS_UNUSED)
                ->where('expire_time', '<', date('Y-m-d H:i:s'))
                ->where('expire_time', '<>', '0000-00-00 00:00:00')
                ->whereNotNull('expire_time')
                ->count();
            
            // 按类型统计
            $byType = CardKey::field('type_id, COUNT(*) as count')
                ->with('cardType')
                ->group('type_id')
                ->select();
            
            $typeStats = [];
            foreach ($byType as $item) {
                $typeStats[] = [
                    'type_id' => $item->type_id,
                    'type_name' => $item->cardType ? $item->cardType->type_name : '未知类型',
                    'count' => $item->count,
                    'unused_count' => CardKey::where('type_id', $item->type_id)
                        ->where('status', CardKey::STATUS_UNUSED)
                        ->count(),
                    'used_count' => CardKey::where('type_id', $item->type_id)
                        ->where('status', CardKey::STATUS_USED)
                        ->count(),
                    'disabled_count' => CardKey::where('type_id', $item->type_id)
                        ->where('status', CardKey::STATUS_DISABLED)
                        ->count()
                ];
            }
            
            // 今日统计
            $todayStart = date('Y-m-d 00:00:00');
            $todayEnd = date('Y-m-d 23:59:59');
            $todayCreated = CardKey::whereBetween('create_time', [$todayStart, $todayEnd])->count();
            $todayUsed = CardKey::where('status', CardKey::STATUS_USED)
                ->whereBetween('use_time', [$todayStart, $todayEnd])
                ->count();
            
            // 本周统计
            $weekStart = date('Y-m-d 00:00:00', strtotime('this week'));
            $weekEnd = date('Y-m-d 23:59:59');
            $weekCreated = CardKey::whereBetween('create_time', [$weekStart, $weekEnd])->count();
            $weekUsed = CardKey::where('status', CardKey::STATUS_USED)
                ->whereBetween('use_time', [$weekStart, $weekEnd])
                ->count();
            
            // 本月统计
            $monthStart = date('Y-m-01 00:00:00');
            $monthEnd = date('Y-m-d 23:59:59');
            $monthCreated = CardKey::whereBetween('create_time', [$monthStart, $monthEnd])->count();
            $monthUsed = CardKey::where('status', CardKey::STATUS_USED)
                ->whereBetween('use_time', [$monthStart, $monthEnd])
                ->count();

            return [
                'success' => true,
                'data' => [
                    'overview' => [
                        'total' => $total,
                        'unused' => $unused,
                        'used' => $used,
                        'disabled' => $disabled,
                        'expired' => $expired,
                        'usage_rate' => $total > 0 ? round($used / $total * 100, 2) : 0
                    ],
                    'by_type' => $typeStats,
                    'today' => [
                        'created' => $todayCreated,
                        'used' => $todayUsed
                    ],
                    'week' => [
                        'created' => $weekCreated,
                        'used' => $weekUsed
                    ],
                    'month' => [
                        'created' => $monthCreated,
                        'used' => $monthUsed
                    ]
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '获取统计数据失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 获取类型列表
     * 
     * @return array
     */
    public static function getTypeList(): array
    {
        try {
            $list = CardType::where('status', 1)->select();

            return [
                'success' => true,
                'data' => $list
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '获取类型列表失败：' . $e->getMessage()
            ];
        }
    }
}

