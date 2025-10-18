<?php

namespace app\api\services;

use app\api\model\likes;
use app\api\model\comments;
use app\api\model\article;
use think\facade\Db;

class likesService
{
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
            // 查询是否已点赞
            $like = likes::where([
                'user_id' => $userId,
                'target_type' => $targetType,
                'target_id' => $targetId
            ])->whereNull('delete_time')->find();
            
            Db::startTrans();
            
            if ($like) {
                // 已点赞，执行取消点赞（软删除）
                $like->delete();
                
                // 减少目标的点赞数
                self::updateTargetLikes($targetType, $targetId, -1);
                
                Db::commit();
                return [
                    'code' => 200,
                    'msg' => '取消点赞成功',
                    'data' => ['is_liked' => false]
                ];
            } else {
                // 未点赞，执行点赞
                // 检查是否有被软删除的记录，如果有则恢复
                $deletedLike = likes::where([
                    'user_id' => $userId,
                    'target_type' => $targetType,
                    'target_id' => $targetId
                ])->withTrashed()->find();
                
                if ($deletedLike) {
                    // 恢复软删除的记录
                    $deletedLike->delete_time = null;
                    $deletedLike->save();
                } else {
                    // 创建新记录
                    likes::create([
                        'user_id' => $userId,
                        'target_type' => $targetType,
                        'target_id' => $targetId
                    ]);
                }
                
                // 增加目标的点赞数
                self::updateTargetLikes($targetType, $targetId, 1);
                
                Db::commit();
                return [
                    'code' => 200,
                    'msg' => '点赞成功',
                    'data' => ['is_liked' => true]
                ];
            }
        } catch (\Exception $e) {
            Db::rollback();
            return [
                'code' => 500,
                'msg' => '操作失败',
                'info' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 更新目标的点赞数
     * @param string $targetType
     * @param int $targetId
     * @param int $increment 1表示增加，-1表示减少
     */
    private static function updateTargetLikes($targetType, $targetId, $increment)
    {
        switch ($targetType) {
            case 'comment':
                comments::where('id', $targetId)->inc('likes', $increment)->update();
                break;
            case 'article':
                article::where('id', $targetId)->inc('likes', $increment)->update();
                break;
        }
    }
    
    /**
     * 检查用户是否点赞
     * @param int $userId
     * @param string $targetType
     * @param int $targetId
     * @return bool
     */
    public static function isLiked($userId, $targetType, $targetId)
    {
        return likes::where([
            'user_id' => $userId,
            'target_type' => $targetType,
            'target_id' => $targetId
        ])->whereNull('delete_time')->count() > 0;
    }
    
    /**
     * 批量检查用户点赞状态
     * @param int $userId
     * @param string $targetType
     * @param array $targetIds
     * @return array [target_id => is_liked]
     */
    public static function batchCheckLiked($userId, $targetType, $targetIds)
    {
        if (empty($targetIds)) {
            return [];
        }
        
        $likes = likes::where([
            'user_id' => $userId,
            'target_type' => $targetType
        ])->whereIn('target_id', $targetIds)
          ->whereNull('delete_time')
          ->column('target_id');
        
        $result = [];
        foreach ($targetIds as $id) {
            $result[$id] = in_array($id, $likes);
        }
        
        return $result;
    }
    
    /**
     * 获取用户点赞列表
     * @param int $userId
     * @param string $targetType
     * @param int $page
     * @param int $limit
     * @return array
     */
    public static function getUserLikes($userId, $targetType = '', $page = 1, $limit = 10)
    {
        try {
            $where = ['user_id' => $userId];
            if ($targetType) {
                $where['target_type'] = $targetType;
            }
            
            $list = likes::where($where)
                ->whereNull('delete_time')
                ->with(['user'])
                ->order('create_time', 'desc')
                ->page($page, $limit)
                ->select();
            
            $total = likes::where($where)->whereNull('delete_time')->count();
            
            return [
                'code' => 200,
                'msg' => '获取成功',
                'data' => [
                    'list' => $list,
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit
                ]
            ];
        } catch (\Exception $e) {
            return [
                'code' => 500,
                'msg' => '获取失败',
                'info' => $e->getMessage()
            ];
        }
    }
}
