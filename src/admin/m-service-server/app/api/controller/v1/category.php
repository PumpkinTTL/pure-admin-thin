<?php

namespace app\api\controller\v1;

use app\api\services\categoryService;
use app\api\services\LogService;
use app\BaseController;
use Exception;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;


class category extends BaseController
{
    /**
     * 获取分类列表
     */
    public function selectCategoryAll()
    {
        try {
            $params = request()->param();

            // 处理软删除数据查询
            $includeDeleted = isset($params['include_deleted']) &&
                             ($params['include_deleted'] === true ||
                              $params['include_deleted'] === 'true' ||
                              $params['include_deleted'] === 1 ||
                              $params['include_deleted'] === '1');

            // 调试日志
            LogService::log('Category查询参数', [
                'include_deleted' => $params['include_deleted'] ?? 'not_set',
                'include_deleted_type' => gettype($params['include_deleted'] ?? null),
                'includeDeleted' => $includeDeleted,
                'delete_status' => $params['delete_status'] ?? 'not_set'
            ]);

            if ($includeDeleted) {
                $params['delete_status'] = 'only_deleted';
            }

            $data = categoryService::selectCategoryAll($params);

            // 调试信息：记录返回数据数量
            LogService::log("分类查询返回数据数量：" . count($data));

            return json([
                'code' => 200,
                'msg' => '获取分类列表成功',
                'data' => [
                    'list' => $data,
                    'pagination' => [
                        'total' => count($data),
                        'current' => $params['page_num'] ?? 1,
                        'page_size' => $params['page_size'] ?? 20
                    ]
                ]
            ]);
        } catch (Exception $e) {
            LogService::error($e);
            return json([
                'code' => 500,
                'msg' => '获取分类列表失败：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }

    /**
     * 根据ID查询分类详情
     */
    public function selectCategoryById()
    {
        $id = request()->param('id');
        $result = categoryService::selectCategoryById($id);
        return json($result);
    }

    /**
     * 新增分类
     */
    public function add()
    {
        $params = request()->param();
        $data = categoryService::addCategory($params);

        if ($data['success']) {
            return json([
                'code' => 200,
                'msg' => $data['message'],
                'data' => $data['data']
            ]);
        } else {
            return json([
                'code' => $data['error_code'] ?? 500,
                'msg' => $data['message'],
                'data' => null
            ]);
        }
    }

    /**
     * 更新分类
     */
    public function update(): \think\response\Json
    {
        $params = request()->param();
        $result = categoryService::updateCategory($params);
        return json($result);
    }

    /**
     * 删除分类（支持单个和批量删除）
     */
    public function delete()
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
                'msg' => '请提供要删除的分类ID'
            ]);
        }

        // 检查是否物理删除
        $isPhysical = isset($params['real']) && $params['real'] === true;

        // 批量删除
        $result = categoryService::batchDeleteCategory($ids, $isPhysical);

        return json([
            'code' => $result['success'] ? 200 : 500,
            'msg' => $result['message'],
            'data' => $result['data'] ?? null
        ]);
    }

    /**
     * 恢复分类
     */
    public function restore()
    {
        $id = request()->param('id');
        $result = categoryService::restoreCategory($id);
        return json($result);
    }

    /**
     * 获取已删除分类列表
     */
    public function getDeletedCategories()
    {
        $params = request()->param();
        $data = categoryService::getDeletedCategories($params);
        return json([
            'code' => 200,
            'msg' => '获取已删除分类列表成功',
            'data' => [
                'list' => $data,
                'pagination' => [
                    'total' => count($data),
                    'current' => $params['page_num'] ?? 1,
                    'page_size' => $params['page_size'] ?? 20
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
        $normalCount = \app\api\model\category::count();
        $deletedCount = \app\api\model\category::onlyTrashed()->count();
        $allCount = \app\api\model\category::withTrashed()->count();

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
     * 测试删除功能
     */
    public function testDelete()
    {
        $params = request()->param();
        $id = $params['id'] ?? null;

        if (!$id) {
            return json([
                'code' => 400,
                'msg' => '请提供分类ID'
            ]);
        }

        // 测试查找分类的不同方式
        $normalFind = \app\api\model\category::find($id);
        $withTrashedFind = \app\api\model\category::withTrashed()->find($id);
        $onlyTrashedFind = \app\api\model\category::onlyTrashed()->find($id);

        return json([
            'code' => 200,
            'msg' => '删除测试',
            'data' => [
                'id' => $id,
                'normal_find' => $normalFind ? '找到' : '未找到',
                'with_trashed_find' => $withTrashedFind ? '找到' : '未找到',
                'only_trashed_find' => $onlyTrashedFind ? '找到' : '未找到',
                'normal_find_data' => $normalFind ? [
                    'id' => $normalFind->id,
                    'name' => $normalFind->getData('name'),
                    'delete_time' => $normalFind->getData('delete_time')
                ] : null,
                'with_trashed_find_data' => $withTrashedFind ? [
                    'id' => $withTrashedFind->id,
                    'name' => $withTrashedFind->getData('name'),
                    'delete_time' => $withTrashedFind->getData('delete_time')
                ] : null
            ]
        ]);
    }

}