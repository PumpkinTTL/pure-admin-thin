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

    /**
     * 获取评论列表（分页，管理端）
     */
    public function getList()
    {
        $params = $this->request->param();
        
        $result = commentsService::getCommentsList($params);
        
        return json([
            'code' => 200,
            'msg' => 'success',
            'data' => $result
        ]);
    }

    /**
     * 获取评论统计数据
     */
    public function getStats()
    {
        $stats = commentsService::getCommentsStats();
        
        return json([
            'code' => 200,
            'msg' => 'success',
            'data' => $stats
        ]);
    }

    /**
     * 添加评论
     */
    public function add()
    {
        $params = $this->request->param();
        
        // 参数验证
        $validate = Validate::rule([
            'article_id' => ValidateRule::isRequire(null, '文章ID必须传递'),
            'content' => ValidateRule::isRequire(null, '评论内容必须传递')->max(1000, '评论内容不能超过1000字'),
            'user_id' => ValidateRule::isRequire(null, '用户ID必须传递')
        ]);
        
        if (!$validate->check($params)) {
            return json([
                'code' => 501,
                'msg' => '参数错误',
                'info' => $validate->getError()
            ]);
        }
        
        // 调用服务添加评论
        $result = commentsService::addComment($params);
        
        return json($result);
    }

    /**
     * 更新评论
     */
    public function update()
    {
        $params = $this->request->param();
        
        // 参数验证
        $validate = Validate::rule([
            'id' => ValidateRule::isRequire(null, '评论ID必须传递')
        ]);
        
        if (!$validate->check($params)) {
            return json([
                'code' => 501,
                'msg' => '参数错误',
                'info' => $validate->getError()
            ]);
        }
        
        // 调用服务更新评论
        $result = commentsService::updateComment($params);
        
        return json($result);
    }

    /**
     * 删除评论（软删除）
     */
    public function delete()
    {
        $params = $this->request->param();
        
        // 参数验证
        $validate = Validate::rule([
            'id' => ValidateRule::isRequire(null, '评论ID必须传递')
        ]);
        
        if (!$validate->check($params)) {
            return json([
                'code' => 501,
                'msg' => '参数错误',
                'info' => $validate->getError()
            ]);
        }
        
        $result = commentsService::deleteComment($params['id']);
        
        return json($result);
    }

    /**
     * 批量删除评论
     */
    public function batchDelete()
    {
        // 获取POST请求体中的数据
        $ids = $this->request->post('ids', []);
        
        // 参数验证
        if (empty($ids) || !is_array($ids)) {
            return json([
                'code' => 501,
                'msg' => '参数错误',
                'info' => 'ids必须是数组'
            ]);
        }
        
        $result = commentsService::batchDeleteComments($ids);
        
        return json($result);
    }

    /**
     * 审核评论（通过）
     */
    public function approve()
    {
        $params = $this->request->param();
        
        // 参数验证
        $validate = Validate::rule([
            'id' => ValidateRule::isRequire(null, '评论ID必须传递')
        ]);
        
        if (!$validate->check($params)) {
            return json([
                'code' => 501,
                'msg' => '参数错误',
                'info' => $validate->getError()
            ]);
        }
        
        $result = commentsService::updateCommentStatus($params['id'], 1);
        
        return json($result);
    }

    /**
     * 拒绝评论
     */
    public function reject()
    {
        $params = $this->request->param();
        
        // 参数验证
        $validate = Validate::rule([
            'id' => ValidateRule::isRequire(null, '评论ID必须传递')
        ]);
        
        if (!$validate->check($params)) {
            return json([
                'code' => 501,
                'msg' => '参数错误',
                'info' => $validate->getError()
            ]);
        }
        
        $result = commentsService::updateCommentStatus($params['id'], 2);
        
        return json($result);
    }

    /**
     * 批量审核评论
     */
    public function batchApprove()
    {
        // 获取POST请求体中的数据
        $ids = $this->request->post('ids', []);
        
        // 参数验证
        if (empty($ids) || !is_array($ids)) {
            return json([
                'code' => 501,
                'msg' => '参数错误',
                'info' => 'ids必须是数组'
            ]);
        }
        
        $result = commentsService::batchApproveComments($ids);
        
        return json($result);
    }

    /**
     * 获取评论详情
     */
    public function detail()
    {
        $params = $this->request->param();
        
        // 参数验证
        $validate = Validate::rule([
            'id' => ValidateRule::isRequire(null, '评论ID必须传递')
        ]);
        
        if (!$validate->check($params)) {
            return json([
                'code' => 501,
                'msg' => '参数错误',
                'info' => $validate->getError()
            ]);
        }
        
        $result = commentsService::getCommentDetail($params['id']);
        
        return json($result);
    }
}
