<?php

namespace app\api\controller\v1;

use app\api\services\EmailRecordService;
use app\BaseController;
use think\response\Json;

class emailRecord extends BaseController
{
    /**
     * 获取邮件记录列表
     * @return Json
     */
    public function getList(): Json
    {
        $params = request()->param();
        $data = EmailRecordService::getList($params);

        return json([
            'code' => 200,
            'data' => $data
        ]);
    }

    /**
     * 获取邮件记录详情
     * @return Json
     */
    public function getDetail(): Json
    {
        $id = request()->param('id');

        if (empty($id)) {
            return json([
                'code' => 400,
                'message' => '缺少参数id'
            ]);
        }

        $result = EmailRecordService::getDetail($id);

        return json([
            'code' => $result['success'] ? 200 : 500,
            'data' => $result['data'] ?? null,
            'message' => $result['message'] ?? ''
        ]);
    }

    /**
     * 获取接收者列表
     * @return Json
     */
    public function getReceivers(): Json
    {
        $recordId = request()->param('record_id');

        if (empty($recordId)) {
            return json([
                'code' => 400,
                'message' => '缺少参数record_id'
            ]);
        }

        $params = request()->param();
        $data = EmailRecordService::getReceivers($recordId, $params);

        return json([
            'code' => 200,
            'data' => $data
        ]);
    }

    /**
     * 发送邮件(创建记录并发送)
     * @return Json
     */
    public function send(): Json
    {
        $data = request()->param();

        // 验证必填参数
        if (empty($data['title'])) {
            return json([
                'code' => 400,
                'message' => '邮件标题不能为空'
            ]);
        }

        // 如果不是使用模板，则验证内容字段
        if (empty($data['template_id']) && empty($data['content'])) {
            return json([
                'code' => 400,
                'message' => '邮件内容不能为空'
            ]);
        }

        if (empty($data['sender_id'])) {
            return json([
                'code' => 400,
                'message' => '发送者ID不能为空'
            ]);
        }

        if (empty($data['receiver_type'])) {
            return json([
                'code' => 400,
                'message' => '接收方式不能为空'
            ]);
        }

        // 根据接收方式验证参数
        if ($data['receiver_type'] == 2 || $data['receiver_type'] == 3) {
            if (empty($data['receiver_ids']) || !is_array($data['receiver_ids'])) {
                return json([
                    'code' => 400,
                    'message' => '请选择接收用户'
                ]);
            }
        }

        if ($data['receiver_type'] == 4) {
            if (empty($data['receiver_emails']) || !is_array($data['receiver_emails'])) {
                return json([
                    'code' => 400,
                    'message' => '请输入接收邮箱'
                ]);
            }
        }

        $result = EmailRecordService::createAndSend($data);

        return json([
            'code' => $result['success'] ? 200 : 500,
            'data' => $result['data'] ?? null,
            'message' => $result['message']
        ]);
    }

    /**
     * 重新发送失败的邮件
     * @return Json
     */
    public function resend(): Json
    {
        $recordId = request()->param('record_id');
        $receiverIds = request()->param('receiver_ids', []);

        if (empty($recordId)) {
            return json([
                'code' => 400,
                'message' => '缺少参数record_id'
            ]);
        }

        $result = EmailRecordService::resendFailed($recordId, $receiverIds);

        return json([
            'code' => $result['success'] ? 200 : 500,
            'data' => $result['data'] ?? null,
            'message' => $result['message']
        ]);
    }

    /**
     * 删除邮件记录
     * @return Json
     */
    public function delete(): Json
    {
        $id = request()->param('id');

        if (empty($id)) {
            return json([
                'code' => 400,
                'message' => '缺少参数id'
            ]);
        }

        $result = EmailRecordService::delete($id);

        return json([
            'code' => $result['success'] ? 200 : 500,
            'message' => $result['message']
        ]);
    }

    /**
     * 批量删除邮件记录
     * @return Json
     */
    public function batchDelete(): Json
    {
        $ids = request()->param('ids', []);

        if (empty($ids) || !is_array($ids)) {
            return json([
                'code' => 400,
                'message' => '缺少参数ids'
            ]);
        }

        $result = EmailRecordService::batchDelete($ids);

        return json([
            'code' => $result['success'] ? 200 : 500,
            'data' => $result['data'] ?? null,
            'message' => $result['message']
        ]);
    }

    /**
     * 恢复邮件记录
     * @return Json
     */
    public function restore(): Json
    {
        $id = request()->param('id');

        if (empty($id)) {
            return json([
                'code' => 400,
                'message' => '缺少参数id'
            ]);
        }

        $result = EmailRecordService::restore($id);

        return json([
            'code' => $result['success'] ? 200 : 500,
            'message' => $result['message']
        ]);
    }

    /**
     * 获取统计数据
     * @return Json
     */
    public function getStatistics(): Json
    {
        $params = request()->param();
        $result = EmailRecordService::getStatistics($params);

        return json([
            'code' => $result['success'] ? 200 : 500,
            'data' => $result['data'] ?? null,
            'message' => $result['message'] ?? ''
        ]);
    }
}
