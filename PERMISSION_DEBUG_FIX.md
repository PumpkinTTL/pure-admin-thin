# 权限过滤问题全面排查和修复

## 问题现象
- ✅ public 文章可以看到
- ❌ login_required 文章登录后看不到
- ❌ 其他权限类型都看不到

## 可能的原因

### 1. 数据库字段问题 ⚠️

**检查点**：`article` 表的 `visibility` 字段

```sql
-- 检查表结构
DESC article;

-- 检查现有数据
SELECT id, title, visibility FROM article LIMIT 10;

-- 检查 visibility 字段是否为 NULL
SELECT COUNT(*) as null_count FROM article WHERE visibility IS NULL;
```

**问题**：
- 如果旧文章的 `visibility` 字段是 `NULL`，它们不会匹配任何条件
- 如果字段不存在，查询会报错

**解决方案**：
```sql
-- 1. 如果字段不存在，添加字段
ALTER TABLE article ADD COLUMN visibility VARCHAR(20) DEFAULT 'public';

-- 2. 更新所有 NULL 值为 'public'
UPDATE article SET visibility = 'public' WHERE visibility IS NULL OR visibility = '';

-- 3. 为了安全，再次确认
SELECT visibility, COUNT(*) as count FROM article GROUP BY visibility;
```

### 2. 中间件问题 ⚠️

**检查点**：`ArticleAuth.php` 中间件是否正确传递用户信息

**当前代码**（第 61-62 行）：
```php
$request->currentUserId = $userId;
$request->currentUserRoles = $userRoles;
```

**检查方法**：在 Controller 中添加调试输出

```php
// 在 article.php Controller 的 selectArticleAll 或 getArticleList 方法中添加
public function selectArticleAll()
{
    $params = request()->param();
    
    // 调试输出
    echo "DEBUG - currentUserId: " . (request()->currentUserId ?? 'NULL') . "\n";
    echo "DEBUG - currentUserRoles: " . json_encode(request()->currentUserRoles ?? []) . "\n";
    
    $params['current_user_id'] = request()->currentUserId ?? 0;
    $params['current_user_roles'] = request()->currentUserRoles ?? [];
    
    // ... 后续代码
}
```

### 3. SQL 逻辑问题 ⚠️

**问题**：ThinkPHP 的 `whereOr` 用法

**错误用法**：
```php
$q->whereOr('visibility', 'public');  // ❌ 第一个条件不应该用 whereOr
```

**正确用法**：
```php
$q->where('visibility', 'public');    // ✅ 第一个条件用 where
$q->whereOr('author_id', $userId);    // ✅ 后续条件用 whereOr
```

或者全部用 `whereOr`：
```php
$q->where(function($q2) {
    $q2->where('visibility', 'public');
})->whereOr(function($q2) {
    $q2->where('author_id', $userId);
});
```

### 4. 空数组判断问题 ⚠️

**问题**：`!empty([])` 返回 `false`

**当前代码**（第 79 行）：
```php
if (!empty($currentUserRoles)) {  // ❌ 空数组会被判断为 empty
```

**修复后**：
```php
if (is_array($currentUserRoles) && count($currentUserRoles) > 0) {  // ✅
```

## 修复步骤

### 步骤 1: 检查并修复数据库

```sql
-- 1. 检查 visibility 字段
SHOW COLUMNS FROM article LIKE 'visibility';

-- 2. 如果没有，添加字段
ALTER TABLE article ADD COLUMN visibility VARCHAR(20) DEFAULT 'public' COMMENT '文章可见性';

-- 3. 更新现有数据
UPDATE article SET visibility = 'public' WHERE visibility IS NULL OR visibility = '';

-- 4. 验证数据
SELECT visibility, COUNT(*) FROM article GROUP BY visibility;
```

### 步骤 2: 修复 Service 层 SQL 逻辑

文件：`app/api/services/articleService.php`

**当前实现已修复**：
- ✅ 第一个条件用 `where()`
- ✅ 后续条件用 `whereOr()`
- ✅ 角色判断改为 `count($currentUserRoles) > 0`
- ✅ 添加了调试日志

### 步骤 3: 验证中间件

文件：`app/api/middleware/ArticleAuth.php`

**检查**：
1. JWT Token 是否正确解析
2. 用户ID是否正确提取
3. 角色信息是否正确查询

**验证方法**：
```php
// 在中间件的第 50 行后添加
LogService::log("ArticleAuth中间件 - 用户ID: {$userId}, 角色: " . json_encode($userRoles), [], 'info');
```

### 步骤 4: 测试 SQL 语句

**手动测试**：
```sql
-- 测试公开文章
SELECT * FROM article WHERE visibility = 'public';

-- 测试登录可见（假设用户ID=1）
SELECT * FROM article WHERE visibility = 'public' OR author_id = 1 OR visibility = 'login_required';

-- 检查是否有 visibility 为 NULL 的数据
SELECT COUNT(*) FROM article WHERE visibility IS NULL;
```

### 步骤 5: 查看日志

**日志位置**：
- ThinkPHP 日志：`runtime/log/`
- 查找关键字：`文章列表查询`、`用户ID`、`角色`

**示例日志输出**：
```
[2024-xx-xx] 文章列表查询 - 用户ID: 1, 角色: [1,2]
[2024-xx-xx] 生成的SQL: SELECT * FROM article WHERE (visibility='public' OR author_id=1 OR visibility='login_required') ...
```

## 最终修复代码

### articleService.php（第 48-92 行）

```php
// 权限过滤逻辑（除非禁用权限过滤）
if (!isset($params['skip_permission_filter']) || !$params['skip_permission_filter']) {
    $query->where(function($q) use ($currentUserId, $currentUserRoles) {
        // 使用 OR 连接所有允许访问的条件
        
        // 1. 公开文章（第一个条件用 where）
        $q->where('visibility', 'public');
        
        // 2. 作者自己的文章
        if ($currentUserId > 0) {
            $q->whereOr('author_id', $currentUserId);
        }
        
        // 3. 登录可见的文章（用户已登录）
        if ($currentUserId > 0) {
            $q->whereOr('visibility', 'login_required');
        }
        
        // 4. 指定用户可见的文章
        if ($currentUserId > 0) {
            $q->whereOr(function($q2) use ($currentUserId) {
                $q2->where('visibility', 'specific_users')
                   ->whereExists(function($q3) use ($currentUserId) {
                       $q3->table('article_user_access')
                          ->whereRaw('article_user_access.article_id = article.id')
                          ->where('article_user_access.user_id', $currentUserId);
                   });
            });
        }
        
        // 5. 指定角色可见的文章
        if (is_array($currentUserRoles) && count($currentUserRoles) > 0) {
            $q->whereOr(function($q4) use ($currentUserRoles) {
                $q4->where('visibility', 'specific_roles')
                   ->whereExists(function($q5) use ($currentUserRoles) {
                       $q5->table('article_role_access')
                          ->whereRaw('article_role_access.article_id = article.id')
                          ->whereIn('article_role_access.role_id', $currentUserRoles);
                   });
            });
        }
    });
}
```

## 测试清单

### 1. 数据库测试
- [ ] `visibility` 字段存在
- [ ] 没有 `NULL` 或空值
- [ ] 至少有一篇 `login_required` 的文章

### 2. 未登录测试
- [ ] 只能看到 `public` 文章
- [ ] 看不到 `login_required` 文章

### 3. 已登录测试
- [ ] 能看到 `public` 文章
- [ ] 能看到 `login_required` 文章 ✅
- [ ] 能看到自己创建的所有文章

### 4. 权限测试
- [ ] 指定用户权限生效
- [ ] 指定角色权限生效

### 5. 日志测试
- [ ] 中间件日志正确输出用户ID和角色
- [ ] Service层日志正确输出用户ID和角色
- [ ] SQL日志正确生成

## 常见错误

### 错误1：visibility 字段全是 NULL
```sql
-- 快速修复
UPDATE article SET visibility = 'public';
```

### 错误2：中间件没有传递用户信息
```php
// 检查 Controller 中是否正确获取
$params['current_user_id'] = request()->currentUserId ?? 0;
```

### 错误3：数组判断错误
```php
// ❌ 错误
if (!empty($array)) 

// ✅ 正确
if (is_array($array) && count($array) > 0)
```

## 下一步操作

1. **立即执行**：检查数据库 `visibility` 字段
2. **查看日志**：确认用户信息是否正确传递
3. **测试查询**：手动运行 SQL 验证逻辑
4. **逐步调试**：从简单到复杂，先确保 `public` 和 `login_required` 工作

## 联系方式

如果问题仍然存在，请提供：
1. 数据库 `visibility` 字段的查询结果
2. 日志文件中的相关输出
3. 生成的 SQL 语句

