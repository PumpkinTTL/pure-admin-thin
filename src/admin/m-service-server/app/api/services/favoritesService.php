<?php

namespace app\api\services;

use app\api\model\favorites;
use app\api\model\comments;
use app\api\model\article;
use think\facade\Db;

class favoritesService
{
    /**
     * 获取收藏列表（管理后台）
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
            
            $list = favorites::where($where)
                ->with(['user'])
                ->order('create_time', 'desc')
                ->page($page, $limit)
                ->select();
            
            $total = favorites::where($where)->count();
            
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
     * 收藏/取消收藏
     * @param int $userId 用户ID
     * @param string $targetType 目标类型: article, product, comment等
     * @param int $targetId 目标ID
     * @return array
     */
    public static function toggleFavorite($userId, $targetType, $targetId)
    {
        try {
            // 查询是否已收藏（只查询物理存在的记录）
            $favorite = favorites::where([
                'user_id' => $userId,
                'target_type' => $targetType,
                'target_id' => $targetId
            ])->find();
            
            if ($favorite) {
                // 已收藏，执行物理删除
                $favorite->delete();
                
                return [
                    'code' => 200,
                    'msg' => '取消收藏成功',
                    'data' => ['is_favorited' => false]
                ];
            } else {
                // 未收藏，创建新记录
                favorites::create([
                    'user_id' => $userId,
                    'target_type' => $targetType,
                    'target_id' => $targetId
                ]);
                
                return [
                    'code' => 200,
                    'msg' => '收藏成功',
                    'data' => ['is_favorited' => true]
                ];
            }
        } catch (\Exception $e) {
            // 检查是否是重复收藏错误
            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                return [
                    'code' => 400,
                    'msg' => '已经收藏过了，请勿重复收藏',
                    'info' => '重复收藏'
                ];
            }
            
            return [
                'code' => 500,
                'msg' => '操作失败',
                'info' => $e->getMessage()
            ];
        }
    }
}
