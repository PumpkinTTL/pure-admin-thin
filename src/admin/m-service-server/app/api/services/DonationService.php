<?php

/**
 * 捐赠记录服务类
 * 
 * 处理捐赠记录相关的业务逻辑
 * 
 * @author AI Assistant
 * @date 2025-01-28
 */

namespace app\api\services;

use app\api\model\Donation;
use app\api\model\CardKey;
use app\api\model\CardKeyLog;
use app\api\model\users;
use think\facade\Db;

class DonationService
{
    /**
     * 获取捐赠列表
     * 
     * @param array $params 查询参数
     * @return array
     */
    public static function getList(array $params): array
    {
        try {
            $result = Donation::getList($params);

            return [
                'code' => 200,
                'data' => $result,
                'message' => '获取成功'
            ];
        } catch (\Exception $e) {
            return [
                'code' => 500,
                'data' => null,
                'message' => '获取列表失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 获取捐赠详情
     * 
     * @param int $id 捐赠ID
     * @return array
     */
    public static function getDetail(int $id): array
    {
        try {
            $detail = Donation::getDetail($id);

            if (!$detail) {
                return [
                    'code' => 404,
                    'data' => null,
                    'message' => '捐赠记录不存在'
                ];
            }

            return [
                'code' => 200,
                'data' => $detail,
                'message' => '获取成功'
            ];
        } catch (\Exception $e) {
            return [
                'code' => 500,
                'data' => null,
                'message' => '获取详情失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 添加捐赠记录
     * 
     * @param array $data 捐赠数据
     * @return array
     */
    public static function add(array $data): array
    {
        Db::startTrans();

        try {
            // 生成捐赠单号
            if (empty($data['donation_no'])) {
                $data['donation_no'] = Donation::generateDonationNo();
            }

            // 设置默认值
            $data['status'] = $data['status'] ?? Donation::STATUS_PENDING;
            $data['is_anonymous'] = $data['is_anonymous'] ?? 0;
            $data['is_public'] = $data['is_public'] ?? 1;
            $data['create_time'] = date('Y-m-d H:i:s');

            // 如果是卡密兑换，验证卡密并关联
            $cardKeyId = null;
            if ($data['channel'] === Donation::CHANNEL_CARDKEY && !empty($data['card_key_code'])) {
                $cardKeyResult = self::processCardKeyDonation($data);
                if ($cardKeyResult['code'] !== 200) {
                    Db::rollback();
                    return $cardKeyResult;
                }
                $data = array_merge($data, $cardKeyResult['data']);
                $cardKeyId = $cardKeyResult['data']['card_key_id'] ?? null;
            }

            // 创建捐赠记录
            $donation = Donation::create($data);

            // 如果是卡密捐赠，更新卡密日志的related_id
            if ($cardKeyId && $donation->id) {
                CardKeyLog::where('card_key_id', $cardKeyId)
                    ->where('use_type', CardKeyLog::USE_TYPE_DONATION)
                    ->where('related_id', null)
                    ->update(['related_id' => $donation->id]);
            }

            Db::commit();

            return [
                'code' => 200,
                'data' => Donation::getDetail($donation->id),
                'message' => '添加成功'
            ];
        } catch (\Exception $e) {
            Db::rollback();

            return [
                'code' => 500,
                'data' => null,
                'message' => '添加失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 更新捐赠记录
     * 
     * @param int $id 捐赠ID
     * @param array $data 更新数据
     * @return array
     */
    public static function update(int $id, array $data): array
    {
        Db::startTrans();

        try {
            $donation = Donation::find($id);

            if (!$donation) {
                return [
                    'code' => 404,
                    'data' => null,
                    'message' => '捐赠记录不存在'
                ];
            }

            // 更新时间
            $data['update_time'] = date('Y-m-d H:i:s');

            // 如果状态变更为已确认，记录确认时间
            if (isset($data['status']) && $data['status'] == Donation::STATUS_CONFIRMED && $donation->status != Donation::STATUS_CONFIRMED) {
                $data['confirm_time'] = date('Y-m-d H:i:s');
            }

            // 如果是卡密兑换且卡密码变更，重新验证
            if ($data['channel'] === Donation::CHANNEL_CARDKEY && !empty($data['card_key_code']) && $data['card_key_code'] !== $donation->card_key_code) {
                $cardKeyResult = self::processCardKeyDonation($data, $id);
                if ($cardKeyResult['code'] !== 200) {
                    Db::rollback();
                    return $cardKeyResult;
                }
                $data = array_merge($data, $cardKeyResult['data']);
            }

            // 更新数据
            $donation->save($data);

            Db::commit();

            return [
                'code' => 200,
                'data' => Donation::getDetail($id),
                'message' => '更新成功'
            ];
        } catch (\Exception $e) {
            Db::rollback();

            return [
                'code' => 500,
                'data' => null,
                'message' => '更新失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 删除捐赠记录（软删除）
     * 
     * @param int $id 捐赠ID
     * @return array
     */
    public static function delete(int $id): array
    {
        try {
            $donation = Donation::find($id);

            if (!$donation) {
                return [
                    'code' => 404,
                    'data' => null,
                    'message' => '捐赠记录不存在'
                ];
            }

            $donation->delete();

            return [
                'code' => 200,
                'data' => null,
                'message' => '删除成功'
            ];
        } catch (\Exception $e) {
            return [
                'code' => 500,
                'data' => null,
                'message' => '删除失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 批量删除捐赠记录
     * 
     * @param array $ids ID数组
     * @return array
     */
    public static function batchDelete(array $ids): array
    {
        try {
            $count = Donation::batchDelete($ids);

            return [
                'code' => 200,
                'data' => ['count' => $count],
                'message' => "成功删除{$count}条记录"
            ];
        } catch (\Exception $e) {
            return [
                'code' => 500,
                'data' => null,
                'message' => '批量删除失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 恢复捐赠记录
     * 
     * @param int $id 捐赠ID
     * @return array
     */
    public static function restore(int $id): array
    {
        try {
            $donation = Donation::onlyTrashed()->find($id);

            if (!$donation) {
                return [
                    'code' => 404,
                    'data' => null,
                    'message' => '捐赠记录不存在或未被删除'
                ];
            }

            $donation->restore();

            return [
                'code' => 200,
                'data' => Donation::getDetail($id),
                'message' => '恢复成功'
            ];
        } catch (\Exception $e) {
            return [
                'code' => 500,
                'data' => null,
                'message' => '恢复失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 获取已删除的捐赠记录列表
     * 
     * @param array $params 查询参数
     * @return array
     */
    public static function getDeletedList(array $params): array
    {
        try {
            $params['query_deleted'] = 'only_deleted';
            $result = Donation::getList($params);

            return [
                'code' => 200,
                'data' => $result,
                'message' => '获取成功'
            ];
        } catch (\Exception $e) {
            return [
                'code' => 500,
                'data' => null,
                'message' => '获取已删除列表失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 处理卡密捐赠
     *
     * @param array $data 捐赠数据
     * @param int|null $donationId 捐赠记录ID（更新时传入）
     * @return array
     */
    private static function processCardKeyDonation(array &$data, ?int $donationId = null): array
    {
        $cardKeyCode = $data['card_key_code'] ?? '';

        if (empty($cardKeyCode)) {
            return [
                'code' => 400,
                'data' => null,
                'message' => '卡密码不能为空'
            ];
        }

        // 查询卡密
        $cardKey = CardKey::where('card_key', $cardKeyCode)->find();

        if (!$cardKey) {
            return [
                'code' => 404,
                'data' => null,
                'message' => '卡密不存在'
            ];
        }

        // 验证卡密状态
        if ($cardKey->status !== CardKey::STATUS_UNUSED) {
            return [
                'code' => 400,
                'data' => null,
                'message' => '卡密已被使用或已禁用'
            ];
        }

        // 验证卡密是否过期
        if ($cardKey->isExpired()) {
            return [
                'code' => 400,
                'data' => null,
                'message' => '卡密已过期'
            ];
        }

        // 获取卡密类型信息
        $cardType = $cardKey->cardType;
        $cardKeyValue = 0;

        if ($cardType && $cardType->price) {
            $cardKeyValue = $cardType->price;
        }

        // 获取捐赠者ID（如果有）
        $userId = $data['user_id'] ?? null;
        if (empty($userId)) {
            // 如果没有user_id，使用匿名用户ID（假设为0或1）
            $userId = 1;
        }

        // 标记卡密为已使用
        $cardKey->status = CardKey::STATUS_USED;
        $cardKey->user_id = $userId;
        $cardKey->use_time = date('Y-m-d H:i:s');
        $cardKey->save();

        // 记录卡密使用日志
        CardKeyLog::addLog(
            $cardKey->id,
            $userId,
            CardKeyLog::ACTION_USE,
            [
                'use_type' => CardKeyLog::USE_TYPE_DONATION,
                'related_id' => $donationId, // 捐赠记录ID（新增时为null，后续需要更新）
                'related_type' => CardKeyLog::RELATED_TYPE_DONATION,
                'ip' => request()->ip(),
                'user_agent' => request()->header('user-agent'),
                'remark' => '使用卡密进行捐赠'
            ]
        );

        return [
            'code' => 200,
            'data' => [
                'card_key_id' => $cardKey->id,
                'card_key_value' => $cardKeyValue
            ],
            'message' => '卡密验证成功'
        ];
    }

    /**
     * 更新捐赠状态
     *
     * @param int $id 捐赠ID
     * @param int $status 状态
     * @return array
     */
    public static function updateStatus(int $id, int $status): array
    {
        try {
            $donation = Donation::find($id);

            if (!$donation) {
                return [
                    'code' => 404,
                    'data' => null,
                    'message' => '捐赠记录不存在'
                ];
            }

            $donation->status = $status;
            $donation->update_time = date('Y-m-d H:i:s');

            // 如果状态变更为已确认，记录确认时间
            if ($status == Donation::STATUS_CONFIRMED && $donation->status != Donation::STATUS_CONFIRMED) {
                $donation->confirm_time = date('Y-m-d H:i:s');
            }

            $donation->save();

            return [
                'code' => 200,
                'data' => Donation::getDetail($id),
                'message' => '状态更新成功'
            ];
        } catch (\Exception $e) {
            return [
                'code' => 500,
                'data' => null,
                'message' => '状态更新失败：' . $e->getMessage()
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
            // 总捐赠记录数
            $totalCount = Donation::count();

            // 各状态统计
            $pendingCount = Donation::where('status', Donation::STATUS_PENDING)->count();
            $confirmedCount = Donation::where('status', Donation::STATUS_CONFIRMED)->count();
            $completedCount = Donation::where('status', Donation::STATUS_COMPLETED)->count();
            $cancelledCount = Donation::where('status', Donation::STATUS_CANCELLED)->count();

            // 各渠道统计
            $wechatCount = Donation::where('channel', Donation::CHANNEL_WECHAT)->count();
            $alipayCount = Donation::where('channel', Donation::CHANNEL_ALIPAY)->count();
            $cryptoCount = Donation::where('channel', Donation::CHANNEL_CRYPTO)->count();
            $cardkeyCount = Donation::where('channel', Donation::CHANNEL_CARDKEY)->count();

            // 总金额统计（排除卡密兑换）
            $totalAmount = Donation::whereIn('channel', [
                Donation::CHANNEL_WECHAT,
                Donation::CHANNEL_ALIPAY,
                Donation::CHANNEL_CRYPTO
            ])->sum('amount');

            // 卡密等值金额统计
            $cardkeyTotalValue = Donation::where('channel', Donation::CHANNEL_CARDKEY)
                ->sum('card_key_value');

            // 今日捐赠统计
            $todayCount = Donation::whereTime('create_time', 'today')->count();
            $todayAmount = Donation::whereTime('create_time', 'today')
                ->whereIn('channel', [
                    Donation::CHANNEL_WECHAT,
                    Donation::CHANNEL_ALIPAY,
                    Donation::CHANNEL_CRYPTO
                ])->sum('amount');

            // 本月捐赠统计
            $monthCount = Donation::whereTime('create_time', 'month')->count();
            $monthAmount = Donation::whereTime('create_time', 'month')
                ->whereIn('channel', [
                    Donation::CHANNEL_WECHAT,
                    Donation::CHANNEL_ALIPAY,
                    Donation::CHANNEL_CRYPTO
                ])->sum('amount');

            return [
                'code' => 200,
                'data' => [
                    'total_count' => $totalCount,
                    'status_stats' => [
                        'pending' => $pendingCount,
                        'confirmed' => $confirmedCount,
                        'completed' => $completedCount,
                        'cancelled' => $cancelledCount,
                    ],
                    'channel_stats' => [
                        'wechat' => $wechatCount,
                        'alipay' => $alipayCount,
                        'crypto' => $cryptoCount,
                        'cardkey' => $cardkeyCount,
                    ],
                    'amount_stats' => [
                        'total_amount' => round($totalAmount, 2),
                        'cardkey_total_value' => round($cardkeyTotalValue, 2),
                        'today_count' => $todayCount,
                        'today_amount' => round($todayAmount, 2),
                        'month_count' => $monthCount,
                        'month_amount' => round($monthAmount, 2),
                    ]
                ],
                'message' => '获取成功'
            ];
        } catch (\Exception $e) {
            return [
                'code' => 500,
                'data' => null,
                'message' => '获取统计数据失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 获取渠道选项
     *
     * @return array
     */
    public static function getChannelOptions(): array
    {
        return [
            'code' => 200,
            'data' => Donation::$channelMap,
            'message' => '获取成功'
        ];
    }

    /**
     * 获取状态选项
     *
     * @return array
     */
    public static function getStatusOptions(): array
    {
        return [
            'code' => 200,
            'data' => Donation::$statusMap,
            'message' => '获取成功'
        ];
    }

    /**
     * 通过邮箱、iden或user_id查询捐赠记录
     *
     * @param string $email 邮箱
     * @param string $iden 标识符
     * @param int $userId 用户ID
     * @return array
     */
    public static function queryByContact(string $email, string $iden, int $userId = 0): array
    {
        try {
            $query = Donation::with(['user', 'cardKey']);

            // 构建查询条件
            $hasCondition = false;

            if (!empty($email) || !empty($iden) || !empty($userId)) {
                $query->where(function ($q) use ($email, $iden, $userId) {
                    if (!empty($email)) {
                        $q->whereOr('email', $email);
                    }
                    if (!empty($iden)) {
                        $q->whereOr('iden', $iden);
                    }
                    if (!empty($userId)) {
                        $q->whereOr('user_id', $userId);
                    }
                });
                $hasCondition = true;
            }

            if (!$hasCondition) {
                return [
                    'code' => 400,
                    'data' => null,
                    'message' => '请提供邮箱、iden或user_id'
                ];
            }

            // 只查询未删除的记录
            $query->where('delete_time', null);

            // 按创建时间倒序
            $list = $query->order('create_time', 'desc')->select();

            // 格式化数据
            $result = [];
            foreach ($list as $item) {
                $result[] = self::formatDonationData($item);
            }

            return [
                'code' => 200,
                'data' => $result,
                'message' => '查询成功'
            ];
        } catch (\Exception $e) {
            return [
                'code' => 500,
                'data' => null,
                'message' => '查询失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 格式化捐赠数据
     *
     * @param Donation $donation
     * @return array
     */
    private static function formatDonationData($donation): array
    {
        $data = $donation->toArray();

        // 添加状态文本
        $data['status_text'] = Donation::$statusMap[$donation->status] ?? '未知';

        // 添加渠道文本
        $data['channel_text'] = Donation::$channelMap[$donation->channel] ?? '未知';

        return $data;
    }
}
