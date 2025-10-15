# 权限过滤逻辑修复说明

## 问题描述

登录后无法看到"登录可见"的文章，权限过滤逻辑存在以下问题：

1. **SQL OR 逻辑错误**：在 ThinkPHP 中，`where()->whereOr()` 的链式调用会导致逻辑错误
2. **字段值不一致**：前端使用 `specified_users/specified_roles`，后端和常量定义使用 `specific_users/specific_roles`

## 修复内容

### 1. 修复 SQL 权限过滤逻辑 ✅

**文件**：`src/admin/m-service-server/app/api/services/articleService.php`

**问题代码**（第 47-81 行）：
```php
$query->where(function($q) use ($currentUserId, $currentUserRoles) {
    // ❌ 错误：第一个条件用 where，后续用 whereOr，会导致逻辑混乱
    $q->where('visibility', 'public')
      ->whereOr('author_id', $currentUserId);
    
    if ($currentUserId > 0) {
        $q->whereOr('visibility', 'login_required');
    }
    // ... 其他条件
});
```

**修复后**：
```php
$query->where(function($q) use ($currentUserId, $currentUserRoles) {
    // ✅ 正确：所有条件都用 whereOr，形成 OR 关系
    
    // 1. 公开文章
    $q->whereOr('visibility', 'public');
    
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
    if (!empty($currentUserRoles)) {
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
```

**关键修改**：
- 第一个条件也改为 `whereOr()`，确保所有条件都是 OR 关系
- 添加 `if ($currentUserId > 0)` 判断，只有登录用户才能看到需要登录的内容
- 保持代码结构清晰，便于维护

### 2. 统一字段值命名 ✅

**问题**：前端使用的字段值与常量定义不一致

**修复文件**：`src/views/basic/article/AddOrEdit.vue`

修改位置：
1. **第 509 行**：`'specified_users'` → `'specific_users'`
2. **第 512 行**：`'specified_roles'` → `'specific_roles'`
3. **第 517 行**：`'specified_users'` → `'specific_users'`
4. **第 520 行**：`'specified_roles'` → `'specific_roles'`
5. **第 567-568 行**：添加 `'login_required'` 和修正其他值
6. **第 579-582 行**：添加登录可见说明
7. **第 1053 行**：`'specified_users'` → `'specific_users'`
8. **第 1056 行**：`'specified_roles'` → `'specific_roles'`
9. **第 122 行（模板）**：`'specified_users'` → `'specific_users'`
10. **第 150 行（模板）**：`'specified_roles'` → `'specific_roles'`

**正确的字段值**（与常量定义一致）：
- `'public'` - 公开
- `'login_required'` - 登录可见
- `'specific_users'` - 指定用户
- `'specific_roles'` - 指定角色  
- `'private'` - 私密

## 权限过滤逻辑说明

### 访问规则（OR 关系）

用户可以看到以下任意一种类型的文章：

1. **公开文章**
   - `visibility = 'public'`
   - 所有人可见，包括未登录用户

2. **作者自己的文章**
   - `author_id = 当前用户ID`
   - 作者可以看到自己的所有文章，不受可见性限制

3. **登录可见的文章**
   - `visibility = 'login_required'` AND `用户已登录`
   - 只有登录用户可见

4. **指定用户可见的文章**
   - `visibility = 'specific_users'` AND `用户在授权列表中`
   - 通过 `article_user_access` 表验证

5. **指定角色可见的文章**
   - `visibility = 'specific_roles'` AND `用户角色在授权列表中`
   - 通过 `article_role_access` 表验证

### SQL 逻辑图示

```
SELECT * FROM article WHERE (
    visibility = 'public'                          -- 公开文章
    OR author_id = {currentUserId}                 -- 作者自己的
    OR visibility = 'login_required'               -- 登录可见
    OR (                                           -- 指定用户
        visibility = 'specific_users' 
        AND EXISTS (
            SELECT 1 FROM article_user_access 
            WHERE article_id = article.id 
            AND user_id = {currentUserId}
        )
    )
    OR (                                           -- 指定角色
        visibility = 'specific_roles' 
        AND EXISTS (
            SELECT 1 FROM article_role_access 
            WHERE article_id = article.id 
            AND role_id IN ({currentUserRoles})
        )
    )
)
```

## 测试验证

### 1. 未登录用户
```
预期：只能看到 visibility='public' 的文章
```

### 2. 已登录普通用户
```
预期：可以看到
- visibility='public' 的文章
- visibility='login_required' 的文章
- 自己创建的所有文章
- 授权给自己的 specific_users 文章
- 授权给自己角色的 specific_roles 文章
```

### 3. 文章作者
```
预期：可以看到自己的所有文章，不受 visibility 限制
```

### 4. 管理员
```
预期：根据中间件配置，可能可以看到所有文章
```

## 相关文件

### 后端
- ✅ `app/api/services/articleService.php` - 权限过滤逻辑
- ✅ `app/api/model/article.php` - Model 关联关系
- ✅ `app/api/controller/v1/article.php` - 控制器

### 前端
- ✅ `src/views/basic/article/AddOrEdit.vue` - 文章编辑表单
- ✅ `src/constants/article.ts` - 常量定义

## 常见问题

### Q1: 为什么要用 whereOr() 而不是 where() + whereOr()？

**A**: 在 ThinkPHP 中，第一个 `where()` 会用 AND 连接到外层条件，而 `whereOr()` 才是在闭包内部形成 OR 关系。所以闭包内所有条件都应该用 `whereOr()`。

### Q2: 为什么要判断 $currentUserId > 0？

**A**: 因为未登录用户的 `$currentUserId` 为 0，他们不应该看到需要登录才能访问的内容。

### Q3: 字段值为什么要统一？

**A**: 前端、后端和数据库必须使用相同的字段值，否则会导致保存和查询不匹配。建议所有地方都参考 `article.ts` 中的常量定义。

## 注意事项

1. **管理员权限**：如果需要管理员可以看到所有文章，应该在查询前判断用户角色，跳过权限过滤（设置 `skip_permission_filter` 参数）

2. **性能优化**：对于高并发场景，考虑添加：
   - `article_user_access` 和 `article_role_access` 表的索引已经添加
   - 可以考虑使用 Redis 缓存用户的可访问文章列表

3. **数据一致性**：修改文章可见性时，旧的权限数据会被自动清除，确保数据一致性

4. **向后兼容**：旧文章如果没有 `visibility` 字段，MySQL 默认值为 `'public'`，不影响现有功能

## 总结

本次修复解决了两个核心问题：
1. ✅ SQL OR 逻辑错误，导致权限过滤不生效
2. ✅ 前后端字段值不一致，导致数据保存和查询不匹配

修复后，权限控制功能应该可以正常工作。

