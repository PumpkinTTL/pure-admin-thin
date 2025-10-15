# 后端权限控制 SQL 错误修复说明

## 错误信息

```
SQLSTATE[23000]: Integrity constraint violation: 1052 Column 'id' in field list is ambiguous
```

## 错误原因

在使用 `belongsToMany` 多对多关联查询时，由于涉及多个表的 JOIN 操作，当使用 `field()` 方法限制查询字段时，如果不明确指定表名，会导致 `id` 字段产生歧义（因为多个表都有 `id` 字段）。

## 问题代码位置

### 1. article.php (Model) - 第 122-148 行

**问题**：`belongsToMany` 的参数顺序错误

原代码：
```php
public function accessUsers()
{
    return $this->belongsToMany(
        users::class,
        'article_user_access',
        'user_id',        // ❌ 错误：应该是 article_id
        'article_id'      // ❌ 错误：应该是 user_id
    );
}

public function accessRoles()
{
    return $this->belongsToMany(
        roles::class,
        'article_role_access',
        'role_id',        // ❌ 错误：应该是 article_id
        'article_id'      // ❌ 错误：应该是 role_id
    );
}
```

修复后：
```php
public function accessUsers()
{
    return $this->belongsToMany(
        users::class,           // 关联模型
        'article_user_access',  // 中间表
        'article_id',           // ✅ 当前模型外键（article在中间表的字段）
        'user_id'               // ✅ 关联模型外键（user在中间表的字段）
    );
}

public function accessRoles()
{
    return $this->belongsToMany(
        roles::class,           // 关联模型
        'article_role_access',  // 中间表
        'article_id',           // ✅ 当前模型外键（article在中间表的字段）
        'role_id'               // ✅ 关联模型外键（role在中间表的字段）
    );
}
```

**参数说明**：
```php
belongsToMany(关联模型, 中间表名, 当前模型外键, 关联模型外键)
```

### 2. articleService.php - 第 22-46 行

**问题**：在 `with()` 中使用 `field()` 限制字段时，没有使用表名前缀

原代码（已修复前的尝试）：
```php
'accessUsers' => function($query) {
    $query->field(['id', 'username']);  // ❌ id 字段有歧义
},
'accessRoles' => function($query) {
    $query->field(['id', 'name']);      // ❌ id 字段有歧义
}
```

**最佳修复方案**：不限制字段，让 ThinkPHP 自动处理

修复后：
```php
// 加载权限关联数据（不限制字段，让ThinkPHP自动处理）
'accessUsers',
'accessRoles'
```

**原因**：
- ThinkPHP 的 `belongsToMany` 会自动处理多表 JOIN 和字段选择
- 手动限制字段可能导致 SQL 歧义或缺少必要的关联字段
- 让框架自动处理可以避免这类问题

## 修复文件清单

### 1. article.php (Model)
**路径**：`src/admin/m-service-server/app/api/model/article.php`

**修改内容**：
- ✅ 修正 `accessUsers()` 方法的 `belongsToMany` 参数顺序
- ✅ 修正 `accessRoles()` 方法的 `belongsToMany` 参数顺序
- ✅ 添加详细注释说明参数含义

### 2. articleService.php (Service)
**路径**：`src/admin/m-service-server/app/api/services/articleService.php`

**修改位置 1** - `selectArticleAll()` 方法（第 22-46 行）：
- ✅ 移除 `accessUsers` 和 `accessRoles` 的 `field()` 限制
- ✅ 改为简单的字符串关联名称，让 ThinkPHP 自动处理

**修改位置 2** - `selectArticleById()` 方法（第 245-261 行）：
- ✅ 添加 `accessUsers` 和 `accessRoles` 关联加载
- ✅ 确保详情查询时也能获取权限数据

## ThinkPHP belongsToMany 参数说明

### 标准格式
```php
$this->belongsToMany(
    关联模型类名,      // 第1个参数：要关联的模型
    中间表名,          // 第2个参数：多对多中间表
    当前模型外键,      // 第3个参数：当前模型在中间表的字段名
    关联模型外键       // 第4个参数：关联模型在中间表的字段名
);
```

### 示例对比

#### 文章关联用户（accessUsers）
- 当前模型：article
- 关联模型：users
- 中间表：article_user_access

```php
// ✅ 正确
$this->belongsToMany(users::class, 'article_user_access', 'article_id', 'user_id');

// ❌ 错误
$this->belongsToMany(users::class, 'article_user_access', 'user_id', 'article_id');
```

#### 文章关联角色（accessRoles）
- 当前模型：article
- 关联模型：roles
- 中间表：article_role_access

```php
// ✅ 正确
$this->belongsToMany(roles::class, 'article_role_access', 'article_id', 'role_id');

// ❌ 错误
$this->belongsToMany(roles::class, 'article_role_access', 'role_id', 'article_id');
```

## 测试验证

### 1. 测试文章列表查询
```bash
GET /api/v1/article/selectArticleAll
```

预期结果：
- ✅ 返回文章列表
- ✅ 每篇文章包含 `access_users` 和 `access_roles` 数据（如果有）
- ✅ 无 SQL 错误

### 2. 测试文章详情查询
```bash
GET /api/v1/article/selectArticleById?id=xxx
```

预期结果：
- ✅ 返回文章详情
- ✅ 包含完整的权限关联数据
- ✅ 无 SQL 错误

### 3. 测试权限过滤
```bash
GET /api/v1/article/getArticleList
```

预期结果：
- ✅ 根据当前用户权限过滤文章列表
- ✅ 只显示有权限访问的文章
- ✅ 无 SQL 错误

## 相关数据库表

### article_user_access (文章用户权限表)
```sql
CREATE TABLE article_user_access (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    article_id BIGINT UNSIGNED NOT NULL,  -- 文章ID
    user_id BIGINT UNSIGNED NOT NULL,     -- 用户ID
    create_time TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    update_time TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_article_id (article_id),
    INDEX idx_user_id (user_id),
    UNIQUE KEY uk_article_user (article_id, user_id)
);
```

### article_role_access (文章角色权限表)
```sql
CREATE TABLE article_role_access (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    article_id BIGINT UNSIGNED NOT NULL,  -- 文章ID
    role_id BIGINT UNSIGNED NOT NULL,     -- 角色ID
    create_time TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    update_time TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_article_id (article_id),
    INDEX idx_role_id (role_id),
    UNIQUE KEY uk_article_role (article_id, role_id)
);
```

## 注意事项

1. **参数顺序很重要**：`belongsToMany` 的第3、4个参数顺序不能错
2. **避免字段歧义**：在多表关联时，要么使用表名前缀，要么不限制字段
3. **让框架处理**：对于复杂的多对多关联，建议不手动限制字段，让框架自动处理
4. **测试完整性**：修改后需要测试列表查询、详情查询和权限过滤功能

## 故障排查

如果仍然出现 SQL 错误：

1. **检查表是否存在**
   ```sql
   SHOW TABLES LIKE 'article_user_access';
   SHOW TABLES LIKE 'article_role_access';
   ```

2. **检查表结构**
   ```sql
   DESC article_user_access;
   DESC article_role_access;
   ```

3. **检查关联模型是否存在**
   - `app/api/model/users.php`
   - `app/api/model/roles.php`

4. **开启 SQL 日志**
   在 `config/database.php` 中设置：
   ```php
   'debug' => true,
   ```
   
5. **查看生成的 SQL**
   在控制器中添加：
   ```php
   \think\facade\Db::listen(function($sql, $time, $master) {
       echo $sql . PHP_EOL;
   });
   ```

## 总结

本次修复主要解决了两个问题：
1. ✅ `belongsToMany` 参数顺序错误
2. ✅ 多表关联时的字段歧义问题

修复后，文章权限控制功能应该可以正常工作了。

