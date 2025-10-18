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

    /**
     * 添加评论
     */
    public static function addComment($data)
    {
        try {
            // 设置默认值
            $commentData = [
                'article_id' => $data['article_id'],
                'user_id' => $data['user_id'],
                'content' => $data['content'],
                'parent_id' => $data['parent_id'] ?? 0,
                'status' => $data['status'] ?? 0, // 默认待审核
                'likes' => 0
            ];
            
            $comment = commentsModel::create($commentData);
            
            return [
                'code' => 200,
                'msg' => '添加成功',
                'data' => $comment
            ];
        } catch (\Exception $e) {
            return [
                'code' => 500,
                'msg' => '添加失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 更新评论
     */
    public static function updateComment($data)
    {
        try {
            $comment = commentsModel::find($data['id']);
            
            if (!$comment) {
                return [
                    'code' => 404,
                    'msg' => '评论不存在'
                ];
            }
            
            // 检查是否已被删除
            if ($comment->delete_time) {
                return [
                    'code' => 400,
                    'msg' => '评论已被删除，无法编辑'
                ];
            }
            
            // 只更新允许修改的字段
            $allowedFields = ['content', 'status'];
            $updateData = [];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }
            
            if (empty($updateData)) {
                return [
                    'code' => 400,
                    'msg' => '没有可更新的字段'
                ];
            }
            
            $comment->save($updateData);
            
            return [
                'code' => 200,
                'msg' => '更新成功',
                'data' => $comment
            ];
        } catch (\Exception $e) {
            return [
                'code' => 500,
                'msg' => '更新失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 删除评论（软删除）
     */
    public static function deleteComment($id)
    {
        try {
            $comment = commentsModel::find($id);
            
            if (!$comment) {
                return [
                    'code' => 404,
                    'msg' => '评论不存在'
                ];
            }
            
            if ($comment->delete_time) {
                return [
                    'code' => 400,
                    'msg' => '评论已被删除'
                ];
            }
            
            // 软删除
            $comment->delete();
            
            // 同时软删除所有子评论
            self::deleteChildComments($id);
            
            return [
                'code' => 200,
                'msg' => '删除成功'
            ];
        } catch (\Exception $e) {
            return [
                'code' => 500,
                'msg' => '删除失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 递归删除子评论
     */
    private static function deleteChildComments($parentId)
    {
        $children = commentsModel::where('parent_id', $parentId)
            ->whereNull('delete_time')
            ->select();
        
        foreach ($children as $child) {
            $child->delete();
            // 递归删除子评论的子评论
            self::deleteChildComments($child->id);
        }
    }

    /**
     * 批量删除评论
     */
    public static function batchDeleteComments($ids)
    {
        try {
            if (empty($ids)) {
                return [
                    'code' => 400,
                    'msg' => '请选择要删除的评论'
                ];
            }
            
            $successCount = 0;
            $failCount = 0;
            
            foreach ($ids as $id) {
                $result = self::deleteComment($id);
                if ($result['code'] == 200) {
                    $successCount++;
                } else {
                    $failCount++;
                }
            }
            
            return [
                'code' => 200,
                'msg' => "成功删除{$successCount}条，失败{$failCount}条",
                'data' => [
                    'success' => $successCount,
                    'fail' => $failCount
                ]
            ];
        } catch (\Exception $e) {
            return [
                'code' => 500,
                'msg' => '批量删除失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 更新评论状态
     */
    public static function updateCommentStatus($id, $status)
    {
        try {
            $comment = commentsModel::find($id);
            
            if (!$comment) {
                return [
                    'code' => 404,
                    'msg' => '评论不存在'
                ];
            }
            
            if ($comment->delete_time) {
                return [
                    'code' => 400,
                    'msg' => '评论已被删除'
                ];
            }
            
            $comment->status = $status;
            $comment->save();
            
            $statusText = [
                0 => '待审核',
                1 => '已通过',
                2 => '已拒绝'
            ];
            
            return [
                'code' => 200,
                'msg' => '状态已更新为：' . ($statusText[$status] ?? '未知'),
                'data' => $comment
            ];
        } catch (\Exception $e) {
            return [
                'code' => 500,
                'msg' => '更新状态失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 批量审核评论
     */
    public static function batchApproveComments($ids)
    {
        try {
            if (empty($ids)) {
                return [
                    'code' => 400,
                    'msg' => '请选择要审核的评论'
                ];
            }
            
            $successCount = 0;
            $failCount = 0;
            
            foreach ($ids as $id) {
                $result = self::updateCommentStatus($id, 1);
                if ($result['code'] == 200) {
                    $successCount++;
                } else {
                    $failCount++;
                }
            }
            
            return [
                'code' => 200,
                'msg' => "成功审核{$successCount}条，失败{$failCount}条",
                'data' => [
                    'success' => $successCount,
                    'fail' => $failCount
                ]
            ];
        } catch (\Exception $e) {
            return [
                'code' => 500,
                'msg' => '批量审核失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 获取评论详情
     */
    public static function getCommentDetail($id)
    {
        try {
            $comment = commentsModel::with(['user', 'replies'])
                ->find($id);
            
            if (!$comment) {
                return [
                    'code' => 404,
                    'msg' => '评论不存在'
                ];
            }
            
            // 计算回复数
            $comment = $comment->toArray();
            $comment['reply_count'] = commentsModel::where('parent_id', $id)
                ->whereNull('delete_time')
                ->count();
            
            return [
                'code' => 200,
                'msg' => '获取成功',
                'data' => $comment
            ];
        } catch (\Exception $e) {
            return [
                'code' => 500,
                'msg' => '获取失败：' . $e->getMessage()
            ];
        }
    }
}
