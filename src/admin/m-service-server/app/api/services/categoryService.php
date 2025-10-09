<?php

namespace app\api\services;

use app\api\model\category as categoryModel;
use app\api\services\LogService;
use think\facade\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

class categoryService
{
    protected $autoWriteTimestamp = true;
    protected $createTime = false; // 创建时不自动写入


    /**
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     * @throws DbException
     */
    public static function selectCategoryAll(array $params = [])
    {
        try {
            // 构建查询
            $query = categoryModel::with(['author' => function ($query) {
                $query->field(['id', 'nickname', 'username', 'avatar']);
            }]);

            // 处理软删除查询
            if (isset($params['delete_status'])) {
                LogService::log('CategoryService软删除查询', [
                    'delete_status' => $params['delete_status']
                ]);

                if ($params['delete_status'] === 'only_deleted') {
                    $query->onlyTrashed();
                    LogService::log('CategoryService: 使用onlyTrashed()查询');
                } elseif ($params['delete_status'] === 'with_deleted') {
                    $query->withTrashed();
                    LogService::log('CategoryService: 使用withTrashed()查询');
                }
            } else {
                LogService::log('CategoryService: 使用默认查询（未删除数据）');
            }

            // 动态查询条件
            if (!empty($params['id'])) {
                $query->where('id', $params['id']);
            }

            if (!empty($params['name'])) {
                $query->where('name', 'like', '%' . $params['name'] . '%');
            }

            if (isset($params['type']) && $params['type'] !== '') {
                $query->where('type', $params['type']);
            }

            if (isset($params['parent_id']) && $params['parent_id'] !== '') {
                $query->where('parent_id', $params['parent_id']);
            }

            if (!empty($params['user_id'])) {
                $query->where('user_id', $params['user_id']);
            }

            if (!empty($params['description'])) {
                $query->where('description', 'like', '%' . $params['description'] . '%');
            }

            // 分页处理
            $pageSize = isset($params['page_size']) ? max(1, min(100, (int)$params['page_size'])) : 20;
            $pageNum = isset($params['page_num']) ? max(1, (int)$params['page_num']) : 1;
            $offset = ($pageNum - 1) * $pageSize;

            // 执行查询
            $result = $query->order('update_time', 'desc')
                ->limit($offset, $pageSize)
                ->select()
                ->toArray();

            LogService::log("分类查询成功，返回 " . count($result) . " 条记录");

            return $result;

        } catch (\Exception $e) {
            LogService::error($e);
            // 不要返回空数组，抛出异常让控制器处理
            throw $e;
        }
    }

    /**
     * 更新分类
     * @param array $params 分类数据
     * @return array
     */
    public static function updateCategory(array $params): array
    {
        try {
            if (empty($params['id'])) {
                return ['code' => 400, 'msg' => '分类ID不能为空'];
            }

            $id = $params['id'];

            // 查询分类是否存在
            $category = categoryModel::find($id);
            if (!$category) {
                LogService::log("更新分类失败，ID不存在：{$id}", [], 'warning');
                return ['code' => 404, 'msg' => '分类不存在'];
            }

            // 过滤不需要更新的字段
            $updateData = [];
            $allowFields = [
                'name', 'slug', 'type', 'description', 'sort_order', 'icon',
                'status', 'parent_id', 'meta_title', 'meta_keywords',
                'meta_description', 'user_id', 'cover_image'
            ];

            foreach ($allowFields as $field) {
                if (isset($params[$field])) {
                    $updateData[$field] = $params[$field];
                }
            }

            // 移除不应该被更新的字段
            unset($updateData['id'], $updateData['create_time'], $updateData['update_time'],
                  $updateData['delete_time'], $updateData['author'], $updateData['category_type_text'],
                  $updateData['count']);

            // 执行更新（模型修改器会自动处理布尔值转换）
            $category->save($updateData);

            LogService::log("更新分类成功，ID：{$id}，名称：{$category->name}");

            return [
                'code' => 200,
                'msg' => '更新成功',
                'data' => $category
            ];

        } catch (\Exception $e) {
            LogService::error($e);
            return ['code' => 500, 'msg' => '更新失败：' . $e->getMessage()];
        }
    }

    /**
     * 添加分类（纯模型方式）
     * @param array $params 分类数据
     * @return array 返回标准化结果
     */
    public static function addCategory(array $params): array
    {
        try {
            // 1. 基础验证（模型层验证更优雅）
            if (empty($params['name'])) {
                throw new \InvalidArgumentException('分类名称不能为空');
            }
            unset($params['version']);
            // 2. 使用模型静态创建
            $category = categoryModel::create(
                array_merge($params, [
                    'status' => $params['status'] ?? 1, // 默认值
                    'sort_order' => $params['sort_order'] ?? 0
                ])
            );

            // 3. 返回标准化结果
            return [
                'success' => true,
                'data' => ['id' => $category->id],
                'message' => '添加成功'
            ];

        } catch (\think\exception\ValidateException $e) {
            // 模型验证错误
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 400
            ];
        } catch (\Exception $e) {
            // 系统错误记录日志
            \think\facade\Log::error('分类添加失败: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => '操作失败，请稍后重试',
                'error_code' => 500
            ];
        }
    }

    /**
     * 根据ID查询分类详情
     * @param int $id 分类ID
     * @return array
     */
    public static function selectCategoryById(int $id): array
    {
        try {
            $category = categoryModel::with(['author', 'parent', 'children'])
                ->find($id);

            if (!$category) {
                LogService::log("查询分类失败，ID不存在：{$id}", [], 'warning');
                return ['code' => 404, 'msg' => '分类不存在'];
            }

            LogService::log("查询分类详情成功，ID：{$id}，名称：{$category->name}");

            return [
                'code' => 200,
                'msg' => '获取分类详情成功',
                'data' => $category
            ];
        } catch (\Exception $e) {
            LogService::error($e);
            return ['code' => 500, 'msg' => '查询失败：' . $e->getMessage()];
        }
    }

    /**
     * 删除分类
     * @param int $id 分类ID
     * @param bool $real 是否物理删除
     * @return array
     */
    public static function deleteCategory(int $id, bool $real = false): array
    {
        try {
            if ($real) {
                // 物理删除：查找包括已软删除的记录
                $category = categoryModel::withTrashed()->find($id);
                if (!$category) {
                    LogService::log("物理删除分类失败，ID不存在：{$id}", [], 'warning');
                    return ['code' => 404, 'msg' => '分类不存在'];
                }
                
                // 物理删除前也需要检查关联
                $checkResult = self::checkCategoryRelations($id);
                if (!$checkResult['can_delete']) {
                    return [
                        'code' => 400,
                        'msg' => $checkResult['message']
                    ];
                }

                // 记录分类名称（在删除前）
                $categoryName = $category->getData('name');

                // 执行物理删除
                $category->force(true)->delete();
                LogService::log("物理删除分类成功，ID：{$id}，名称：{$categoryName}");

                return [
                    'code' => 200,
                    'msg' => '物理删除成功'
                ];
            } else {
                // 软删除：只查找未删除的记录
                $category = categoryModel::find($id);
                if (!$category) {
                    LogService::log("软删除分类失败，ID不存在：{$id}", [], 'warning');
                    return ['code' => 404, 'msg' => '分类不存在'];
                }
                
                // 软删除前检查关联
                $checkResult = self::checkCategoryRelations($id);
                if (!$checkResult['can_delete']) {
                    return [
                        'code' => 400,
                        'msg' => $checkResult['message']
                    ];
                }

                // 记录分类名称（在删除前）
                $categoryName = $category->getData('name');

                // 执行软删除
                $category->delete();
                LogService::log("软删除分类成功，ID：{$id}，名称：{$categoryName}");

                return [
                    'code' => 200,
                    'msg' => '软删除成功'
                ];
            }
        } catch (\Exception $e) {
            LogService::error($e);
            return ['code' => 500, 'msg' => '删除失败：' . $e->getMessage()];
        }
    }

    /**
     * 恢复已删除的分类
     * @param int $id 分类ID
     * @return array
     */
    public static function restoreCategory(int $id): array
    {
        try {
            $category = categoryModel::onlyTrashed()->find($id);
            if (!$category) {
                LogService::log("恢复分类失败，ID不存在或未删除：{$id}", [], 'warning');
                return ['code' => 404, 'msg' => '分类不存在或未删除'];
            }

            $category->restore();
            LogService::log("恢复分类成功，ID：{$id}，名称：{$category->name}");

            return [
                'code' => 200,
                'msg' => '恢复成功',
                'data' => $category
            ];
        } catch (\Exception $e) {
            LogService::error($e);
            return ['code' => 500, 'msg' => '恢复失败：' . $e->getMessage()];
        }
    }

    /**
     * 批量恢复分类
     * @param array $ids 分类ID数组
     * @return array
     */
    public static function batchRestoreCategory(array $ids): array
    {
        try {
            Db::startTrans();

            $successCount = 0;
            $failedIds = [];
            $results = [];

            foreach ($ids as $id) {
                try {
                    $result = self::restoreCategory($id);

                    if ($result['code'] === 200) {
                        $successCount++;
                        $results[] = ['id' => $id, 'status' => 'success'];
                    } else {
                        $failedIds[] = $id;
                        $results[] = ['id' => $id, 'status' => 'failed', 'message' => $result['msg']];
                    }
                } catch (\Exception $e) {
                    $failedIds[] = $id;
                    $results[] = ['id' => $id, 'status' => 'failed', 'message' => $e->getMessage()];
                }
            }

            Db::commit();

            $totalCount = count($ids);

            if ($successCount === $totalCount) {
                return [
                    'success' => true,
                    'message' => "批量恢复成功，共处理 {$totalCount} 个分类",
                    'data' => [
                        'total' => $totalCount,
                        'success' => $successCount,
                        'failed' => count($failedIds),
                        'results' => $results
                    ]
                ];
            } elseif ($successCount > 0) {
                return [
                    'success' => true,
                    'message' => "批量恢复部分成功，成功 {$successCount} 个，失败 " . count($failedIds) . " 个",
                    'data' => [
                        'total' => $totalCount,
                        'success' => $successCount,
                        'failed' => count($failedIds),
                        'failed_ids' => $failedIds,
                        'results' => $results
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'message' => "批量恢复失败，所有分类都处理失败",
                    'data' => [
                        'total' => $totalCount,
                        'success' => 0,
                        'failed' => count($failedIds),
                        'failed_ids' => $failedIds,
                        'results' => $results
                    ]
                ];
            }

        } catch (\Exception $e) {
            Db::rollback();
            LogService::error($e);

            return [
                'success' => false,
                'message' => '批量恢复失败: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * 获取已删除的分类列表
     * @param array $params 查询参数
     * @return array
     */
    public static function getDeletedCategories(array $params = []): array
    {
        try {
            // 强制查询已删除的分类
            $params['delete_status'] = 'only_deleted';
            return self::selectCategoryAll($params);
        } catch (\Exception $e) {
            LogService::error($e);
            return [];
        }
    }

    /**
     * 检查分类关联关系
     * @param int $id 分类ID
     * @return array
     */
    private static function checkCategoryRelations(int $id): array
    {
        try {
            // 检查是否有文章使用此分类
            $articleCount = \app\api\model\article::where('category_id', $id)->count();
            if ($articleCount > 0) {
                return [
                    'can_delete' => false,
                    'message' => "该分类下有 {$articleCount} 篇文章，无法删除"
                ];
            }
            
            // 检查是否有资源使用此分类
            $resourceCount = \app\api\model\resource::where('category_id', $id)->count();
            if ($resourceCount > 0) {
                return [
                    'can_delete' => false,
                    'message' => "该分类下有 {$resourceCount} 个资源，无法删除"
                ];
            }
            
            // 检查是否有子分类
            $childCount = categoryModel::where('parent_id', $id)->count();
            if ($childCount > 0) {
                return [
                    'can_delete' => false,
                    'message' => "该分类下有 {$childCount} 个子分类，无法删除"
                ];
            }
            
            return [
                'can_delete' => true,
                'message' => '可以删除'
            ];
        } catch (\Exception $e) {
            LogService::error($e);
            return [
                'can_delete' => false,
                'message' => '检查关联关系失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 批量删除分类
     * @param array $ids 分类ID数组
     * @param bool $isPhysical 是否物理删除
     * @return array
     */
    public static function batchDeleteCategory(array $ids, bool $isPhysical = false): array
    {
        try {
            Db::startTrans();

            $successCount = 0;
            $failedIds = [];
            $results = [];

            foreach ($ids as $id) {
                try {
                    $result = self::deleteCategory($id, $isPhysical);

                    if ($result['code'] === 200) {
                        $successCount++;
                        $results[] = ['id' => $id, 'status' => 'success'];
                    } else {
                        $failedIds[] = $id;
                        $results[] = ['id' => $id, 'status' => 'failed', 'message' => $result['msg']];
                    }
                } catch (\Exception $e) {
                    $failedIds[] = $id;
                    $results[] = ['id' => $id, 'status' => 'failed', 'message' => $e->getMessage()];
                }
            }

            Db::commit();

            $totalCount = count($ids);
            $deleteType = $isPhysical ? '物理删除' : '软删除';

            if ($successCount === $totalCount) {
                return [
                    'success' => true,
                    'message' => "批量{$deleteType}成功，共处理 {$totalCount} 个分类",
                    'data' => [
                        'total' => $totalCount,
                        'success' => $successCount,
                        'failed' => count($failedIds),
                        'results' => $results
                    ]
                ];
            } elseif ($successCount > 0) {
                return [
                    'success' => true,
                    'message' => "批量{$deleteType}部分成功，成功 {$successCount} 个，失败 " . count($failedIds) . " 个",
                    'data' => [
                        'total' => $totalCount,
                        'success' => $successCount,
                        'failed' => count($failedIds),
                        'failed_ids' => $failedIds,
                        'results' => $results
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'message' => "批量{$deleteType}失败，所有分类都处理失败",
                    'data' => [
                        'total' => $totalCount,
                        'success' => 0,
                        'failed' => count($failedIds),
                        'failed_ids' => $failedIds,
                        'results' => $results
                    ]
                ];
            }

        } catch (\Exception $e) {
            Db::rollback();
            LogService::error($e);

            return [
                'success' => false,
                'message' => '批量删除失败: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
}