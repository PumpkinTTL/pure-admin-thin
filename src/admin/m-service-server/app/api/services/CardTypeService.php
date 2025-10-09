<?php

/**
 * 卡密类型服务类
 * 
 * 处理卡密类型相关的业务逻辑
 * 
 * @author AI Assistant
 * @date 2025-10-04
 */

namespace app\api\services;

use app\api\model\CardType;
use app\api\model\CardKey;

class CardTypeService
{
    /**
     * 获取类型列表
     * 
     * @param array $params 查询参数
     * @return array
     */
    public function getList(array $params): array
    {
        try {
            $result = CardType::getList($params);

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
     * 获取所有启用的类型
     * 
     * @return array
     */
    public function getEnabledTypes(): array
    {
        try {
            $types = CardType::getEnabledTypes();

            return [
                'success' => true,
                'data' => $types
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '获取类型列表失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 获取类型详情
     * 
     * @param int $id 类型ID
     * @return array
     */
    public function getDetail(int $id): array
    {
        try {
            $type = CardType::find($id);

            if (!$type) {
                return [
                    'success' => false,
                    'message' => '类型不存在'
                ];
            }

            return [
                'success' => true,
                'data' => $type
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '获取详情失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 创建类型
     * 
     * @param array $data 类型数据
     * @return array
     */
    public function create(array $data): array
    {
        try {
            // 检查类型名称是否已存在
            if (CardType::where('type_name', $data['type_name'])->find()) {
                return [
                    'success' => false,
                    'message' => '类型名称已存在'
                ];
            }

            // 检查类型编码是否已存在
            if (CardType::where('type_code', $data['type_code'])->find()) {
                return [
                    'success' => false,
                    'message' => '类型编码已存在'
                ];
            }

            $type = CardType::create($data);

            return [
                'success' => true,
                'message' => '创建成功',
                'data' => $type
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '创建失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 更新类型
     * 
     * @param int $id 类型ID
     * @param array $data 类型数据
     * @return array
     */
    public function update(int $id, array $data): array
    {
        try {
            $type = CardType::find($id);

            if (!$type) {
                return [
                    'success' => false,
                    'message' => '类型不存在'
                ];
            }

            // 检查类型名称是否已被其他记录使用
            if (isset($data['type_name'])) {
                $exists = CardType::where('type_name', $data['type_name'])
                    ->where('id', '<>', $id)
                    ->find();
                if ($exists) {
                    return [
                        'success' => false,
                        'message' => '类型名称已存在'
                    ];
                }
            }

            // 检查类型编码是否已被其他记录使用
            if (isset($data['type_code'])) {
                $exists = CardType::where('type_code', $data['type_code'])
                    ->where('id', '<>', $id)
                    ->find();
                if ($exists) {
                    return [
                        'success' => false,
                        'message' => '类型编码已存在'
                    ];
                }
            }

            $type->save($data);

            return [
                'success' => true,
                'message' => '更新成功',
                'data' => $type
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '更新失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 删除类型
     * 
     * @param int $id 类型ID
     * @return array
     */
    public function delete(int $id): array
    {
        try {
            $type = CardType::find($id);

            if (!$type) {
                return [
                    'success' => false,
                    'message' => '类型不存在'
                ];
            }

            // 检查是否有卡密正在使用此类型
            $cardKeyCount = CardKey::where('type_id', $id)->count();
            if ($cardKeyCount > 0) {
                return [
                    'success' => false,
                    'message' => "有{$cardKeyCount}个卡密正在使用此类型，不允许删除"
                ];
            }

            $type->delete();

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
     * 批量删除类型
     * 
     * @param array $ids 类型ID数组
     * @return array
     */
    public function batchDelete(array $ids): array
    {
        try {
            // 检查是否有卡密正在使用这些类型
            $cardKeyCount = CardKey::whereIn('type_id', $ids)->count();
            if ($cardKeyCount > 0) {
                return [
                    'success' => false,
                    'message' => "有{$cardKeyCount}个卡密正在使用这些类型，不允许删除"
                ];
            }
            
            $count = CardType::batchDelete($ids);

            return [
                'success' => true,
                'message' => "成功删除{$count}个类型"
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '批量删除失败：' . $e->getMessage()
            ];
        }
    }
}

