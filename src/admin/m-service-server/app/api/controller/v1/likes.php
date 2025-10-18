<?php

namespace app\api\controller\v1;

use app\api\controller\BaseController;
use app\api\services\likesService;
use think\facade\Validate;
use app\api\validate\ValidateRule;

class likes extends BaseController
{
    /**
     * 点赞/取消点赞
     * POST /api/v1/likes/toggle
     */
    public function toggle()
    {
        $params = $this->request->post();
        
        // 参数验证
        if (empty($params['target_type']) || empty($params['target_id'])) {
            return json([
                'code' => 501,
                'msg' => '参数错误',
                'info' => 'target_type和target_id必须传递'
            ]);
        }
        
        // 验证target_type
        $allowedTypes = ['comment', 'article'];
        if (!in_array($params['target_type'], $allowedTypes)) {
            return json([
                'code' => 501,
                'msg' => '参数错误',
                'info' => 'target_type必须是: ' . implode(', ', $allowedTypes)
            ]);
        }
        
        // 获取当前用户ID（从token或session中获取）
        $userId = $params['user_id'] ?? 1; // 暂时使用传入的user_id，实际应从认证中获取
        
        $result = likesService::toggleLike(
            $userId,
            $params['target_type'],
            $params['target_id']
        );
        
        return json($result);
    }
    
    /**
     * 检查是否点赞
     * GET /api/v1/likes/check
     */
    public function check()
    {
        $params = $this->request->get();
        
        if (empty($params['target_type']) || empty($params['target_id'])) {
            return json([
                'code' => 501,
                'msg' => '参数错误'
            ]);
        }
        
        $userId = $params['user_id'] ?? 1;
        
        $isLiked = likesService::isLiked(
            $userId,
            $params['target_type'],
            $params['target_id']
        );
        
        return json([
            'code' => 200,
            'msg' => '查询成功',
            'data' => ['is_liked' => $isLiked]
        ]);
    }
    
    /**
     * 批量检查点赞状态
     * POST /api/v1/likes/batchCheck
     */
    public function batchCheck()
    {
        $params = $this->request->post();
        
        if (empty($params['target_type']) || empty($params['target_ids'])) {
            return json([
                'code' => 501,
                'msg' => '参数错误'
            ]);
        }
        
        $userId = $params['user_id'] ?? 1;
        
        $result = likesService::batchCheckLiked(
            $userId,
            $params['target_type'],
            $params['target_ids']
        );
        
        return json([
            'code' => 200,
            'msg' => '查询成功',
            'data' => $result
        ]);
    }
    
    /**
     * 获取用户点赞列表
     * GET /api/v1/likes/list
     */
    public function list()
    {
        $params = $this->request->get();
        
        $userId = $params['user_id'] ?? 1;
        $targetType = $params['target_type'] ?? '';
        $page = $params['page'] ?? 1;
        $limit = $params['limit'] ?? 10;
        
        $result = likesService::getUserLikes($userId, $targetType, $page, $limit);
        
        return json($result);
    }
}
