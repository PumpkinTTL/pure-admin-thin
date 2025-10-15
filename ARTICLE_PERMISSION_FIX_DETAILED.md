# 🔧 文章权限访问问题 - 完整诊断与修复方案

## 📋 问题现象

**用户登录后仍然无法查看 `login_required`(登录可见) 的文章**

---

## 🔍 问题根源分析

### 1. **SQL条件逻辑错误** ⚠️ **核心问题**

**位置**: `articleService.php` 第60-107行

**问题代码**:
```php
$query->where(function($q) use ($currentUserId, $currentUserRoles) {
    // 1. 公开文章 - 使用 whereOr
    $q->whereOr('visibility', 'public');  // ❌ 第一个条件不应该用 whereOr
    
    // 2. 作者自己的文章
    if ($currentUserId > 0) {
        $q->whereOr('author_id', $currentUserId);
    }
    
    // 3. 登录可见的文章
    if ($currentUserId > 0) {
        $q->whereOr('visibility', 'login_required');
    }
    
    // ... 其他条件
});
```

**生成的错误SQL**:
```sql
WHERE (
    AND visibility = 'public'  -- ❌ whereOr在第一次调用时生成 AND
    OR author_id = 1
    OR visibility = 'login_required'
)
```

**正确的SQL应该是**:
```sql
WHERE (
    visibility = 'public'  -- ✅ 第一个条件不加 AND
    OR author_id = 1
    OR visibility = 'login_required'
)
```

---

### 2. **whereOr 的正确用法**

ThinkPHP 的 `whereOr` 规则：
- **第一次调用** `whereOr` 时，会生成 `AND` 而不是 `OR`
- **解决方案**: 第一个条件用 `where`，后续条件才用 `whereOr`

---

## ✅ 完整修复方案

### 修复 1: 更正 articleService.php 的权限过滤逻辑

**文件**: `src/admin/m-service-server/app/api/services/articleService.php`

**第53-108行** 修改为:

```php
// 权限过滤逻辑
if (!isset($params['skip_permission_filter']) || !$params['skip_permission_filter']) {
    LogService::log("[Service] 开始构建权限过滤条件", [], 'info');
    error_log("[Service] ========== 权限过滤参数 ==========");
    error_log("[Service] currentUserId: {$currentUserId}");
    error_log("[Service] currentUserRoles: " . json_encode($currentUserRoles));
    error_log("[Service] ======================================");
    
    $query->where(function($q) use ($currentUserId, $currentUserRoles) {
        // ✅ 修复：第一个条件用 where，后续用 whereOr
        
        // 1. 公开文章 - 第一个条件用 where（不是 whereOr）
        LogService::log("[Service] 添加条件: visibility = public", [], 'info');
        $q->where('visibility', 'public');  // ✅ 第一个条件用 where
        
        // 2. 作者自己的文章
        if ($currentUserId > 0) {
            LogService::log("[Service] 添加条件: author_id = {$currentUserId}", [], 'info');
            $q->whereOr('author_id', $currentUserId);  // ✅ 后续条件用 whereOr
        }
        
        // 3. 登录可见的文章（用户已登录）
        if ($currentUserId > 0) {
            LogService::log("[Service] 添加条件: visibility = login_required (用户已登录)", [], 'info');
            $q->whereOr('visibility', 'login_required');  // ✅ 用 whereOr
        } else {
            LogService::log("[Service] 跳过 login_required 条件 (用户未登录)", [], 'info');
        }
        
        // 4. 指定用户可见的文章
        if ($currentUserId > 0) {
            LogService::log("[Service] 添加条件: specific_users", [], 'info');
            $q->whereOr(function($q2) use ($currentUserId) {
                $q2->where('visibility', 'specific_users')
                   ->whereExists(function($q3) use ($currentUserId) {
                       $q3->table('bl_article_user_access')  // ✅ 确认表名
                          ->whereRaw('bl_article_user_access.article_id = bl_article.id')
                          ->where('bl_article_user_access.user_id', $currentUserId);
                   });
            });
        }
        
        // 5. 指定角色可见的文章
        if (is_array($currentUserRoles) && count($currentUserRoles) > 0) {
            LogService::log("[Service] 添加条件: specific_roles (角色: " . json_encode($currentUserRoles) . ")", [], 'info');
            $q->whereOr(function($q4) use ($currentUserRoles) {
                $q4->where('visibility', 'specific_roles')
                   ->whereExists(function($q5) use ($currentUserRoles) {
                       $q5->table('bl_article_role_access')  // ✅ 确认表名
                          ->whereRaw('bl_article_role_access.article_id = bl_article.id')
                          ->whereIn('bl_article_role_access.role_id', $currentUserRoles);
                   });
            });
        } else {
            LogService::log("[Service] 跳过指定角色条件 (用户无角色)", [], 'info');
        }
    });
}
```

---

### 修复 2: 确认数据库表名

检查你的数据库表名是否有前缀 `bl_`：

```sql
-- 检查表名
SHOW TABLES LIKE '%article%';
```

**如果表名有前缀**，确保代码中使用正确的表名：
- `bl_article_user_access`（用户权限表）
- `bl_article_role_access`（角色权限表）
- `bl_article`（文章表）

**如果表名没有前缀**，修改为：
- `article_user_access`
- `article_role_access`
- `article`

---

### 修复 3: 前端 Token 传递确认

**文件**: `src/utils/http/index.ts` 第107-108行

确认 token 格式正确：

```typescript
// 添加token到请求头 - 使用标准Bearer格式
config.headers["Authorization"] = formatToken(data.token);
```

**文件**: `src/utils/auth.ts` 第132-134行

```typescript
/** 格式化token（jwt格式） */
export const formatToken = (token: string): string => {
  return "Bearer " + token;  // ✅ 确保格式为 "Bearer <token>"
};
```

---

## 🧪 测试验证步骤

### 1. 创建测试文章

```sql
-- 插入一篇登录可见的测试文章
INSERT INTO bl_article (
    id, title, content, author_id, category_id, 
    visibility, status, create_time, update_time
) VALUES (
    99999, 
    '测试-登录可见文章', 
    '这是一篇只有登录用户才能看到的文章', 
    1, 
    1, 
    'login_required',  -- ✅ 设置为登录可见
    1, 
    NOW(), 
    NOW()
);
```

### 2. 测试未登录用户

```bash
# 不带token请求
curl -X GET "http://your-api.com/api/v1/article/selectArticleAll"
```

**预期结果**：只返回 `visibility='public'` 的文章，**不包含** ID=99999 的文章

### 3. 测试已登录用户

```bash
# 带token请求
curl -X GET "http://your-api.com/api/v1/article/selectArticleAll" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

**预期结果**：返回 `visibility='public'` **和** `login_required` 的文章，**包含** ID=99999 的文章

---

## 📊 调试日志查看

修复后，查看后端日志（PHP error_log）：

```bash
# Windows
Get-Content D:\path\to\php_error.log -Tail 50

# 或者在代码中添加的日志文件
Get-Content D:\path\to\runtime\log\*.log -Tail 100
```

**关键日志输出**：

```
[Middleware] Token 获取: eyJ0eXAiOiJKV1QiLCJhbGc...
[Middleware] Token验证结果: SUCCESS
[Middleware] 从 Token 解析到的 userId: 1
[Middleware] 用户查询结果: FOUND
[Middleware] 用户角色: [1,2,3]
[ArticleAuth] Final userId: 1
[ArticleAuth] Final roles: [1,2,3]

[Controller] params userId: 1
[Controller] params roles: [1,2,3]

[Service] currentUserId: 1
[Service] currentUserRoles: [1,2,3]
[Service] 添加条件: visibility = public
[Service] 添加条件: author_id = 1
[Service] 添加条件: visibility = login_required (用户已登录)  ✅ 关键日志

[Service] SQL查询语句: SELECT * FROM `bl_article` WHERE ( 
    `visibility` = 'public' 
    OR `author_id` = 1 
    OR `visibility` = 'login_required'  ✅ 正确的SQL
) ...
```

---

## 🎯 核心修改总结

| 问题 | 原因 | 修复方案 |
|------|------|----------|
| 登录后看不到 `login_required` 文章 | `whereOr` 第一个条件错误 | 第一个条件用 `where`，后续用 `whereOr` |
| Token可能未传递 | 前端配置问题 | 确认 `Authorization: Bearer <token>` 格式 |
| 用户角色未获取 | 中间件逻辑问题 | 已修复，确认 `with('roles')` 正确 |
| 表名不匹配 | 数据库表前缀 | 确认表名是否有 `bl_` 前缀 |

---

## ✨ 预期效果

修复后：

✅ **未登录用户**: 只能看到 `visibility='public'` 的文章  
✅ **已登录用户**: 可以看到 `public` + `login_required` + 自己的文章 + 授权文章  
✅ **SQL 正确**: `WHERE (public OR login_required OR author_id=1 ...)`  
✅ **日志清晰**: 可以看到完整的权限过滤过程

---

**修复优先级**: 🔴 **立即修复**

**修复难度**: ⭐⭐ (简单，只需改一行代码)

**影响范围**: 整个文章权限系统

---

**建议**: 修复完成后，重启PHP服务，清除缓存，然后测试！

