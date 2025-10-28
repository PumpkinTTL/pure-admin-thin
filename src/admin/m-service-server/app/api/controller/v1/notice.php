<?php

namespace app\api\controller\v1;

use app\api\middleware\NoticeAuth;
use app\api\services\noticeService;
use app\BaseController;
use think\response\Json;

class notice extends BaseController
{
    // 应用公告权限中间件
    protected $middleware = [
        NoticeAuth::class
    ];

    /**
     * 获取公告列表
     * @return Json
     */
    public function getNoticeList(): Json
    {
        $params = request()->param();

        // 从中间件获取用户信息和管理员标识
        $currentUserId = request()->currentUserId ?? 0;
        $currentUserRoles = request()->currentUserRoles ?? [];
        $isAdmin = request()->isAdmin ?? false; // 从中间件获取管理员标识（基于 Token 中的角色）

        error_log("[notice Controller] getNoticeList - userId: {$currentUserId}, roles: " . json_encode($currentUserRoles) . ", isAdmin: " . ($isAdmin ? 'true' : 'false'));

        // 传递用户上下文到 Service
        $params['current_user_id'] = $currentUserId;
        $params['current_user_roles'] = $currentUserRoles;
        $params['is_admin'] = $isAdmin; // 传递管理员标识

        $data = noticeService::getNoticeList($params);

        return json([
            'code' => 200,
            'data' => $data
        ]);
    }

    /**
     * 获取公告详情
     * @return Json
     */
    public function getNoticeById(): Json
    {
        $id = request()->param('notice_id');
        $result = noticeService::getNoticeById($id);

        return json([
            'code' => $result['success'] ? 200 : 500,
            'data' => $result['data'] ?? null,
            'message' => $result['message']
        ]);
    }

    /**
     * 创建公告
     * @return Json
     */
    public function createNotice(): Json
    {
        $data = request()->param();

        // 验证数据
        $validate = Validate([
            'title' => 'require|max:100',
            'content' => 'require',
            'category_type' => 'require|in:1,2,3,4,5',
            'publisher_id' => 'require|number',
            'visibility' => 'require|in:public,login_required,specific_users,specific_roles',
            'publish_time' => 'date',
            'expire_time' => 'date',
            'status' => 'in:0,1,2',
            'priority' => 'in:0,1,2',
            'is_top' => 'boolean'
        ]);

        if (!$validate->check($data)) {
            return json([
                'code' => 400,
                'message' => $validate->getError()
            ]);
        }

        $result = noticeService::createNotice($data);

        return json([
            'code' => $result['success'] ? 200 : 500,
            'data' => $result['data'] ?? null,
            'message' => $result['message']
        ]);
    }

    /**
     * 更新公告
     * @return Json
     */
    public function updateNotice(): Json
    {
        $data = request()->param();
        $id = request()->param('notice_id');

        // 验证数据
        $validate = Validate([
            'title' => 'max:100',
            'category_type' => 'in:1,2,3,4,5',
            'visibility' => 'in:public,login_required,specific_users,specific_roles',
            'publish_time' => 'date',
            'expire_time' => 'date',
            'status' => 'in:0,1,2',
            'priority' => 'in:0,1,2',
            'is_top' => 'boolean'
        ]);

        if (!$validate->check($data)) {
            return json([
                'code' => 400,
                'message' => $validate->getError()
            ]);
        }

        $result = noticeService::updateNotice($id, $data);

        return json([
            'code' => $result['success'] ? 200 : 500,
            'data' => $result['data'] ?? null,
            'message' => $result['message']
        ]);
    }

    /**
     * 删除公告
     * @return Json
     */
    public function deleteNotice(): Json
    {
        $id = request()->param('notice_id');
        $real = request()->param('real', false);

        // 将real参数转换为布尔值
        $realDelete = filter_var($real, FILTER_VALIDATE_BOOLEAN);

        $result = noticeService::deleteNotice($id, $realDelete);

        return json([
            'code' => $result['success'] ? 200 : 500,
            'message' => $result['message']
        ]);
    }

    /**
     * 获取已删除的公告列表
     * @return Json
     */
    public function getTrashedNotices(): Json
    {
        $params = request()->param();
        $data = noticeService::getTrashedNotices($params);

        return json([
            'code' => 200,
            'data' => $data
        ]);
    }

    /**
     * 恢复已删除的公告
     * @return Json
     */
    public function restoreNotice(): Json
    {
        $id = request()->param('notice_id');
        $result = noticeService::restoreNotice($id);

        return json([
            'code' => $result['success'] ? 200 : 500,
            'message' => $result['message']
        ]);
    }

    /**
     * 更新公告状态
     * @return Json
     */
    public function updateNoticeStatus(): Json
    {
        $id = request()->param('notice_id');
        $status = request()->param('status');

        $result = noticeService::updateNoticeStatus($id, $status);

        return json([
            'code' => $result['success'] ? 200 : 500,
            'message' => $result['message']
        ]);
    }

    /**
     * 切换公告置顶状态
     * @return Json
     */
    public function toggleNoticeTop(): Json
    {
        $id = request()->param('notice_id');
        $result = noticeService::toggleNoticeTop($id);

        return json([
            'code' => $result['success'] ? 200 : 500,
            'message' => $result['message']
        ]);
    }

    /**
     * 获取用户可见的公告
     * @return Json
     */
    public function getUserNotices(): Json
    {
        $userId = request()->param('user_id');
        $params = request()->param();

        $data = noticeService::getUserNotices($userId, $params);

        return json([
            'code' => 200,
            'data' => $data
        ]);
    }
}
