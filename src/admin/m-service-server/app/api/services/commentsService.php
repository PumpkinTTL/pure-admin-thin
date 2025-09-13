<?php

namespace app\api\services;


use app\api\model\comments as commentsModel;
class commentsService
{

    /**
     * 获取文章的顶级评论（带无限级嵌套）
     */
    public static function getTreeComments($articleId)
    {
        return commentsModel::with(['user', 'replies'])
            ->where('article_id', $articleId)
            ->where('parent_id', 0)
            ->whereNull('delete_time')
            ->select()
            ->toArray();
    }
       /**
     * 按需加载子评论（解决性能问题）
     */
    public static function getChildComments($parentId)
    {
        return commentsModel::with(['user', 'replies'])
            ->where('parent_id', $parentId)
            ->whereNull('delete_time')
            ->select()
            ->toArray();
    }
}