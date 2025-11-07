<?php

/**
 * 卡密服务类
 * 
 * 处理卡密相关的业务逻辑，调用CardKeyUtil工具类
 * 
 * @author AI Assistant
 * @date 2025-10-01
 */

namespace app\api\services;

use app\api\model\CardKey;
use app\api\model\CardType;
use app\api\model\CardKeyLog;
use app\api\model\Donation;
use app\api\model\users;
use app\api\model\premium;
use utils\CardKeyUtil;
use think\facade\Db;

class CardKeyService
{
    /**
     * 生成单个卡密
     * 
     * @param array $data 卡密数据
     * @return array
     */
    public function generate(array $data): array
    {
        // 验证type_id
        if (empty($data['type_id'])) {
            return [
                'success' => false,
                'message' => '卡密类型ID不能为空'
            ];
        }

        // 查询类型信息
        $cardType = CardType::find($data['type_id']);
        if (!$cardType) {
            return [
                'success' => false,
                'message' => '卡密类型不存在'
            ];
        }

        // 调用工具类批量生成（数量为1）
        return CardKeyUtil::batchGenerate(1, $data['type_id'], [
            'expire_time' => $data['expire_time'] ?? null,  // 单独设置的过期时间
            'remark' => $data['remark'] ?? '',
            'salt' => $data['salt'] ?? ''
        ]);
    }

    /**
     * 批量生成卡密
     * 
     * @param array $data 生成参数
     * @return array
     */
    public function batchGenerate(array $data): array
    {
        $count = $data['count'] ?? 1;
        $typeId = $data['type_id'] ?? null;

        if ($count <= 0 || $count > 10000) {
            return [
                'success' => false,
                'message' => '生成数量必须在1-10000之间'
            ];
        }

        if (empty($typeId)) {
            return [
                'success' => false,
                'message' => '卡密类型ID不能为空'
            ];
        }

        // 查询类型信息
        $cardType = CardType::find($typeId);
        if (!$cardType) {
            return [
                'success' => false,
                'message' => '卡密类型不存在'
            ];
        }

        // 调用工具类批量生成
        return CardKeyUtil::batchGenerate($count, $typeId, [
            'expire_time' => $data['expire_time'] ?? null,  // 单独设置的过期时间
            'remark' => $data['remark'] ?? '',
            'salt' => $data['salt'] ?? ''
        ]);
    }

    /**
     * 获取卡密列表
     * 
     * @param array $params 查询参数
     * @return array
     */
    public function getList(array $params): array
    {
        try {
            $result = CardKey::getList($params);

            return [
                'success' => true,
                'data' => $result
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '获取列表失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 获取卡密详情
     * 
     * @param int $id 卡密ID
     * @return array
     */
    public function getDetail(int $id): array
    {
        try {
            $detail = CardKey::getDetail($id);

            if (!$detail) {
                return [
                    'success' => false,
                    'message' => '卡密不存在'
                ];
            }

            return [
                'success' => true,
                'data' => $detail
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '获取详情失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 删除卡密
     * 
     * @param int $id 卡密ID
     * @return array
     */
    public function delete(int $id): array
    {
        try {
            $cardKey = CardKey::find($id);

            if (!$cardKey) {
                return [
                    'success' => false,
                    'message' => '卡密不存在'
                ];
            }

            // 已使用的卡密不允许删除
            if ($cardKey->status == CardKey::STATUS_USED) {
                return [
                    'success' => false,
                    'message' => '已使用的卡密不允许删除'
                ];
            }

            $cardKey->delete();

            return [
                'success' => true,
                'message' => '删除成功'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '删除失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 批量删除卡密
     * 
     * @param array $ids 卡密ID数组
     * @return array
     */
    public function batchDelete(array $ids): array
    {
        try {
            // 检查是否有已使用的卡密
            $usedCount = CardKey::whereIn('id', $ids)
                ->where('status', CardKey::STATUS_USED)
                ->count();

            if ($usedCount > 0) {
                return [
                    'success' => false,
                    'message' => "有{$usedCount}个已使用的卡密不允许删除"
                ];
            }

            $count = CardKey::batchDelete($ids);

            return [
                'success' => true,
                'message' => "成功删除{$count}个卡密"
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '批量删除失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 验证卡密
     * 
     * @param string $code 卡密码
     * @return array
     */
    public function verify(string $cardKey): array
    {
        // 调用工具类验证
        return CardKeyUtil::verify($cardKey);
    }

    /**
     * 使用卡密
     * 
     * @param string $code 卡密码
     * @param int $userId 使用者ID
     * @param array $extra 额外信息
     * @return array
     */
    public function use(string $cardKey, int $userId, array $extra = []): array
    {
        // 开启事务，确保数据一致性
        Db::startTrans();

        try {
            // 1. 验证卡密
            $verifyResult = CardKeyUtil::verify($cardKey);
            if (!$verifyResult['success']) {
                Db::rollback();
                return $verifyResult;
            }

            $cardKeyModel = $verifyResult['data'];

            // 2. 二次验证状态（防止并发问题）
            if ($cardKeyModel->status !== CardKey::STATUS_UNUSED) {
                Db::rollback();
                return [
                    'success' => false,
                    'message' => '卡密已被使用或禁用'
                ];
            }

            // 3. 获取卡密类型信息
            $cardType = $cardKeyModel->cardType;
            if (!$cardType) {
                Db::rollback();
                return [
                    'success' => false,
                    'message' => '卡密类型不存在'
                ];
            }

            // 4. 根据卡密类型的use_type决定处理逻辑
            $useType = $cardType->use_type ?? CardKeyLog::USE_TYPE_MEMBERSHIP;
            $relatedId = null;
            $relatedType = null;
            $membershipInfo = null;
            $donationInfo = null;
            $resultMessage = '使用成功';

            // 5. 更新卡密为已使用状态
            $cardKeyModel->status = CardKey::STATUS_USED;
            $cardKeyModel->user_id = $userId;
            $cardKeyModel->use_time = date('Y-m-d H:i:s');
            $cardKeyModel->save();

            // 6. 根据不同的use_type执行不同的业务逻辑
            switch ($useType) {
                case CardKeyLog::USE_TYPE_MEMBERSHIP:
                    // 兑换会员
                    if ($cardType->membership_duration !== null) {
                        $membershipResult = $this->processUserMembership($userId, $cardType->membership_duration, $cardType->type_name);

                        if (!$membershipResult['success']) {
                            Db::rollback();
                            return $membershipResult;
                        }

                        $membershipInfo = $membershipResult['data'];
                        $resultMessage = '会员兑换成功';
                    }
                    break;

                case CardKeyLog::USE_TYPE_DONATION:
                    // 捐赠类型的卡密 - 自动创建捐赠记录
                    // 创建捐赠记录（关联用户ID，捐赠者信息从用户表查询）
                    $donation = Donation::create([
                        'donation_no' => Donation::generateDonationNo(),
                        'user_id' => $userId,  // 关联用户ID
                        'channel' => Donation::CHANNEL_CARDKEY,
                        'amount' => 0,
                        'card_key_code' => $cardKeyModel->card_key,
                        'card_key_id' => $cardKeyModel->id,
                        'card_key_value' => $cardType->price ?? 0,
                        'status' => Donation::STATUS_CONFIRMED,  // 卡密捐赠直接确认
                        'is_anonymous' => 0,  // 默认不匿名（因为有user_id）
                        'is_public' => 1,     // 默认公开
                        'email' => null,      // 可选联系方式（用户可后续填写）
                        'iden' => null,       // 可选唯一标识（用户可后续填写）
                        'payment_time' => date('Y-m-d H:i:s'),
                        'confirm_time' => date('Y-m-d H:i:s'),
                        'remark' => '使用卡密捐赠',
                        'create_time' => date('Y-m-d H:i:s')
                    ]);

                    $relatedId = $donation->id;
                    $relatedType = CardKeyLog::RELATED_TYPE_DONATION;
                    $donationInfo = [
                        'donation_id' => $donation->id,
                        'donation_no' => $donation->donation_no,
                        'amount' => $cardType->price ?? 0
                    ];
                    $resultMessage = '捐赠成功，感谢您的支持！';
                    break;

                case CardKeyLog::USE_TYPE_REGISTER:
                    // 注册邀请码
                    // TODO: 实现注册邀请逻辑
                    $relatedId = $userId;  // 关联到新注册的用户
                    $relatedType = CardKeyLog::RELATED_TYPE_USER;
                    $resultMessage = '注册邀请码使用成功';
                    break;

                case CardKeyLog::USE_TYPE_PRODUCT:
                    // 商品兑换
                    // TODO: 实现商品兑换逻辑，创建订单
                    // $relatedId = $orderId;
                    // $relatedType = CardKeyLog::RELATED_TYPE_ORDER;
                    $resultMessage = '商品兑换码使用成功';
                    break;

                case CardKeyLog::USE_TYPE_POINTS:
                    // 积分兑换
                    // TODO: 实现积分兑换逻辑
                    // $relatedId = $pointsRecordId;
                    // $relatedType = CardKeyLog::RELATED_TYPE_POINTS;
                    $resultMessage = '积分兑换成功';
                    break;

                default:
                    // 其他类型
                    $resultMessage = '卡密使用成功';
                    break;
            }

            // 7. 记录使用日志
            $logRemark = $extra['remark'] ?? '';
            if ($membershipInfo) {
                $logRemark .= ($logRemark ? ' | ' : '') . "会员到期时间: {$membershipInfo['expiration_time']}";
            }

            CardKeyLog::addLog(
                $cardKeyModel->id,
                $userId,
                CardKeyLog::ACTION_USE,
                [
                    'use_type' => $useType,
                    'related_id' => $relatedId,
                    'related_type' => $relatedType,
                    'expire_time' => $membershipInfo['expiration_time'] ?? null,
                    'ip' => $extra['ip'] ?? request()->ip(),
                    'user_agent' => $extra['user_agent'] ?? request()->header('user-agent'),
                    'remark' => $logRemark
                ]
            );

            // 8. 提交事务
            Db::commit();

            return [
                'success' => true,
                'message' => $resultMessage,
                'data' => [
                    'card_key' => $cardKeyModel,
                    'use_type' => $useType,
                    'membership_info' => $membershipInfo,
                    'donation_info' => $donationInfo,
                    'related_id' => $relatedId,
                    'related_type' => $relatedType
                ]
            ];
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();

            return [
                'success' => false,
                'message' => '使用失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 处理用户会员时长
     * 
     * @param int $userId 用户ID
     * @param int $durationMinutes 会员时长（分钟）
     * @param string $typeName 卡密类型名称
     * @return array
     */
    private function processUserMembership(int $userId, int $durationMinutes, string $typeName = ''): array
    {
        try {
            // 获取用户模型
            $user = users::find($userId);
            if (!$user) {
                return [
                    'success' => false,
                    'message' => '用户不存在'
                ];
            }

            // 获取或创建会员信息
            $premium = $user->premium;
            $isNewPremium = false;

            if (!$premium) {
                $premium = new premium();
                $premium->user_id = $userId;
                $premium->create_time = date('Y-m-d H:i:s');

                // 生成会员ID（5位数字）
                $premiumId = \utils\NumUtil::generateNumberCode(1, 5);

                // 确保ID不重复
                $maxAttempts = 10;
                $attempts = 0;
                while (premium::where('id', $premiumId)->find() && $attempts < $maxAttempts) {
                    $premiumId = \utils\NumUtil::generateNumberCode(1, 5);
                    $attempts++;
                }

                if ($attempts >= $maxAttempts) {
                    throw new \Exception('无法生成唯一的会员ID，请稍后重试');
                }

                $premium->id = $premiumId;
                $isNewPremium = true;
            }

            // 0 = 永久会员
            if ($durationMinutes === 0) {
                $premium->expiration_time = '2080-01-01 00:00:00'; // 使用项目约定的永久会员时间
                $premium->remark = $typeName ?: '永久会员';
            } else {
                // 计算到期时间
                $currentExpireTime = $premium->expiration_time;

                // 检查当前会员是否为永久会员
                if ($currentExpireTime && strpos($currentExpireTime, '2080-01-01') !== false) {
                    // 已经是永久会员，不需要累加
                    return [
                        'success' => true,
                        'message' => '用户已是永久会员',
                        'data' => [
                            'is_permanent' => true,
                            'expiration_time' => $currentExpireTime,
                            'remark' => $premium->remark
                        ]
                    ];
                }

                // 判断是否需要累加时长
                if ($currentExpireTime && strtotime($currentExpireTime) > time()) {
                    // 如果当前会员未过期，在现有时间基础上累加
                    $newExpireTime = date('Y-m-d H:i:s', strtotime($currentExpireTime) + ($durationMinutes * 60));
                } else {
                    // 如果当前会员已过期或没有会员，从现在开始计算
                    $newExpireTime = date('Y-m-d H:i:s', time() + ($durationMinutes * 60));
                }

                $premium->expiration_time = $newExpireTime;
                $premium->remark = $typeName ?: $premium->remark ?: '普通会员';
            }

            // 保存会员信息
            $premium->save();

            return [
                'success' => true,
                'message' => $isNewPremium ? '会员开通成功' : '会员时长续期成功',
                'data' => [
                    'is_permanent' => $durationMinutes === 0,
                    'expiration_time' => $premium->expiration_time,
                    'remark' => $premium->remark,
                    'is_new' => $isNewPremium
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '会员处理失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 禁用卡密
     * 
     * @param int $id 卡密ID
     * @param int $userId 操作者ID
     * @param string $reason 禁用原因
     * @return array
     */
    public function disable(int $id, int $userId, string $reason = ''): array
    {
        try {
            $cardKey = CardKey::find($id);

            if (!$cardKey) {
                return [
                    'success' => false,
                    'message' => '卡密不存在'
                ];
            }

            // 已使用的卡密不能禁用
            if ($cardKey->status == CardKey::STATUS_USED) {
                return [
                    'success' => false,
                    'message' => '已使用的卡密不能禁用'
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

            // 记录禁用日志
            CardKeyLog::addLog(
                $cardKey->id,
                $userId,
                CardKeyLog::ACTION_DISABLE,
                [
                    'remark' => $reason
                ]
            );

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
     * 批量禁用卡密
     * 
     * @param array $ids 卡密ID数组
     * @param int $userId 操作者ID
     * @param string $reason 禁用原因
     * @return array
     */
    public function batchDisable(array $ids, int $userId, string $reason = ''): array
    {
        try {
            $successCount = 0;
            $failCount = 0;
            $usedCount = 0;
            $disabledCount = 0;
            $errors = [];

            foreach ($ids as $id) {
                $result = $this->disable($id, $userId, $reason);
                if ($result['success']) {
                    $successCount++;
                } else {
                    $failCount++;
                    // 记录具体的失败原因
                    if (strpos($result['message'], '已使用') !== false) {
                        $usedCount++;
                    } elseif (strpos($result['message'], '已经被禁用') !== false) {
                        $disabledCount++;
                    }
                    $errors[] = "ID {$id}: {$result['message']}";
                }
            }

            // 构建详细的消息
            $message = "禁用完成！";
            if ($successCount > 0) {
                $message .= "成功禁用 {$successCount} 张卡密";
            }
            if ($usedCount > 0) {
                $message .= ($successCount > 0 ? "，" : "") . "{$usedCount} 张卡密已使用无法禁用";
            }
            if ($disabledCount > 0) {
                $message .= ($successCount > 0 || $usedCount > 0 ? "，" : "") . "{$disabledCount} 张卡密已被禁用";
            }

            return [
                'success' => $successCount > 0,
                'message' => $message,
                'data' => [
                    'success_count' => $successCount,
                    'fail_count' => $failCount,
                    'used_count' => $usedCount,
                    'disabled_count' => $disabledCount,
                    'errors' => $errors
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '批量禁用失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 导出卡密
     * 
     * @param array $params 筛选参数
     * @return array
     */
    public function export(array $params): array
    {
        // 调用工具类导出
        return CardKeyUtil::exportCodes($params);
    }

    /**
     * 获取统计数据
     * 
     * @return array
     */
    public function getStatistics(): array
    {
        // 调用工具类获取统计
        return CardKeyUtil::getStatistics();
    }

    /**
     * 获取类型列表
     * 
     * @return array
     */
    public function getTypeList(): array
    {
        // 调用工具类获取类型列表
        return CardKeyUtil::getTypeList();
    }

    /**
     * 重置卡密状态（测试环境使用）
     * 
     * 将已使用或禁用的卡密重置为未使用状态
     * 注意：此功能仅用于测试环境，生产环境请禁用
     * 
     * @param int $id 卡密ID
     * @param int $userId 操作者ID
     * @param string $reason 重置原因
     * @return array
     */
    public function reset(int $id, int $userId, string $reason = ''): array
    {
        try {
            $cardKey = CardKey::find($id);

            if (!$cardKey) {
                return [
                    'success' => false,
                    'message' => '卡密不存在'
                ];
            }

            // 已经是未使用状态
            if ($cardKey->status == CardKey::STATUS_UNUSED) {
                return [
                    'success' => false,
                    'message' => '卡密已经是未使用状态'
                ];
            }

            // 重置为未使用状态
            $cardKey->status = CardKey::STATUS_UNUSED;
            $cardKey->user_id = null;
            $cardKey->use_time = null;
            $cardKey->save();

            // 记录重置日志
            CardKeyLog::addLog(
                $cardKey->id,
                $userId,
                'reset',
                [
                    'remark' => $reason ?: '测试重置'
                ]
            );

            return [
                'success' => true,
                'message' => '重置成功'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '重置失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 批量重置卡密状态（测试环境使用）
     * 
     * @param array $ids 卡密ID数组
     * @param int $userId 操作者ID
     * @param string $reason 重置原因
     * @return array
     */
    public function batchReset(array $ids, int $userId, string $reason = ''): array
    {
        try {
            $successCount = 0;
            $failCount = 0;
            $errors = [];

            foreach ($ids as $id) {
                $result = $this->reset($id, $userId, $reason);
                if ($result['success']) {
                    $successCount++;
                } else {
                    $failCount++;
                    $errors[] = "ID {$id}: {$result['message']}";
                }
            }

            return [
                'success' => $failCount === 0,
                'message' => "成功重置{$successCount}个卡密" . ($failCount > 0 ? "，失败{$failCount}个" : ''),
                'data' => [
                    'success_count' => $successCount,
                    'fail_count' => $failCount,
                    'errors' => $errors
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '批量重置失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 获取使用记录
     * 
     * @param int $cardKeyId 卡密ID
     * @param array $params 查询参数
     * @return array
     */
    public function getLogs(int $cardKeyId, array $params = []): array
    {
        try {
            $page = $params['page'] ?? 1;
            $limit = $params['limit'] ?? 10;

            $result = CardKeyLog::getLogsByCardKey($cardKeyId, $page, $limit);

            return [
                'success' => true,
                'data' => $result
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '获取记录失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 获取所有使用日志
     * 
     * @param array $params 查询参数
     * @return array
     */
    public function getAllLogs(array $params = []): array
    {
        try {
            $result = CardKeyLog::getList($params);

            return [
                'success' => true,
                'data' => $result
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '获取日志失败：' . $e->getMessage()
            ];
        }
    }
}
