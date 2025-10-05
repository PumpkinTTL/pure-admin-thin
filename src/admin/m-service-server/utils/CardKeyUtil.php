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
}

