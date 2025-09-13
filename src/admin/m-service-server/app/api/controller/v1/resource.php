<?php

namespace app\api\controller\v1;

use app\api\services\resourceService;
use app\BaseController;
use think\response\Json;
use think\Validate;

class resource extends BaseController
{
    /**
     * 查询所有资源
     * @return Json
     */
    public function selectResourceAll(): Json
    {
        $params = request()->param();

        // 处理软删除数据查询
        $includeDeleted = isset($params['include_deleted']) &&
                         ($params['include_deleted'] === true ||
                          $params['include_deleted'] === 'true' ||
                          $params['include_deleted'] === 1 ||
                          $params['include_deleted'] === '1');

        // 调试日志
        \think\facade\Log::info('Resource查询参数', [
            'include_deleted' => $params['include_deleted'] ?? 'not_set',
            'include_deleted_type' => gettype($params['include_deleted'] ?? null),
            'includeDeleted' => $includeDeleted,
            'delete_status' => $params['delete_status'] ?? 'not_set'
        ]);

        if ($includeDeleted) {
            $params['delete_status'] = 'only_deleted';
        }

        $data = resourceService::selectResourceAll($params);

        return json([
            'code' => 200,
            'msg' => '获取资源列表成功',
            'data' => [
                'list' => $data->items() ?? $data,
                'pagination' => [
                    'total' => method_exists($data, 'total') ? $data->total() : count($data),
                    'current' => $params['page'] ?? $params['page_num'] ?? 1,
                    'page_size' => $params['page_size'] ?? 100
                ]
            ]
        ]);
    }

    /**
     * 测试软删除查询
     */
    public function testSoftDelete()
    {
        $params = request()->param();

        // 直接测试软删除查询
        $normalCount = \app\api\model\resource::count();
        $deletedCount = \app\api\model\resource::onlyTrashed()->count();
        $allCount = \app\api\model\resource::withTrashed()->count();

        return json([
            'code' => 200,
            'msg' => '软删除测试',
            'data' => [
                'normal_count' => $normalCount,
                'deleted_count' => $deletedCount,
                'all_count' => $allCount,
                'params' => $params
            ]
        ]);
    }

    /**
     * 创建资源
     * @return Json
     */
    public function add()
    {
        $data = request()->param();

        // 验证数据格式
        $validate = Validate([
            'resource.resource_name' => 'require',
            'resource.category_id' => 'require|number',
            'downloadMethods' => 'array',
            'resourceTags' => 'array'
        ]);

        if (!$validate->check($data)) {
            return json([
                'code' => 400,
                'message' => $validate->getError()
            ]);
        }

        $result = resourceService::createResource($data);

        return json([
            'code' => $result['success'] ? 200 : 500,
            'data' => $result['data'] ?? null,
            'message' => $result['message']
        ]);
    }

    /**
     * 根据ID查询资源详情
     * @return Json
     */
    public function selectResourceById(): Json
    {
        $id = request()->param('id');
        $result = resourceService::selectResourceById($id);

        return json([
            'code' => $result['success'] ? 200 : 500,
            'data' => $result['data'] ?? null,
            'message' => $result['message']
        ]);
    }

    /**
     * 删除资源（支持单个和批量删除）
     * @return Json
     */
    public function deleteResource(): Json
    {
        $params = request()->param();

        // 获取要删除的ID（支持单个ID或ID数组）
        $ids = [];
        if (isset($params['id'])) {
            $ids = is_array($params['id']) ? $params['id'] : [$params['id']];
        } elseif (isset($params['ids'])) {
            $ids = is_array($params['ids']) ? $params['ids'] : [$params['ids']];
        }

        if (empty($ids)) {
            return json([
                'code' => 400,
                'msg' => '请提供要删除的资源ID'
            ]);
        }

        // 检查是否物理删除
        $isPhysical = isset($params['real']) && $params['real'] === true;

        // 批量删除
        $result = resourceService::batchDeleteResource($ids, $isPhysical);

        return json([
            'code' => $result['success'] ? 200 : 500,
            'msg' => $result['message'],
            'data' => $result['data'] ?? null
        ]);
    }
    
    /**
     * 恢复被软删除的资源
     * @return Json
     */
    public function restore(): Json
    {
        $id = request()->param('id');
        $result = resourceService::restoreResource($id);

        return json([
            'code' => $result['success'] ? 200 : 500,
            'message' => $result['message']
        ]);
    }
    
    /**
     * 获取已删除的资源列表
     * @return Json
     */
    public function getDeletedResources(): Json
    {
        $params = request()->param();
        $data = resourceService::getDeletedResources($params);

        return json([
            'code' => 200,
            'msg' => '获取已删除资源列表成功',
            'data' => [
                'list' => $data->items() ?? $data,
                'pagination' => [
                    'total' => method_exists($data, 'total') ? $data->total() : count($data),
                    'current' => $params['page'] ?? $params['page_num'] ?? 1,
                    'page_size' => $params['page_size'] ?? 100
                ]
            ]
        ]);
    }
    
    /**
     * 彻底删除资源
     * @return Json
     */
    public function forceDeleteResource(): Json
    {
        $id = request()->param('id');
        $result = resourceService::forceDeleteResource($id);

        return json([
            'code' => $result['success'] ? 200 : 500,
            'message' => $result['message']
        ]);
    }
    
    /**
     * 更新资源
     * @return Json
     */
    public function update(): Json
    {
        $data = request()->param();

        // 验证数据格式
        $validate = Validate([
            'resource' => 'require|array',
            'resource.id' => 'require|number',
            'resource.resource_name' => 'require',
            'resource.category_id' => 'require|number',
            'downloadMethods' => 'array',
            'resourceTags' => 'array'
        ]);

        if (!$validate->check($data)) {
            return json([
                'code' => 400,
                'message' => $validate->getError()
            ]);
        }

        $id = $data['resource']['id'];
        $result = resourceService::updateResource($id, $data);
        return json([
            'code' => $result['success'] ? 200 : 500,
            'data' => $result['data'] ?? null,
            'msg' => $result['message']
        ]);
    }
}