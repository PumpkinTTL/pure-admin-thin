# 后端文章权限控制实现指南

## 概述
本文档说明如何在后端实现文章的权限控制功能，包括保存权限数据和验证访问权限。

## 数据库修改

### 1. 文章表 (articles) 新增字段

```sql
ALTER TABLE articles ADD COLUMN visibility VARCHAR(20) DEFAULT 'public' COMMENT '可见性: public-公开, private-私有, password-密码访问, specified_users-指定用户, specified_roles-指定角色';
```

### 2. 创建文章用户权限关联表 (article_user_access)

```sql
CREATE TABLE article_user_access (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    article_id BIGINT UNSIGNED NOT NULL COMMENT '文章ID',
    user_id BIGINT UNSIGNED NOT NULL COMMENT '用户ID',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_article_id (article_id),
    INDEX idx_user_id (user_id),
    UNIQUE KEY uk_article_user (article_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文章用户访问权限表';
```

### 3. 创建文章角色权限关联表 (article_role_access)

```sql
CREATE TABLE article_role_access (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    article_id BIGINT UNSIGNED NOT NULL COMMENT '文章ID',
    role_id BIGINT UNSIGNED NOT NULL COMMENT '角色ID',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_article_id (article_id),
    INDEX idx_role_id (role_id),
    UNIQUE KEY uk_article_role (article_id, role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='文章角色访问权限表';
```

## 后端 Model 修改

### Article Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $fillable = [
        // ... 现有字段 ...
        'visibility',  // 新增
    ];

    /**
     * 文章授权用户关联
     */
    public function accessUsers()
    {
        return $this->belongsToMany(User::class, 'article_user_access', 'article_id', 'user_id')
            ->withTimestamps();
    }

    /**
     * 文章授权角色关联
     */
    public function accessRoles()
    {
        return $this->belongsToMany(Role::class, 'article_role_access', 'article_id', 'role_id')
            ->withTimestamps();
    }
}
```

## Controller 修改

### ArticleController 添加/更新方法修改

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ArticleController extends Controller
{
    /**
     * 添加文章
     */
    public function add(Request $request)
    {
        try {
            DB::beginTransaction();

            $data = $request->all();
            
            // 提取权限相关数据
            $visibility = $data['visibility'] ?? 'public';
            $accessUsers = $data['access_users'] ?? [];
            $accessRoles = $data['access_roles'] ?? [];
            
            // 移除不属于articles表的字段
            unset($data['access_users'], $data['access_roles']);
            
            // 创建文章
            $article = Article::create($data);

            // 保存权限数据
            $this->saveArticlePermissions($article, $visibility, $accessUsers, $accessRoles);

            DB::commit();

            return response()->json([
                'code' => 200,
                'msg' => '文章创建成功',
                'data' => $article
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'code' => 500,
                'msg' => '文章创建失败: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 更新文章
     */
    public function update(Request $request)
    {
        try {
            DB::beginTransaction();

            $data = $request->all();
            $articleId = $data['id'] ?? null;

            if (!$articleId) {
                return response()->json([
                    'code' => 400,
                    'msg' => '文章ID不能为空'
                ], 400);
            }

            $article = Article::find($articleId);
            if (!$article) {
                return response()->json([
                    'code' => 404,
                    'msg' => '文章不存在'
                ], 404);
            }

            // 提取权限相关数据
            $visibility = $data['visibility'] ?? 'public';
            $accessUsers = $data['access_users'] ?? [];
            $accessRoles = $data['access_roles'] ?? [];
            
            // 移除不属于articles表的字段
            unset($data['access_users'], $data['access_roles']);

            // 更新文章
            $article->update($data);

            // 更新权限数据
            $this->saveArticlePermissions($article, $visibility, $accessUsers, $accessRoles);

            DB::commit();

            return response()->json([
                'code' => 200,
                'msg' => '文章更新成功',
                'data' => $article
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'code' => 500,
                'msg' => '文章更新失败: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 保存文章权限数据
     */
    private function saveArticlePermissions($article, $visibility, $accessUsers, $accessRoles)
    {
        // 更新可见性
        $article->visibility = $visibility;
        $article->save();

        // 清除旧的权限关联
        $article->accessUsers()->detach();
        $article->accessRoles()->detach();

        // 保存新的权限关联
        if ($visibility === 'specified_users' && !empty($accessUsers)) {
            $article->accessUsers()->attach($accessUsers);
        }

        if ($visibility === 'specified_roles' && !empty($accessRoles)) {
            $article->accessRoles()->attach($accessRoles);
        }
    }

    /**
     * 获取文章详情（带权限验证）
     */
    public function selectArticleById(Request $request)
    {
        try {
            $articleId = $request->input('id');
            if (!$articleId) {
                return response()->json([
                    'code' => 400,
                    'msg' => '文章ID不能为空'
                ], 400);
            }

            $article = Article::with(['accessUsers', 'accessRoles'])->find($articleId);
            if (!$article) {
                return response()->json([
                    'code' => 404,
                    'msg' => '文章不存在'
                ], 404);
            }

            // 权限验证
            $user = auth()->user();
            if (!$this->canAccessArticle($article, $user)) {
                return response()->json([
                    'code' => 403,
                    'msg' => '您没有权限访问此文章'
                ], 403);
            }

            return response()->json([
                'code' => 200,
                'msg' => '获取成功',
                'data' => $article
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'msg' => '获取文章失败: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 检查用户是否有权限访问文章
     */
    private function canAccessArticle($article, $user = null)
    {
        // 如果文章是公开的，任何人都可以访问
        if ($article->visibility === 'public') {
            return true;
        }

        // 如果没有登录用户，只能访问公开文章
        if (!$user) {
            return false;
        }

        // 作者本人可以访问
        if ($article->author_id === $user->id) {
            return true;
        }

        // 管理员可以访问所有文章
        if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
            return true;
        }

        // 根据可见性进行权限判断
        switch ($article->visibility) {
            case 'private':
                // 私有文章只有作者可以访问（已在上面检查）
                return false;

            case 'specified_users':
                // 检查用户是否在授权列表中
                return $article->accessUsers()->where('user_id', $user->id)->exists();

            case 'specified_roles':
                // 检查用户的角色是否在授权列表中
                $userRoleIds = $user->roles()->pluck('id')->toArray();
                $articleRoleIds = $article->accessRoles()->pluck('role_id')->toArray();
                return !empty(array_intersect($userRoleIds, $articleRoleIds));

            case 'password':
                // 密码保护的文章需要单独处理，这里暂时返回false
                // 实际应该检查session中是否已验证密码
                return false;

            default:
                return false;
        }
    }

    /**
     * 获取文章列表（带权限过滤）
     */
    public function getArticleList(Request $request)
    {
        try {
            $user = auth()->user();
            
            $query = Article::query();

            // 如果用户未登录，只显示公开文章
            if (!$user) {
                $query->where('visibility', 'public');
            } else {
                // 如果用户已登录但不是管理员，需要进行权限过滤
                if (!$user->hasRole('admin') && !$user->hasRole('super_admin')) {
                    $query->where(function ($q) use ($user) {
                        $q->where('visibility', 'public')
                          ->orWhere('author_id', $user->id)
                          ->orWhere(function ($subQ) use ($user) {
                              $subQ->where('visibility', 'specified_users')
                                   ->whereHas('accessUsers', function ($userQ) use ($user) {
                                       $userQ->where('user_id', $user->id);
                                   });
                          })
                          ->orWhere(function ($subQ) use ($user) {
                              $subQ->where('visibility', 'specified_roles')
                                   ->whereHas('accessRoles', function ($roleQ) use ($user) {
                                       $userRoleIds = $user->roles()->pluck('id')->toArray();
                                       $roleQ->whereIn('role_id', $userRoleIds);
                                   });
                          });
                    });
                }
                // 管理员可以看到所有文章，不需要额外过滤
            }

            // 分页
            $pageSize = $request->input('page_size', 10);
            $articles = $query->paginate($pageSize);

            return response()->json([
                'code' => 200,
                'msg' => '获取成功',
                'data' => $articles
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'msg' => '获取文章列表失败: ' . $e->getMessage()
            ], 500);
        }
    }
}
```

## 前端数据格式说明

前端提交的数据格式：
```javascript
{
  // ... 其他文章字段 ...
  visibility: 'public' | 'private' | 'password' | 'specified_users' | 'specified_roles',
  access_users: [1, 2, 3], // 用户ID数组（当visibility为specified_users时）
  access_roles: [1, 2]     // 角色ID数组（当visibility为specified_roles时）
}
```

后端返回文章详情时的数据格式：
```javascript
{
  code: 200,
  msg: '获取成功',
  data: {
    // ... 文章基本信息 ...
    visibility: 'specified_users',
    access_users: [
      { id: 1, username: 'user1', ... }
    ],
    access_roles: [
      { id: 1, name: 'editor', ... }
    ]
  }
}
```

## 测试场景

### 1. 公开文章
- 任何人都可以查看
- 前端不显示权限设置

### 2. 私有文章
- 只有作者本人可以查看
- 其他用户访问时返回403错误

### 3. 指定用户访问
- 只有指定的用户可以查看
- 前端显示已授权用户列表
- 其他用户访问时返回403错误

### 4. 指定角色访问
- 只有拥有指定角色的用户可以查看
- 前端显示已授权角色列表
- 其他用户访问时返回403错误

### 5. 管理员特权
- 管理员可以查看所有文章，不受权限限制
- 作者可以查看和编辑自己的文章

## 注意事项

1. 所有数据库操作使用事务，确保数据一致性
2. 权限验证在控制器层完成
3. 管理员和作者本人拥有特殊权限
4. 列表查询时自动过滤无权限的文章
5. 详情查询时返回403错误提示无权限
6. 前端应该根据用户身份和文章权限显示相应的操作按钮

## API 端点

以下API端点需要添加或修改权限验证：

- `POST /api/v1/article/add` - 创建文章（保存权限数据）
- `POST /api/v1/article/update` - 更新文章（更新权限数据）
- `GET /api/v1/article/selectArticleById` - 获取文章详情（验证权限）
- `GET /api/v1/article/getArticleList` - 获取文章列表（过滤权限）
- `GET /api/v1/article/selectArticleAll` - 管理后台文章列表（管理员专用）

