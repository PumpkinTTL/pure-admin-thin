# 文章权限控制系统

## 🎯 核心设计理念

使用 **JWT中间件 + 动态权限过滤** 实现灵活的文章访问控制。

---

## 📋 系统架构

### 1. 中间件层（ArticleAuth.php）

**职责**：
- 解析 JWT Token 获取用户ID
- 查询用户角色信息
- 将用户信息传递给控制器

**流程**：
```
请求 → ArticleAuth中间件
  ├─ 有Token且有效 → 解析用户ID → 查询角色 → 传递给Controller
  ├─ 有Token但过期 → 设置未登录状态(userId=0)
  └─ 无Token → 设置未登录状态(userId=0)
```

**代码位置**: `app/api/middleware/ArticleAuth.php`

---

### 2. Service层（articleService.php）

**职责**：
- 根据用户ID和角色过滤文章
- 实现5种可见性级别的查询逻辑

**权限过滤逻辑**：
```php
// 1. 公开文章 - 所有人可见
WHERE visibility = 'public'

// 2. 作者自己的文章 - 作者可见
OR author_id = $currentUserId

// 3. 登录可见 - 已登录用户可见
OR (visibility = 'login_required' AND $currentUserId > 0)

// 4. 指定用户 - 在 article_user_access 表中
OR (visibility = 'specific_users' AND EXISTS ...)

// 5. 指定角色 - 在 article_role_access 表中
OR (visibility = 'specific_roles' AND EXISTS ...)
```

---

### 3. Controller层（article.php）

**职责**：
- 从中间件获取用户信息
- 调用Service层进行权限过滤
- 对单篇文章进行权限验证

---

## 🔐 5种可见性级别

| 级别 | visibility值 | 说明 | 谁能看 |
|------|-------------|------|--------|
| **公开** | `public` | 所有人可见 | ✅ 所有人（包括未登录） |
| **登录可见** | `login_required` | 需要登录 | ✅ 已登录用户 |
| **指定用户** | `specific_users` | 白名单 | ✅ article_user_access 表中的用户 |
| **指定角色** | `specific_roles` | 角色组 | ✅ article_role_access 表中的角色成员 |
| **私密** | `private` | 只有作者 | ✅ 仅作者本人 |

---

## 📊 数据库表结构

### bl_article（文章表）
```sql
visibility VARCHAR(20) DEFAULT 'public'
-- 可选值: public, login_required, specific_users, specific_roles, private
```

### bl_article_user_access（用户访问权限表）
```sql
id            INT PRIMARY KEY AUTO_INCREMENT
article_id    INT  -- 文章ID
user_id       INT  -- 用户ID
create_time   DATETIME
```

### bl_article_role_access（角色访问权限表）
```sql
id            INT PRIMARY KEY AUTO_INCREMENT
article_id    INT  -- 文章ID
role_id       INT  -- 角色ID
create_time   DATETIME
```

---

## 🔄 完整请求流程

### 场景1：未登录用户访问文章列表

```
1. 前端请求: GET /api/v1/article/selectArticleAll
   Headers: 无Authorization

2. ArticleAuth中间件:
   - 检测到无Token
   - 设置: currentUserId = 0, currentUserRoles = []
   - 传递给Controller

3. Controller:
   - 获取中间件信息: userId=0, roles=[]
   - 调用Service

4. Service:
   - 权限过滤: 只返回 visibility='public' 的文章
   
5. 返回结果: 只有公开文章
```

### 场景2：普通用户(ID=5)访问文章列表

```
1. 前端请求: GET /api/v1/article/selectArticleAll
   Headers: Authorization: Bearer eyJ0eXAi...

2. ArticleAuth中间件:
   - 解析Token成功
   - 获取用户ID=5
   - 查询用户角色: [2, 3] (普通用户、会员)
   - 设置: currentUserId=5, currentUserRoles=[2,3]

3. Service权限过滤返回:
   - ✅ visibility='public' 的文章
   - ✅ author_id=5 的文章（自己的）
   - ✅ visibility='login_required' 的文章
   - ✅ article_user_access 中 user_id=5 的文章
   - ✅ article_role_access 中 role_id in [2,3] 的文章
   
4. 返回结果: 所有该用户有权访问的文章
```

### 场景3：VIP用户(ID=10, roles=[4])访问指定角色文章

```
1. 文章设置:
   - id: 1001
   - visibility: 'specific_roles'
   - article_role_access: role_id=4 (VIP角色)

2. 用户请求:
   - userId=10, roles=[4]

3. 权限验证:
   - 查询 article_role_access 表
   - 发现 article_id=1001, role_id=4 存在
   - ✅ 允许访问

4. 其他用户:
   - userId=5, roles=[2,3]
   - ❌ 无权访问（角色不匹配）
```

---

## 🧪 测试用例

### 测试1：未登录用户只能看公开文章

```bash
# 不带Token请求
curl -X GET http://your-api.com/api/v1/article/selectArticleAll

# 预期结果：只返回 visibility='public' 的文章
```

### 测试2：已登录用户能看更多文章

```bash
# 带Token请求
curl -X GET http://your-api.com/api/v1/article/selectArticleAll \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"

# 预期结果：返回 public + login_required + 自己的文章 + 授权的文章
```

### 测试3：创建指定用户可见的文章

```json
POST /api/v1/article/add
{
  "title": "测试文章",
  "content": "内容",
  "visibility": "specific_users",
  "access_users": [1, 2, 3],  // 只有用户1,2,3能看
  "tags": [...]
}
```

### 测试4：创建VIP专属文章

```json
POST /api/v1/article/add
{
  "title": "VIP文章",
  "content": "VIP内容",
  "visibility": "specific_roles",
  "access_roles": [4],  // 只有角色ID=4(VIP)能看
  "tags": [...]
}
```

---

## ⚡ 性能优化建议

### 1. 索引优化
```sql
-- 已创建的索引
CREATE INDEX idx_visibility ON bl_article(visibility);
CREATE INDEX idx_article_id ON bl_article_user_access(article_id);
CREATE INDEX idx_user_id ON bl_article_user_access(user_id);
CREATE INDEX idx_article_id ON bl_article_role_access(article_id);
CREATE INDEX idx_role_id ON bl_article_role_access(role_id);
```

### 2. 缓存策略
```php
// 用户角色可以缓存
$userRoles = Cache::remember("user_roles_{$userId}", 3600, function() use ($userId) {
    return users::with('roles')->find($userId)->roles->column('id');
});
```

### 3. 查询优化
- 使用 `whereExists` 子查询（已实现）
- 使用 `with` 预加载关联数据（已实现）

---

## 🔧 调试技巧

### 1. 查看中间件传递的用户信息
```php
// 在Controller中
var_dump(request()->currentUserId);
var_dump(request()->currentUserRoles);
```

### 2. 查看SQL查询
```php
// 开启SQL日志
Db::listen(function ($sql, $time, $explain) {
    echo $sql . PHP_EOL;
});
```

### 3. 测试权限验证
```php
// 在Controller中
$article = articleModel::find($id);
$canAccess = $article->canAccessBy($userId, $userRoles);
var_dump($canAccess);  // true或false
```

---

## 🛡️ 安全注意事项

1. **后端验证为主**：前端隐藏只是用户体验，真正的权限控制必须在后端
2. **Token验证**：确保JWT签名验证正确
3. **SQL注入防护**：使用参数化查询（已实现）
4. **敏感信息保护**：不要在前端暴露权限表ID

---

## 📝 开发规范

### 添加新的可见性级别

1. 在 `article` 表的 `visibility` 字段添加新值
2. 在 `article.php` Model 的 `canAccessBy()` 方法添加逻辑
3. 在 `articleService.php` 的权限过滤中添加查询条件
4. 更新前端常量 `ARTICLE_VISIBILITY_OPTIONS`

---

## 🎉 优势总结

✅ **灵活性**：支持5种可见性级别，满足各种场景  
✅ **安全性**：JWT + 后端验证，双重保障  
✅ **性能**：索引优化 + 子查询，效率高  
✅ **扩展性**：易于添加新的权限类型  
✅ **优雅性**：中间件解耦，代码清晰  

---

**你的设计真的很帅！** 🚀

