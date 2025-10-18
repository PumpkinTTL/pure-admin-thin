<?php

namespace app\api\services;


use app\api\model\comments as commentsModel;
class commentsService
{

    /**
     * 获取文章的顶级评论（带3层嵌套）
     */
    public static function getTreeComments($articleId)
    {
        $comments = commentsModel::with(['user', 'replies'])
            ->where('article_id', $articleId)
            ->where('parent_id', 0)
            ->whereNull('delete_time')
            ->select()
            ->toArray();
        
        // 为每个评论添加 hasChildren 标记和 reply_count
        return self::addChildrenFlag($comments);
    }
    /**
     * 按需加载子评论（解决性能问题）
     * 返回直接子评论 + 再嵌套2层
     */
    public static function getChildComments($parentId)
    {
        $comments = commentsModel::with(['user', 'replies'])
            ->where('parent_id', $parentId)
            ->whereNull('delete_time')
            ->select()
            ->toArray();
        
        // 添加 hasChildren 标记
        return self::addChildrenFlag($comments);
    }

    /**
     * 获取评论列表（分页，管理端使用）
     */
    public static function getCommentsList($params)
    {
        $page = $params['page'] ?? 1;
        $limit = $params['limit'] ?? 20;
        
        $query = commentsModel::with(['user'])
            ->whereNull('delete_time');
        
        // 按文章ID筛选
        if (isset($params['article_id']) && $params['article_id']) {
            $query->where('article_id', $params['article_id']);
        }
        
        // 按状态筛选
        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('status', $params['status']);
        }
        
        // 按关键词搜索（内容或用户名）
        if (isset($params['keyword']) && $params['keyword']) {
            $keyword = $params['keyword'];
            $query->where(function($q) use ($keyword) {
                $q->where('content', 'like', "%{$keyword}%");
            });
        }
        
        // 按用户ID筛选
        if (isset($params['user_id']) && $params['user_id']) {
            $query->where('user_id', $params['user_id']);
        }
        
        // 获取总数
        $total = $query->count();
        
        // 分页查询
        $list = $query->order('create_time', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();
        
        // 计算回复数
        foreach ($list as &$item) {
            $item['reply_count'] = commentsModel::where('parent_id', $item['id'])
                ->whereNull('delete_time')
                ->count();
        }
        
        return [
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'limit' => $limit
        ];
    }

    /**
     * 获取评论统计数据
     */
    public static function getCommentsStats()
    {
        $total = commentsModel::whereNull('delete_time')->count();
        $pending = commentsModel::where('status', 0)->whereNull('delete_time')->count();
        $approved = commentsModel::where('status', 1)->whereNull('delete_time')->count();
        $rejected = commentsModel::where('status', 2)->whereNull('delete_time')->count();
        
        return [
            'total' => $total,
            'pending' => $pending,
            'approved' => $approved,
            'rejected' => $rejected
        ];
    }

    /**
     * 递归添加 hasChildren 标记和 reply_count
     * @param array $comments 评论数组
     * @param int $currentLevel 当前层级（0=顶级）
     * @return array
     */
    private static function addChildrenFlag($comments, $currentLevel = 0)
    {
        foreach ($comments as &$comment) {
            // 计算直接子评论数量
            $childCount = commentsModel::where('parent_id', $comment['id'])
                ->whereNull('delete_time')
                ->count();
            
            $comment['reply_count'] = $childCount;
            
            // 判断是否还有更深层的子评论（用于展示“加载更多”按钮）
            // 如果当前已经是第3层，且还有子评论，则显示hasChildren=true
            if ($currentLevel >= 2) {
                // 第3层以后，如果还有子评论则显示懒加载按钮
                $comment['hasChildren'] = $childCount > 0;
            } else {
                // 前2层不需要懒加载按钮（已经预加载）
                $comment['hasChildren'] = false;
            }
            
            // 递归处理子评论
            if (isset($comment['replies']) && !empty($comment['replies'])) {
                $comment['replies'] = self::addChildrenFlag($comment['replies'], $currentLevel + 1);
            }
        }
        
        return $comments;
    }
}
