<?php

namespace app\api\controller\v1;

use app\api\middleware\ParamFilter;
use app\api\services\articleService;
use app\api\services\LogService;
use app\BaseController;
use Exception;
use OpenAI;
use think\facade\Db;
use utils\NumUtil;


class article extends BaseController
{
    protected $middleware = [ParamFilter::class];

    /**
     * 使用讯飞星火API生成文章摘要
     * @throws Exception
     */
    public function getSummary()
    {
        try {
            $prompt = request()->param('prompt');
            if (empty($prompt)) {
                return json([
                    'code' => 400,
                    'msg' => '摘要生成内容不能为空',
                    'data' => null
                ]);
            }

            $client = OpenAI::client('0a9b9ec5f1dafb0eec121f8f6cb8d118');

            $result = $client->chat()->create([
                'base_url' => 'https://spark-api-open.xf-yun.com/v1/chat/completions',
                'model' => 'Spark Lite',
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

            return json([
                'code' => 200,
                'msg' => '生成摘要成功',
                'data' => $result
            ]);
        } catch (Exception $e) {
            LogService::error($e);
            return json([
                'code' => 500,
                'msg' => '生成摘要失败：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }

    /**
     * 获取文章列表(管理后台)
     */
    public function selectArticleAll()
    {
        $params = request()->param();
        $result = articleService::selectArticleAll($params);
        return json($result);
    }

    /**
     * 获取文章列表(前台)
     */
    public function getArticleList()
    {
        // 获取并验证参数
        $params = request()->only([
            'id', 'author_id', 'category_id',
            'title', 'keywords', 'create_time',
            'update_time', 'publish_time',
            'page',
            'page_size'  // 分页参数
        ]);

        $result = articleService::selectArticleAll($params);
        return json($result);
    }

    /**
     * 添加文章
     */
    public function add()
    {
        // 1. 参数获取与验证
        $params = $this->request->param();
        // 1. 处理文章ID：前端传了取前端，否则生成5位数
        $articleId = !empty($params['id']) ? (int)$params['id'] : NumUtil::generateNumberCode();
        try {
            // 基础验证（TP8内置验证）
            $this->validate($params, [
                'title' => 'require|max:50',
                'content' => 'require',
                'author_id' => 'require',
                'tags' => 'require|array',

            ], [
                'title.require' => '文章标题必须填写',
                'content.require' => '文章内容必须填写',
                'tags.require' => '至少需要一个文章标签'
            ]);
            // 2. 开启事务
            Db::startTrans();

            // 3. 插入文章主表
            articleService::addArticle($params);

            // 4. 处理标签关联 - 使用统一的标签处理方法
            if (!articleService::handleArticleTags($articleId, $params['tags'])) {
                throw new Exception('标签关联处理失败');
            }

            // 5. 提交事务
            Db::commit();

            // 6. 返回标准响应
            return json([
                'code' => 200,
                'msg' => '文章新增成功',
                'data' => [
                    'article_id' => $articleId
                ]
            ]);

        } catch (\think\exception\ValidateException $e) {
            // 验证异常捕获
            return json([
                'code' => 422,
                'msg' => $e->getMessage(),
                'data' => null
            ]);

        } catch (Exception $e) {
            // 其他异常捕获（自动回滚）
            Db::rollback();

            // 生产环境隐藏详细错误（开发环境可显示）
            $msg = app()->isDebug() ? $e->getMessage() : '系统繁忙，请稍后重试';

            return json([
                'code' => 500,
                'msg' => "文章新增失败: {$msg}",
                'data' => null
            ]);
        }
    }

    /**
     * 根据id查询文章详情
     */
    public function selectArticleById()
    {
        $id = request()->param('id');
        $result = articleService::selectArticleById($id);
        return json($result);
    }
    
    /**
     * 更新文章
     */
    public function update()
    {
        $params = request()->param();
        $id = isset($params['id']) ? intval($params['id']) : 0;
        
        if ($id <= 0) {
            return json(['code' => 400, 'msg' => '缺少必要参数ID']);
        }
        
        $result = articleService::updateArticle($id, $params);
        return json($result);
    }
    
    /**
     * 删除文章
     */
    public function delete()
    {
        $params = request()->param();
        $id = isset($params['id']) ? intval($params['id']) : 0;
        $real = isset($params['real']) && ($params['real'] === 'true' || $params['real'] === true || $params['real'] === '1' || $params['real'] === 1);
        
        if ($id <= 0) {
            return json(['code' => 400, 'msg' => '缺少必要参数ID']);
        }
        
        $result = articleService::deleteArticle($id, $real);
        return json($result);
    }
    
    /**
     * 恢复已删除的文章
     */
    public function restore()
    {
        $params = request()->param();
        $id = isset($params['id']) ? intval($params['id']) : 0;
        
        if ($id <= 0) {
            return json(['code' => 400, 'msg' => '缺少必要参数ID']);
        }
        
        $result = articleService::restoreArticle($id);
        return json($result);
    }
    
    /**
     * 获取已删除的文章列表
     */
    public function getDeletedArticles()
    {
        $params = request()->param();
        $result = articleService::getDeletedArticles($params);
        return json($result);
    }
}