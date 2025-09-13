<?php

namespace app\api\services;

use app\api\model\resource;
use think\facade\Request;
use think\facade\Db;
// 启用日志
use think\facade\Log;
class resourceService
{
    public static function selectResourceAll($params)
    {
        // 默认每页100条
        $pageSize = $params['page_size'] ?? 100;
        $page = $params['page'] ?? $params['page_num'] ?? 1;

        // 构建基础查询
        $query = resource::with([
            'downloadLinks' => function ($query) {
                $query->field(['id', 'resource_id', 'method_name', 'download_link', 'extraction_code']);
            },
            'author' => function ($query) {
                $query->field(['id', 'username', 'avatar']);
            },
            'tags' => function ($query) {
                $query->field(['name']);
            },
            'category' => function ($query) {

            }
        ]);

        // 动态条件查询
        $query = self::buildDynamicConditions($query, $params);

        // 处理软删除查询
        if (isset($params['delete_status'])) {
            \think\facade\Log::info('ResourceService软删除查询', [
                'delete_status' => $params['delete_status']
            ]);

            if ($params['delete_status'] === 'only_deleted') {
                // 只查询已删除的数据
                $query->onlyTrashed();
                \think\facade\Log::info('ResourceService: 使用onlyTrashed()查询');
            } elseif ($params['delete_status'] === 'with_deleted') {
                // 查询所有数据（包括已删除和未删除的）
                $query->withTrashed();
                \think\facade\Log::info('ResourceService: 使用withTrashed()查询');
            }
            // 默认不传参数只查询未删除的数据
        } else {
            \think\facade\Log::info('ResourceService: 使用默认查询（未删除数据）');
        }
        // 分页查询
        return $query->paginate([
            'list_rows' => $pageSize,
            'page' => $page,
            'query' => Request::get()
        ]);
    }

    /**
     * 构建动态查询条件
     */
    protected static function buildDynamicConditions($query, $params)
    {
        // 资源名称模糊查询
        if (!empty($params['resource_name'])) {
            $query->where('resource_name', 'like', '%' . $params['resource_name'] . '%');
        }

        // 分类ID精确查询
        if (!empty($params['category_id'])) {
            $query->where('category_id', $params['category_id']);
        }

        // 平台精确查询
        if (!empty($params['platform'])) {
            $query->where('platform', $params['platform']);
        }

        // 数据库状态查询（非软删除状态）
        if (!empty($params['db_status'])) {
            $query->where('status', $params['db_status']);
        }

        // 是否付费精确查询
        if (isset($params['is_premium']) && $params['is_premium'] !== '') {
            $query->where('is_premium', (int) $params['is_premium']);
        }

        // 排序处理（示例：支持create_time排序）
        if (!empty($params['sort_field']) && !empty($params['sort_order'])) {
            $query->order($params['sort_field'], $params['sort_order']);
        } else {
            // 默认排序
            $query->order('publish_time', 'desc');
        }

        return $query;
    }

    /**
     * 创建资源（包含下载方式和标签）
     * @param array $data 资源数据
     * @return array
     */
    public static function createResource(array $data): array
    {
        try {
            Db::startTrans();

            // 转换日期格式
            $data['resource']['update_time'] = date('Y-m-d H:i:s', strtotime($data['resource']['update_time']));

            // 1. 创建主资源
            $resourceData = $data['resource'];
            $resource = resource::create($resourceData);

            // 2. 创建下载方式
            if (!empty($data['downloadMethods'])) {
                $downloadMethods = array_map(function ($method) use ($resource) {
                    $method['resource_id'] = $resource->id;
                    return $method;
                }, $data['downloadMethods']);

                Db::name('download_method')->insertAll($downloadMethods);
            }

            // 3. 创建资源标签关联
            if (!empty($data['resourceTags'])) {
                $tags = array_map(function ($tag) use ($resource) {
                    $tag['resource_id'] = $resource->id;
                    return $tag;
                }, $data['resourceTags']);

                Db::name('resource_tag')->insertAll($tags);
            }

            // 提交事务
            Db::commit();

            return [
                'success' => true,
                'data' => ['id' => $resource->id],
                'message' => '资源创建成功'
            ];

        } catch (\Throwable $e) {
            // 回滚事务
            Db::rollback();

            // 记录错误日志
            Log::error('资源创建失败: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => '资源创建失败: ' . $e->getMessage(),
                'error_code' => 500
            ];
        }
    }

    /**
     * 根据ID获取资源详情（使用模型关联查询）
     * @param int $id 资源ID
     * @return array
     */
    public static function selectResourceById(int $id): array
    {
        try {
            // 使用with关联查询
            $resource = resource::with(['downloadLinks', 'tags', 'author', 'category'])
                ->find($id);

            if (!$resource) {
                throw new \think\Exception('资源不存在');
            }

            return [
                'success' => true,
                'data' => $resource,
                'message' => '查询成功'
            ];

        } catch (\Throwable $e) {
            // 记录错误日志
            Log::error('资源查询失败: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => '资源查询失败: ' . $e->getMessage(),
                'error_code' => 500
            ];
        }
    }

    /**
     * 软删除资源
     * @param int $id 资源ID
     * @param bool $deleteRelated 是否同时删除关联数据
     * @return array
     */
    public static function deleteResource(int $id, bool $deleteRelated = false): array
    {
        try {
            Db::startTrans();
            
            // 1. 查找资源
            $resource = resource::find($id);
            if (!$resource) {
                throw new \think\Exception('资源不存在');
            }
            
            // 2. 删除资源（根据deleteRelated决定是软删除还是物理删除）
            if ($deleteRelated) {
                // 如果需要删除关联数据，同时物理删除资源本身
                // 先删除关联数据
                Db::name('download_method')->where('resource_id', $id)->delete(true); // 强制删除
                Db::name('resource_tag')->where('resource_id', $id)->delete(true); // 强制删除
                
                // 物理删除资源
                $resource->force()->delete();
                Log::info('资源及关联数据物理删除成功', ['id' => $id]);
            } else {
                // 仅软删除资源本身
                $resource->delete();
                Log::info('仅资源软删除成功，保留关联数据', ['id' => $id]);
            }

            Db::commit();
            
            return [
                'success' => true,
                'message' => $deleteRelated ? '资源及关联数据永久删除成功' : '资源删除成功，保留关联数据'
            ];
            
        } catch (\Throwable $e) {
            Db::rollback();
            
            Log::error('资源删除失败: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => '资源删除失败: ' . $e->getMessage(),
                'error_code' => 500
            ];
        }
    }

    /**
     * 比较两个数组的差异
     * @param array $oldData 旧数据
     * @param array $newData 新数据
     * @param array $fields 需要比较的字段
     * @return bool 返回是否有差异
     */
    protected static function hasArrayDiff($oldData, $newData, $fields)
    {
        if (count($oldData) != count($newData)) {
            return true;
        }
        
        foreach ($oldData as $index => $oldItem) {
            if (!isset($newData[$index])) {
                return true;
            }
            
            $newItem = $newData[$index];
            foreach ($fields as $field) {
                if (!isset($oldItem[$field]) || !isset($newItem[$field]) || $oldItem[$field] != $newItem[$field]) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * 更新资源（包含下载方式和标签）
     * @param int $id 资源ID
     * @param array $data 更新数据
     * @return array
     */
    public static function updateResource(int $id, $data): array
    {
        try {
            Db::startTrans();
            
            // 是否有数据变化的标志
            $hasChanges = false;
            
            // 1. 检查资源是否存在
            $resource = resource::with(['downloadLinks', 'tags'])->find($id);
            if (!$resource) {
                throw new \think\Exception('资源不存在');
            }
            
            // 2. 更新主资源
            if (!empty($data['resource'])) {
                $resourceData = $data['resource'];
                
                // 移除不应更新的字段
                unset($resourceData['tags'], $resourceData['id']);
                
                // 处理日期字段
                try {
                    if (!empty($resourceData['update_time'])) {
                        $resourceData['update_time'] = date('Y-m-d H:i:s', strtotime($resourceData['update_time']));
                    }
                    if (!empty($resourceData['publish_time'])) {
                        $resourceData['publish_time'] = date('Y-m-d H:i:s', strtotime($resourceData['publish_time']));
                    }
                } catch (\Throwable $e) {
                    Log::error('日期转换失败', [
                        'error' => $e->getMessage()
                    ]);
                }
                
                // 比较资源数据是否有变化
                $resourceChanged = false;
                $resourceArray = $resource->toArray();
                
                foreach ($resourceData as $key => $value) {
                    if (!isset($resourceArray[$key]) || $resourceArray[$key] != $value) {
                        $resourceChanged = true;
                        break;
                    }
                }
                
                // 只有在资源数据有变化时才更新
                if ($resourceChanged) {
                    $resource->save($resourceData);
                    $hasChanges = true;
                    Log::info('资源主表数据已更新', ['id' => $id]);
                } else {
                    Log::info('资源主表数据无变化，跳过更新', ['id' => $id]);
                }
            }
            
            // 4. 更新下载方式
            $downloadMethodsData = $data['downloadMethods'] ?? [];
            if (!empty($downloadMethodsData)) {
                // 获取现有下载方式
                $existingMethods = $resource->downloadLinks ? $resource->downloadLinks->toArray() : [];
                
                // 检查下载方式是否有变化
                $methodChanged = false;
                
                // 数量不同，肯定有变化
                if (count($existingMethods) != count($downloadMethodsData)) {
                    $methodChanged = true;
                } else {
                    // 比较每个下载方式的关键字段
                    $methodFields = ['method_name', 'download_link', 'extraction_code', 'status', 'sort_order'];
                    
                    // 对现有方法和新方法进行排序，以便比较
                    usort($existingMethods, function($a, $b) {
                        return $a['id'] <=> $b['id'];
                    });
                    
                    usort($downloadMethodsData, function($a, $b) {
                        return ($a['id'] ?? 0) <=> ($b['id'] ?? 0);
                    });
                    
                    // 使用辅助方法比较数组差异
                    $methodChanged = self::hasArrayDiff($existingMethods, $downloadMethodsData, $methodFields);
                }
                
                // 只有在下载方式有变化时才更新
                if ($methodChanged) {
                    // 删除旧下载方式
                    Db::name('download_method')->where('resource_id', $id)->delete();
                    
                    // 直接使用前端传来的数据，不做任何处理
                    Db::name('download_method')->insertAll($downloadMethodsData);
                    
                    $hasChanges = true;
                    Log::info('下载方式已更新', ['count' => count($downloadMethodsData)]);
                } else {
                    Log::info('下载方式无变化，跳过更新');
                }
            }
            
            // 3. 更新资源标签
            $tagsData = $data['resourceTags'] ?? [];
            if (!empty($tagsData)) {
                // 获取现有标签
                $existingTags = [];
                if ($resource->tags) {
                    foreach ($resource->tags as $tag) {
                        $existingTags[] = $tag['pivot']['category_id'];
                    }
                }
                
                // 前端传来的标签ID
                $newTagIds = [];
                foreach ($tagsData as $tag) {
                    $newTagIds[] = $tag['category_id'];
                }
                
                // 排序并比较标签ID
                sort($existingTags);
                sort($newTagIds);
                
                // 只有在标签有变化时才更新
                if (implode(',', $existingTags) !== implode(',', $newTagIds)) {
                    // 先删除旧标签
                    Db::name('resource_tag')->where('resource_id', $id)->delete();
                    
                    // 直接使用前端传来的数据，不做任何处理
                    Db::name('resource_tag')->insertAll($tagsData);
                    
                    $hasChanges = true;
                    Log::info('资源标签已更新', ['count' => count($tagsData)]);
                } else {
                    Log::info('资源标签无变化，跳过更新');
                }
            }
            
            Db::commit();
            
            return [
                'success' => true,
                'data' => ['id' => $id],
                'message' => $hasChanges ? '资源更新成功' : '资源无变化，跳过更新'
            ];
            
        } catch (\Throwable $e) {
            Db::rollback();
            Log::error('资源更新失败: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'success' => false,
                'message' => '资源更新失败: ' . $e->getMessage(),
                'error_code' => 500
            ];
        }
    }

    /**
     * 恢复被软删除的资源
     * @param int $id 资源ID
     * @return array
     */
    public static function restoreResource(int $id): array
    {
        try {
            // 查找被软删除的资源
            $resource = resource::onlyTrashed()->find($id);
            
            if (!$resource) {
                throw new \think\Exception('找不到已删除的资源或资源不存在');
            }
            
            // 恢复资源
            $resource->restore();
            
            // 记录日志
            Log::info('资源恢复成功', ['id' => $id]);
            
            return [
                'success' => true,
                'message' => '资源恢复成功'
            ];
            
        } catch (\Throwable $e) {
            // 记录错误日志
            Log::error('资源恢复失败: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => '资源恢复失败: ' . $e->getMessage(),
                'error_code' => 500
            ];
        }
    }
    
    /**
     * 彻底删除资源
     * @param int $id 资源ID
     * @return array
     */
    public static function forceDeleteResource(int $id): array
    {
        try {
            Db::startTrans();
            
            // 查找资源（包括已软删除的）
            $resource = resource::withTrashed()->find($id);
            
            if (!$resource) {
                throw new \think\Exception('资源不存在');
            }
            
            // 删除关联数据
            Db::name('download_method')->where('resource_id', $id)->delete(true); // 强制删除
            Db::name('resource_tag')->where('resource_id', $id)->delete(true); // 强制删除
            
            // 彻底删除资源
            $resource->force()->delete();
            
            Db::commit();
            
            // 记录日志
            Log::info('资源彻底删除成功', ['id' => $id]);
            
            return [
                'success' => true,
                'message' => '资源彻底删除成功'
            ];
            
        } catch (\Throwable $e) {
            // 回滚事务
            Db::rollback();
            
            // 记录错误日志
            Log::error('资源彻底删除失败: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => '资源彻底删除失败: ' . $e->getMessage(),
                'error_code' => 500
            ];
        }
    }
    
    /**
     * 获取已删除的资源列表
     * @param array $params 查询参数
     * @return \think\Paginator
     */
    public static function getDeletedResources(array $params = [])
    {
        // 默认每页100条
        $pageSize = $params['page_size'] ?? 100;
        $page = $params['page'] ?? 1;
        
        // 构建基础查询 - 只查询已软删除的资源
        $query = resource::onlyTrashed()->with([
            'downloadLinks' => function ($query) {
                $query->field(['id', 'resource_id', 'method_name', 'download_link', 'extraction_code']);
            },
            'author' => function ($query) {
                $query->field(['id', 'username', 'avatar']);
            },
            'tags' => function ($query) {
                $query->field(['name']);
            },
            'category' => function ($query) {
                // 加载分类信息
            }
        ]);
        
        // 动态条件查询
        $query = self::buildDynamicConditions($query, $params);
        
        // 分页查询
        return $query->paginate([
            'list_rows' => $pageSize,
            'page' => $page,
            'query' => Request::get()
        ]);
    }

    /**
     * 批量删除资源
     * @param array $ids 资源ID数组
     * @param bool $isPhysical 是否物理删除
     * @return array
     */
    public static function batchDeleteResource(array $ids, bool $isPhysical = false): array
    {
        try {
            Db::startTrans();

            $successCount = 0;
            $failedIds = [];
            $results = [];

            foreach ($ids as $id) {
                try {
                    if ($isPhysical) {
                        // 物理删除
                        $result = self::forceDeleteResource($id);
                    } else {
                        // 软删除
                        $result = self::deleteResource($id, false);
                    }

                    if ($result['success']) {
                        $successCount++;
                        $results[] = ['id' => $id, 'status' => 'success'];
                    } else {
                        $failedIds[] = $id;
                        $results[] = ['id' => $id, 'status' => 'failed', 'message' => $result['message']];
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
                    'message' => "批量{$deleteType}成功，共处理 {$totalCount} 个资源",
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
                    'message' => "批量{$deleteType}失败，所有资源都处理失败",
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
            Log::error('批量删除资源失败: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => '批量删除失败: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
}