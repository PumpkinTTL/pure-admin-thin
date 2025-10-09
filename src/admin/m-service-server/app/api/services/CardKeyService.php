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
            
            // 3. 更新卡密为已使用状态
            $cardKeyModel->status = CardKey::STATUS_USED;
            $cardKeyModel->user_id = $userId;
            $cardKeyModel->use_time = date('Y-m-d H:i:s');
            $cardKeyModel->save();
            
            // 4. 处理会员时长逻辑（核心功能）
            $cardType = $cardKeyModel->cardType;
            $membershipInfo = null;
            
            if ($cardType && $cardType->membership_duration !== null) {
                // 调用会员时长处理方法
                $membershipResult = $this->processUserMembership($userId, $cardType->membership_duration, $cardType->type_name);
                
                if (!$membershipResult['success']) {
                    // 如果会员处理失败，回滚所有操作
                    Db::rollback();
                    return $membershipResult;
                }
                
                $membershipInfo = $membershipResult['data'];
            }
            
            // 5. 记录使用日志
            $logRemark = $extra['remark'] ?? '';
            if ($membershipInfo) {
                $logRemark .= ($logRemark ? ' | ' : '') . "会员到期时间: {$membershipInfo['expiration_time']}";
            }
            
            CardKeyLog::create([
                'card_key_id' => $cardKeyModel->id,
                'user_id' => $userId,
                'action' => 'use',
                'ip' => $extra['ip'] ?? '',
                'user_agent' => $extra['user_agent'] ?? '',
                'remark' => $logRemark,
                'create_time' => date('Y-m-d H:i:s')
            ]);
            
            // 提交事务
            Db::commit();
            
            return [
                'success' => true,
                'message' => '使用成功' . ($membershipInfo ? '，会员已开通' : ''),
                'data' => [
                    'card_key' => $cardKeyModel,
                    'membership_info' => $membershipInfo
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
            CardKeyLog::create([
                'card_key_id' => $cardKey->id,
                'user_id' => $userId,
                'action' => 'disable',
                'remark' => $reason,
                'create_time' => date('Y-m-d H:i:s')
            ]);

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
