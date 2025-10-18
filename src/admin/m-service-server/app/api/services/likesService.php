<?php

namespace app\api\services;

use app\api\model\likes;
use app\api\model\comments;
use app\api\model\article;
use think\facade\Db;

class likesService
{
    /**
     * 获取点赞列表（管理后台）
     * @param array $params 查询参数
     * @return array
     */
    public static function getList($params = [])
    {
        try {
            $where = [];
            
            // 按用户ID筛选
            if (!empty($params['user_id'])) {
                $where['user_id'] = $params['user_id'];
            }
            
            // 按类型筛选
            if (!empty($params['target_type'])) {
                $where['target_type'] = $params['target_type'];
            }
            
            $page = $params['page'] ?? 1;
            $limit = $params['limit'] ?? 10;
            
            $list = likes::where($where)
                ->with(['user'])
                ->order('create_time', 'desc')
                ->page($page, $limit)
                ->select();
            
            $total = likes::where($where)->count();
            
            return [
                'list' => $list,
                'total' => $total,
                'page' => $page,
                'limit' => $limit
            ];
        } catch (\Exception $e) {
            return [
                'list' => [],
                'total' => 0,
                'page' => $page ?? 1,
                'limit' => $limit ?? 10,
                'error' => $e->getMessage()
            ];
        }
    }
    /**
     * 点赞/取消点赞
     * @param int $userId 用户ID
     * @param string $targetType 目标类型: comment, article
     * @param int $targetId 目标ID
     * @return array
     */
    public static function toggleLike($userId, $targetType, $targetId)
    {
        try {
            // 查询是否已点赞（只查询物理存在的记录）
            $like = likes::where([
                'user_id' => $userId,
                'target_type' => $targetType,
                'target_id' => $targetId
            ])->find();
            
            if ($like) {
                // 已点赞，执行删除（物理删除）
                $like->delete();
                
                return [
                    'code' => 200,
                    'msg' => '取消点赞成功',
                    'data' => ['is_liked' => false]
                ];
            } else {
                // 未点赞，创建新记录
                likes::create([
                    'user_id' => $userId,
                    'target_type' => $targetType,
                    'target_id' => $targetId
                ]);
                
                return [
                    'code' => 200,
                    'msg' => '点赞成功',
                    'data' => ['is_liked' => true]
                ];
            }
        } catch (\Exception $e) {
            return [
                'code' => 500,
                'msg' => '操作失败',
                'info' => $e->getMessage()
            ];
        }
    }
    
}
