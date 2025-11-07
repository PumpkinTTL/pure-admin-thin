<?php

namespace app\api\controller\v1;

use app\api\services\DonationService;
use app\BaseController;
use think\response\Json;

class Donation extends BaseController
{
    /**
     * 获取捐赠记录列表
     * 
     * GET /api/v1/donation/list
     * 
     * @return Json
     */
    public function getList(): Json
    {
        $params = request()->param();
        $result = DonationService::getList($params);

        return json($result);
    }

    /**
     * 获取捐赠记录详情
     * 
     * GET /api/v1/donation/detail
     * 
     * @return Json
     */
    public function getDetail(): Json
    {
        $id = request()->param('id');

        if (empty($id)) {
            return json([
                'code' => 400,
                'data' => null,
                'message' => '缺少参数id'
            ]);
        }

        $result = DonationService::getDetail((int)$id);

        return json($result);
    }

    /**
     * 添加捐赠记录
     * 
     * POST /api/v1/donation/add
     * 
     * @return Json
     */
    public function add(): Json
    {
        $data = request()->post();

        // 验证必填字段
        if (empty($data['channel'])) {
            return json([
                'code' => 400,
                'data' => null,
                'message' => '捐赠渠道不能为空'
            ]);
        }

        $result = DonationService::add($data);

        return json($result);
    }

    /**
     * 更新捐赠记录
     * 
     * POST /api/v1/donation/update
     * 
     * @return Json
     */
    public function update(): Json
    {
        $data = request()->post();

        if (empty($data['id'])) {
            return json([
                'code' => 400,
                'data' => null,
                'message' => '缺少参数id'
            ]);
        }

        $id = (int)$data['id'];
        unset($data['id']);

        $result = DonationService::update($id, $data);

        return json($result);
    }

    /**
     * 删除捐赠记录（软删除）
     * 
     * POST /api/v1/donation/delete
     * 
     * @return Json
     */
    public function delete(): Json
    {
        $id = request()->post('id');

        if (empty($id)) {
            return json([
                'code' => 400,
                'data' => null,
                'message' => '缺少参数id'
            ]);
        }

        $result = DonationService::delete((int)$id);

        return json($result);
    }

    /**
     * 批量删除捐赠记录
     * 
     * POST /api/v1/donation/batchDelete
     * 
     * @return Json
     */
    public function batchDelete(): Json
    {
        $ids = request()->post('ids');

        if (empty($ids) || !is_array($ids)) {
            return json([
                'code' => 400,
                'data' => null,
                'message' => '参数ids必须是数组且不能为空'
            ]);
        }

        $result = DonationService::batchDelete($ids);

        return json($result);
    }

    /**
     * 恢复捐赠记录
     * 
     * POST /api/v1/donation/restore
     * 
     * @return Json
     */
    public function restore(): Json
    {
        $id = request()->post('id');

        if (empty($id)) {
            return json([
                'code' => 400,
                'data' => null,
                'message' => '缺少参数id'
            ]);
        }

        $result = DonationService::restore((int)$id);

        return json($result);
    }

    /**
     * 获取已删除的捐赠记录列表
     * 
     * GET /api/v1/donation/getDeletedList
     * 
     * @return Json
     */
    public function getDeletedList(): Json
    {
        $params = request()->param();
        $result = DonationService::getDeletedList($params);

        return json($result);
    }

    /**
     * 更新捐赠状态
     * 
     * POST /api/v1/donation/updateStatus
     * 
     * @return Json
     */
    public function updateStatus(): Json
    {
        $id = request()->post('id');
        $status = request()->post('status');

        if (empty($id)) {
            return json([
                'code' => 400,
                'data' => null,
                'message' => '缺少参数id'
            ]);
        }

        if (!isset($status)) {
            return json([
                'code' => 400,
                'data' => null,
                'message' => '缺少参数status'
            ]);
        }

        $result = DonationService::updateStatus((int)$id, (int)$status);

        return json($result);
    }

    /**
     * 获取统计数据
     * 
     * GET /api/v1/donation/statistics
     * 
     * @return Json
     */
    public function getStatistics(): Json
    {
        $result = DonationService::getStatistics();

        return json($result);
    }

    /**
     * 获取渠道选项
     * 
     * GET /api/v1/donation/channelOptions
     * 
     * @return Json
     */
    public function getChannelOptions(): Json
    {
        $result = DonationService::getChannelOptions();

        return json($result);
    }

    /**
     * 获取状态选项
     *
     * GET /api/v1/donation/statusOptions
     *
     * @return Json
     */
    public function getStatusOptions(): Json
    {
        $result = DonationService::getStatusOptions();

        return json($result);
    }

    /**
     * 通过邮箱、iden或user_id查询捐赠记录
     *
     * GET /api/v1/donation/query
     *
     * @return Json
     */
    public function queryByContact(): Json
    {
        $email = request()->param('email', '');
        $iden = request()->param('iden', '');
        $userId = request()->param('user_id', 0);

        if (empty($email) && empty($iden) && empty($userId)) {
            return json([
                'code' => 400,
                'data' => null,
                'message' => '请提供邮箱、iden或user_id'
            ]);
        }

        $result = DonationService::queryByContact($email, $iden, (int)$userId);

        return json($result);
    }
}
