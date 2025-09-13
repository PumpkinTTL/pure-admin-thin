<?php

namespace app\api\controller\v1;


use app\BaseController;
use app\api\services\commentsService;

use think\facade\Validate;

use think\validate\ValidateRule;
use think\facade\Db;

class comments extends BaseController
{
    // 根据文章获取评论
   /**
     * 获取文章评论树（首次加载）
     */
    public function getComments($articleId)
    {
        // 开启SQL日志（调试用）
        Db::listen(function($sql) {
            // echo "[SQL] " . $sql . "\n";
        });

        $comments = commentsService::getTreeComments($articleId);
        return json([
            'code' => 200,
            'data' => $this->formatTree($comments) // 格式化嵌套结构
        ]);
    }
      /**
     * 获取子评论（懒加载）
     */
    public function getCommentsChildren($parentId)
    {
        return json([
            'code' => 200,
            'data' => commentsService::getChildComments($parentId)
        ]);
    }
    /**
     * 格式化树形结构（添加层级标记）
     */
    private function formatTree($comments, $level = 0)
    {
        return array_map(function($item) use ($level) {
            $item['level'] = $level;
            if (!empty($item['replies'])) {
                $item['replies'] = $this->formatTree($item['replies'], $level + 1);
            }
            return $item;
        }, $comments);
    }
}