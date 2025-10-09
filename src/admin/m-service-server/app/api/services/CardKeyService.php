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
use utils\CardKeyUtil;

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
        try {
            // 验证卡密
            $verifyResult = CardKeyUtil::verify($cardKey);
            if (!$verifyResult['success']) {
                return $verifyResult;
            }

            $cardKeyModel = $verifyResult['data'];
            
            // 更新为已使用状态
            $cardKeyModel->status = CardKey::STATUS_USED;
            $cardKeyModel->user_id = $userId;
            $cardKeyModel->use_time = date('Y-m-d H:i:s');
            $cardKeyModel->save();
            
            // 记录使用日志
            CardKeyLog::create([
                'card_key_id' => $cardKeyModel->id,
                'user_id' => $userId,
                'action' => 'use',
                'ip' => $extra['ip'] ?? '',
                'user_agent' => $extra['user_agent'] ?? '',
                'remark' => $extra['remark'] ?? '',
                'create_time' => date('Y-m-d H:i:s')
            ]);
            
            return [
                'success' => true,
                'message' => '使用成功',
                'data' => $cardKeyModel
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '使用失败：' . $e->getMessage()
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
