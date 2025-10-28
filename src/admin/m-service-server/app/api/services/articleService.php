<?php

namespace app\api\services;

use app\api\model\article as articleModel;
use app\api\services\LogService;
use think\facade\Db;

class articleService
{
    /**
     * 获取文章列表
     * @param array $params 查询参数
     * @return array
     */
    public static function selectArticleAll(array $params = [])
    {
        // 获取当前用户信息（优先从参数获取，其次从request，最后从session）
        $currentUserId = $params['current_user_id'] ?? request()->currentUserId ?? session('user_id') ?? 0;
        $currentUserRoles = $params['current_user_roles'] ?? request()->currentUserRoles ?? session('user_roles') ?? [];
        $isAdmin = $params['is_admin'] ?? request()->isAdmin ?? false;



        // 基础查询构建
        $query = articleModel::with([
            'category' => function ($query) {
                $query->field(['id', 'name', 'slug', 'meta_title', 'meta_keywords']);
            },
            'author' => function ($query) {
                // 首先过滤 author 表的字段
                $query->field(['id', 'username', 'nickname', 'avatar', 'signature'])
                    // 然后关联 roles 并过滤 roles 表的字段
                    ->with(['roles' => function ($query) {
                        $query->field(['id', 'name', 'description', 'show_weight']); // roles 表需要的字段
                    }]);
            },

            'tags' => function ($query) {
                $query->field(['name']);
            },
            // 加载权限关联数据，限制返回字段保护隐私
            'accessUsers' => function ($query) {
                $query->field(['bl_users.id', 'bl_users.username', 'bl_users.nickname']);
            },
            'accessRoles' => function ($query) {
                $query->field(['bl_roles.id', 'bl_roles.name']);
            }
        ])
            ->withCount(['favorites', 'likes', 'comments']);

        // 权限过滤逻辑（除非禁用权限过滤）
        if (!isset($params['skip_permission_filter']) || !$params['skip_permission_filter']) {
            error_log("========================================");
            error_log("🔍 [articleService] 权限过滤判断:");
            error_log("isAdmin 类型: " . gettype($isAdmin));
            error_log("isAdmin 值: " . var_export($isAdmin, true));
            error_log("isAdmin === true: " . ($isAdmin === true ? 'YES' : 'NO'));
            error_log("isAdmin == true: " . ($isAdmin == true ? 'YES' : 'NO'));
            error_log("isAdmin == 1: " . ($isAdmin == 1 ? 'YES' : 'NO'));
            error_log("========================================");

            // ✅ 如果是管理员，跳过权限过滤（管理员可以看到所有文章）
            if ($isAdmin === true || $isAdmin === 1 || $isAdmin == true) {
                error_log("========================================");
                error_log("✅✅✅ [articleService] 管理员请求 - 跳过权限过滤 ✅✅✅");
                error_log("========================================");
            } else {
                // 非管理员：应用权限过滤
                $query->where(function ($query) use ($currentUserId, $currentUserRoles) {
                    // ✅ 修复：第一个条件用 where，后续条件用 whereOr

                    // 1. 公开文章 - 所有人都能看（第一个条件用 where）
                    $query->where('visibility', 'public');

                    // 2. 作者自己的文章 - 作者始终可见（包括private）
                    if ($currentUserId > 0) {
                        $query->whereOr('author_id', $currentUserId);
                        error_log("[articleService] ✅ 添加条件: author_id = {$currentUserId} (用 whereOr)");
                    }

                    // 3. 登录可见 - 已登录用户能看
                    if ($currentUserId > 0) {
                        $query->whereOr('visibility', 'login_required');
                        error_log("[articleService] ✅ 添加条件: visibility = 'login_required' (用 whereOr)");
                    } else {
                        error_log("[articleService] ⏭️  跳过 login_required (用户未登录)");
                    }

                    // 4. 指定用户可见 - 使用原生 whereRaw 避免关联问题
                    if ($currentUserId > 0) {
                        $query->whereOr(function ($subQuery) use ($currentUserId) {
                            $subQuery->where('visibility', 'specific_users')
                                ->whereRaw("EXISTS (SELECT 1 FROM bl_article_user_access WHERE bl_article_user_access.article_id = bl_article.id AND bl_article_user_access.user_id = ?)", [$currentUserId]);
                        });
                        error_log("[articleService] ✅ 添加条件: specific_users (userId={$currentUserId}) (用 whereOr)");
                    }

                    // 5. 指定角色可见 - 使用原生 whereRaw 避免关联问题
                    if (is_array($currentUserRoles) && count($currentUserRoles) > 0) {
                        $rolesStr = implode(',', array_map('intval', $currentUserRoles));

                        $query->whereOr(function ($subQuery) use ($currentUserRoles, $rolesStr) {
                            $subQuery->where('visibility', 'specific_roles')
                                ->whereRaw("EXISTS (SELECT 1 FROM bl_article_role_access WHERE bl_article_role_access.article_id = bl_article.id AND bl_article_role_access.role_id IN ({$rolesStr}))");
                        });
                    }
                });
            }
        }

        // ID精确查询
        if (!empty($params['id'])) {
            $query->where('id', $params['id']);
        }

        // 标题模糊查询
        if (!empty($params['title'])) {
            $query->whereLike('title', '%' . $params['title'] . '%');
        }

        // 作者ID精确查询
        if (!empty($params['author_id'])) {
            $query->where('author_id', $params['author_id']);
        }

        // 用户ID查询 (与author_id同义，提供兼容)
        if (!empty($params['user_id'])) {
            $query->where('author_id', $params['user_id']);
        }

        // 分类ID精确查询
        if (!empty($params['category_id'])) {
            $query->where('category_id', $params['category_id']);
        }

        // 状态查询 (0:草稿 1:已发布 2:审核中 3:已下架)
        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('status', $params['status']);
        }

        // 置顶查询 (0:否 1:是)
        if (isset($params['is_top']) && $params['is_top'] !== '') {
            $query->where('is_top', $params['is_top']);
        }

        // 推荐查询 (0:否 1:是)
        if (isset($params['is_recommend']) && $params['is_recommend'] !== '') {
            $query->where('is_recommend', $params['is_recommend']);
        }

        // 原创查询 (0:否 1:是)
        if (isset($params['is_original']) && $params['is_original'] !== '') {
            $query->where('is_original', $params['is_original']);
        }
        // create_time 时间范围查询
        if (!empty($params['create_time_start'])) {
            $query->where('create_time', '>=', $params['create_time_start']);
        }
        if (!empty($params['create_time_end'])) {
            $query->where('create_time', '<=', $params['create_time_end']);
        }
        // create_time 单边查询
        if (!empty($params['create_time_gt'])) {
            $query->where('create_time', '>', $params['create_time_gt']);
        }
        if (!empty($params['create_time_lt'])) {
            $query->where('create_time', '<', $params['create_time_lt']);
        }

        // update_time 时间范围查询
        if (!empty($params['update_time_start'])) {
            $query->where('update_time', '>=', $params['update_time_start']);
        }
        if (!empty($params['update_time_end'])) {
            $query->where('update_time', '<=', $params['update_time_end']);
        }
        // update_time 单边查询
        if (!empty($params['update_time_gt'])) {
            $query->where('update_time', '>', $params['update_time_gt']);
        }
        if (!empty($params['update_time_lt'])) {
            $query->where('update_time', '<', $params['update_time_lt']);
        }

        // publish_time 时间范围查询
        if (!empty($params['publish_time_start'])) {
            $query->where('publish_time', '>=', $params['publish_time_start']);
        }
        if (!empty($params['publish_time_end'])) {
            $query->where('publish_time', '<=', $params['publish_time_end']);
        }
        // publish_time 单边查询
        if (!empty($params['publish_time_gt'])) {
            $query->where('publish_time', '>', $params['publish_time_gt']);
        }
        if (!empty($params['publish_time_lt'])) {
            $query->where('publish_time', '<', $params['publish_time_lt']);
        }

        // 删除状态查询
        if (isset($params['delete_status'])) {
            if ($params['delete_status'] === 'only_deleted') {
                $query->onlyTrashed(); // 仅查询已删除
            } else if ($params['delete_status'] === 'with_deleted') {
                $query->withTrashed(); // 包含已删除
            }
            // 默认不使用withTrashed，查询未删除的数据
        }

        // 排序条件
        $orderField = 'update_time'; // 默认按修改时间排序
        $orderDirection = 'desc';  // 默认排序方向

        // 如果sort参数为true，则按sort字段排序
        if (isset($params['sort']) && ($params['sort'] === true || $params['sort'] === 'true' || $params['sort'] === 1 || $params['sort'] === '1')) {
            $orderField = 'sort';
        }

        // 指定排序方向
        if (!empty($params['order'])) {
            $orderDirection = strtolower($params['order']) === 'asc' ? 'asc' : 'desc';
        }

        // 应用排序
        $query->order($orderField, $orderDirection);

        // 分页参数设置
        $page = !empty($params['page']) ? intval($params['page']) : 1;
        $pageSize = !empty($params['page_size']) ? intval($params['page_size']) : 10;

        LogService::log("[Service] 开始执行分页查询 - page: {$page}, pageSize: {$pageSize}", [], 'info');

        // 执行分页查询
        $result = $query->paginate([
            'page' => $page,
            'list_rows' => $pageSize
        ]);

        // 返回标准格式的分页数据
        return [
            'code' => 200,
            'msg' => '获取文章列表成功',
            'data' => [
                'list' => $result->items(),
                'pagination' => [
                    'total' => $result->total(),
                    'current' => $result->currentPage(),
                    'page_size' => $pageSize,
                    'pages' => $result->lastPage()
                ]
            ]
        ];
    }

    /**
     * 文章新增
     * @param array $params 文章数据
     * @return articleModel
     */
    public static function addArticle($params)
    {
        return articleModel::create($params);
    }

    /**
     * 根据id查询文章详情
     * @param int $id 文章ID
     * @return array
     */
    public static function selectArticleById($id)
    {
        $article = articleModel::with([
            'category' => function ($query) {
                $query->field(['id', 'name', 'slug', 'meta_title', 'meta_keywords']);
            },

            'author' => function ($query) {
                $query->field(['id', 'username', 'nickname', 'avatar', 'signature']);
            },
            'tags' => function ($query) {
                $query->field(['name']);
            },
            'comments' => function ($query) {
                $query->field(['id', 'content', 'create_time', 'update_time', 'delete_time']);
            },
            // 加载权限关联数据
            'accessUsers',
            'accessRoles'
        ])->withCount(['favorites', 'likes', 'comments'])->where('id', $id)->find();

        if (!$article) {
            return [
                'code' => 404,
                'msg' => '文章不存在',
                'data' => null
            ];
        }

        return [
            'code' => 200,
            'msg' => '获取文章详情成功',
            'data' => $article
        ];
    }

    /**
     * 删除文章
     * @param int $id 文章ID
     * @param bool $real 是否物理删除，默认为false(软删除)
     * @return array
     */
    public static function deleteArticle(int $id, bool $real = false): array
    {
        try {
            // 查询文章是否存在
            $article = articleModel::withTrashed()->find($id);
            if (!$article) {
                LogService::log("删除文章失败，ID不存在：{$id}", [], 'warning');
                return ['code' => 404, 'msg' => '文章不存在'];
            }

            // 根据real参数决定删除方式
            if ($real === true) {
                // 物理删除
                if ($article->delete_time !== null) {
                    // 如果已经软删除，则需要先恢复
                    $article->restore();
                }
                $article->force()->delete();
                LogService::log("物理删除文章成功，ID：{$id}，标题：{$article->title}");
            } else {
                // 软删除
                $article->delete();
                LogService::log("软删除文章成功，ID：{$id}，标题：{$article->title}");
            }

            return [
                'code' => 200,
                'msg' => '删除成功'
            ];
        } catch (\Exception $e) {
            LogService::error($e);
            return ['code' => 500, 'msg' => '删除失败：' . $e->getMessage()];
        }
    }

    /**
     * 恢复已删除的文章
     * @param int $id 文章ID
     * @return array
     */
    public static function restoreArticle(int $id): array
    {
        try {
            // 查询已删除的文章
            $article = articleModel::onlyTrashed()->find($id);
            if (!$article) {
                LogService::log("恢复文章失败，ID不存在或未被删除：{$id}", [], 'warning');
                return ['code' => 404, 'msg' => '文章不存在或未被删除'];
            }

            // 恢复文章
            $article->restore();

            LogService::log("恢复文章成功，ID：{$id}，标题：{$article->title}");

            return [
                'code' => 200,
                'msg' => '恢复成功',
                'data' => $article
            ];
        } catch (\Exception $e) {
            LogService::error($e);
            return ['code' => 500, 'msg' => '恢复失败：' . $e->getMessage()];
        }
    }

    /**
     * 获取已删除的文章列表
     * @param array $params 查询参数
     * @return array
     */
    public static function getDeletedArticles(array $params = []): array
    {
        // 强制设置只查询已删除的文章
        $params['delete_status'] = 'only_deleted';
        return self::selectArticleAll($params);
    }

    /**
     * 更新文章
     * @param int $id 文章ID
     * @param array $data 文章数据
     * @return array
     */
    public static function updateArticle(int $id, array $data): array
    {
        // 开启事务
        Db::startTrans();
        try {
            // 查询文章是否存在
            $article = articleModel::find($id);
            if (!$article) {
                LogService::log("更新文章失败，ID不存在：{$id}", [], 'warning');
                return ['code' => 404, 'msg' => '文章不存在'];
            }

            // 更新文章
            $article->save($data);

            // 如果有标签数据，需要更新标签关联
            if (isset($data['tags']) && is_array($data['tags'])) {
                // 先删除旧关联
                Db::name('article_tag')->where('article_id', $id)->delete();

                // 使用统一的标签处理方法
                if (!self::handleArticleTags($id, $data['tags'])) {
                    throw new \Exception('标签关联处理失败');
                }
            }

            // 如果有权限数据，需要更新权限关联
            if (isset($data['visibility']) && in_array($data['visibility'], ['specific_users', 'specific_roles'])) {
                if (!self::saveArticleAccess($id, $data)) {
                    throw new \Exception('权限关联处理失败');
                }
            }

            // 提交事务
            Db::commit();

            LogService::log("更新文章成功，ID：{$id}，标题：{$article->title}");

            return [
                'code' => 200,
                'msg' => '更新成功',
                'data' => $article
            ];
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            LogService::error($e);
            return ['code' => 500, 'msg' => '更新失败：' . $e->getMessage()];
        }
    }

    /**
     * 处理文章标签关联
     * @param int $articleId 文章ID
     * @param array $tags 标签数据
     * @return bool
     */
    public static function handleArticleTags(int $articleId, array $tags): bool
    {
        try {
            if (empty($tags)) {
                return true;
            }

            $tagsData = [];
            foreach ($tags as $tag) {
                // 生成唯一ID，避免重复
                $tagId = \utils\NumUtil::generateNumberCode();

                // 检查ID是否已存在，如果存在则重新生成
                $attempts = 0;
                while (Db::name('article_tag')->where('id', $tagId)->find() && $attempts < 10) {
                    $tagId = \utils\NumUtil::generateNumberCode();
                    $attempts++;
                }

                // 如果尝试10次仍然重复，使用更安全的备用策略
                if ($attempts >= 10) {
                    // 使用微秒时间戳的后5位 + 随机数，确保是5位数字
                    $microtime = (int)(microtime(true) * 1000000);
                    $tagId = ($microtime % 90000) + 10000; // 确保是10000-99999之间的5位数

                    // 最后检查一次，如果还冲突就抛出异常
                    if (Db::name('article_tag')->where('id', $tagId)->find()) {
                        throw new \Exception("无法生成唯一的标签ID，请稍后重试");
                    }
                }

                $tagsData[] = [
                    'id' => $tagId,
                    'article_id' => $articleId,
                    'category_id' => $tag['category_id'],
                    'create_time' => date('Y-m-d H:i:s')
                ];
            }

            if (!empty($tagsData)) {
                Db::name('article_tag')->insertAll($tagsData);
            }

            return true;
        } catch (\Exception $e) {
            LogService::error($e);
            return false;
        }
    }

    /**
     * 保存文章访问权限
     * @param int $articleId 文章ID
     * @param array $data 权限数据（包含access_users和access_roles）
     * @return bool
     */
    public static function saveArticleAccess(int $articleId, array $data): bool
    {
        try {
            // 保存指定用户权限
            if (isset($data['access_users']) && is_array($data['access_users'])) {
                // 先删除旧权限
                Db::name('article_user_access')->where('article_id', $articleId)->delete();

                // 插入新权限
                if (!empty($data['access_users'])) {
                    $accessData = [];
                    foreach ($data['access_users'] as $userId) {
                        $accessData[] = [
                            'article_id' => $articleId,
                            'user_id' => $userId,
                            'create_time' => date('Y-m-d H:i:s')
                        ];
                    }
                    Db::name('article_user_access')->insertAll($accessData);
                    LogService::log("保存文章用户权限成功，文章ID：{$articleId}，用户数：" . count($accessData));
                }
            }

            // 保存指定角色权限
            if (isset($data['access_roles']) && is_array($data['access_roles'])) {
                // 先删除旧权限
                Db::name('article_role_access')->where('article_id', $articleId)->delete();

                // 插入新权限
                if (!empty($data['access_roles'])) {
                    $accessData = [];
                    foreach ($data['access_roles'] as $roleId) {
                        $accessData[] = [
                            'article_id' => $articleId,
                            'role_id' => $roleId,
                            'create_time' => date('Y-m-d H:i:s')
                        ];
                    }
                    Db::name('article_role_access')->insertAll($accessData);
                    LogService::log("保存文章角色权限成功，文章ID：{$articleId}，角色数：" . count($accessData));
                }
            }

            return true;
        } catch (\Exception $e) {
            LogService::error($e);
            return false;
        }
    }
}
