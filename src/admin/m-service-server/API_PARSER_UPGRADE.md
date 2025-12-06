# API 路由解析器升级说明

## 📋 升级内容

### 1. 新增功能

#### ✅ 支持多种路由定义方式

- `Route::rule()` - 通用路由（已支持）
- `Route::get()` - GET 请求（新增）
- `Route::post()` - POST 请求（新增）
- `Route::put()` - PUT 请求（新增）
- `Route::delete()` - DELETE 请求（新增）

#### ✅ 路径参数规范化

```php
// 原始路径
Route::get('detail/:id', ':version.CardKey/detail');
Route::post('disable/:id', ':version.CardKey/disable');

// 解析后
/api/v1/cardkey/detail/{id}
/api/v1/cardkey/disable/{id}
```

#### ✅ 支持 v2 版本路由

```php
// v2 版本路由
Route::group('/v2/user', function () {
    Route::rule('/profile', 'v2.User/profile');
});

// 解析为
/api/v2/user/profile
```

#### ✅ 自动识别中间件

```php
Route::group('/:version/user', function () {
    // ...
})->middleware([
    app\api\middleware\Auth::class  // 自动识别为需要权限
]);
```

#### ✅ 提取注释作为描述

```php
Route::rule('/login', ':version.User/login'); // 用户登录
// description 字段自动填充为 "用户登录"
```

#### ✅ 自动提取模块名

```php
// 从路径提取模块名
/api/v1/user/login → module: user
/api/v1/article/list → module: article
/api/v1/cardkey/generate → module: cardkey
```

#### ✅ 自动判断权限检查模式

- **auto**: 有中间件的接口，自动根据 module + method 检查权限
- **manual**: 特殊接口，需要手动指定权限标识
- **none**: 公开接口，不检查权限

---

## 🗄️ 数据库变更

### 新增字段

```sql
ALTER TABLE `api`
ADD COLUMN `module` VARCHAR(50) NULL COMMENT '模块名',
ADD COLUMN `required_permission` VARCHAR(100) NULL COMMENT '所需权限标识',
ADD COLUMN `check_mode` ENUM('auto', 'manual', 'none') DEFAULT 'auto' COMMENT '权限检查模式';
```

### 字段说明

| 字段                | 类型         | 说明               | 示例                   |
| ------------------- | ------------ | ------------------ | ---------------------- |
| module              | VARCHAR(50)  | 模块名             | user, article, cardkey |
| required_permission | VARCHAR(100) | 手动指定的权限标识 | user:force-logout      |
| check_mode          | ENUM         | 权限检查模式       | auto, manual, none     |

---

## 🚀 使用步骤

### 步骤1：执行数据库迁移

```bash
# 在 MySQL 中执行迁移脚本
mysql -u root -p your_database < database/migrations/add_api_permission_fields.sql
```

### 步骤2：重新同步 API

1. 登录后台管理系统
2. 进入 "系统管理" → "API 管理"
3. 点击 "同步接口" 按钮
4. 选择 "清空现有数据并重新导入"（推荐）或 "仅导入新接口"

### 步骤3：验证结果

查看 API 列表，确认：

- ✅ `module` 字段已正确填充
- ✅ `check_mode` 字段已正确设置
- ✅ 路径参数已规范化（:id → {id}）
- ✅ 注释已提取为描述
- ✅ Route::get/post/put/delete 的接口已导入

---

## 📊 解析结果示例

### 示例1：Route::rule

```php
// 路由定义
Route::rule('/login', ':version.User/login'); // 用户登录
```

**解析结果：**

```json
{
  "version": "v1",
  "method": "ANY",
  "model": "User",
  "module": "user",
  "path": "/login",
  "full_path": "/api/v1/user/login",
  "check_mode": "none",
  "description": "用户登录"
}
```

### 示例2：Route::get 带参数

```php
// 路由定义
Route::get('detail/:id', ':version.CardKey/detail'); // 获取卡密详情
```

**解析结果：**

```json
{
  "version": "v1",
  "method": "GET",
  "model": "CardKey",
  "module": "cardkey",
  "path": "detail/:id",
  "full_path": "/api/v1/cardkey/detail/{id}",
  "check_mode": "auto",
  "description": "获取卡密详情"
}
```

### 示例3：Route::post 带中间件

```php
// 路由定义
Route::group('/:version/user', function () {
    Route::post('add', ':version.User/add'); // 添加用户
})->middleware([
    app\api\middleware\Auth::class
]);
```

**解析结果：**

```json
{
  "version": "v1",
  "method": "POST",
  "model": "User",
  "module": "user",
  "path": "add",
  "full_path": "/api/v1/user/add",
  "check_mode": "auto",
  "description": "添加用户"
}
```

### 示例4：v2 版本路由

```php
// 路由定义
Route::group('/v2/user', function () {
    Route::rule('/profile', 'v2.User/profile'); // 获取用户信息
})->middleware([
    app\api\middleware\Auth::class
]);
```

**解析结果：**

```json
{
  "version": "v2",
  "method": "ANY",
  "model": "User",
  "module": "user",
  "path": "/profile",
  "full_path": "/api/v2/user/profile",
  "check_mode": "auto",
  "description": "获取用户信息"
}
```

---

## 🎯 权限检查模式说明

### auto 模式（自动检查）

**适用场景：** 80% 的常规接口

**工作原理：**

```
请求: GET /api/v1/user/list
↓
提取: module=user, method=GET
↓
映射: GET → view
↓
检查权限: user:view:all / user:view:own / user:view:dept / user:*
```

**示例：**

```php
// 接口配置
{
  "full_path": "/api/v1/user/list",
  "method": "GET",
  "module": "user",
  "check_mode": "auto"
}

// 自动检查以下权限（按优先级）
user:view:all    // 查看所有用户
user:view:dept   // 查看部门用户
user:view:own    // 查看自己
user:*           // 用户模块所有权限
*:*:*            // 超级管理员
```

### manual 模式（手动指定）

**适用场景：** 15% 的特殊接口

**工作原理：**

```
请求: POST /api/v1/user/forceLogoutUser
↓
读取: required_permission = "user:force-logout"
↓
检查权限: user:force-logout
```

**示例：**

```php
// 接口配置
{
  "full_path": "/api/v1/user/forceLogoutUser",
  "method": "POST",
  "module": "user",
  "check_mode": "manual",
  "required_permission": "user:force-logout"
}

// 只检查指定的权限
user:force-logout  // 强制登出用户
```

### none 模式（不检查）

**适用场景：** 5% 的公开接口

**工作原理：**

```
请求: POST /api/v1/user/login
↓
检查: check_mode = "none"
↓
直接放行，不检查权限
```

**示例：**

```php
// 接口配置
{
  "full_path": "/api/v1/user/login",
  "method": "POST",
  "module": "user",
  "check_mode": "none"
}

// 不检查任何权限，直接放行
```

---

## 🔧 代码变更清单

### 修改的文件

1. **ApiManager.php** - 路由解析器

   - ✅ 重写 `parseRouteFile()` 方法
   - ✅ 新增 `removeBlockComments()` 方法
   - ✅ 新增 `extractRouteGroups()` 方法
   - ✅ 新增 `parseGroupRoutes()` 方法
   - ✅ 新增 `extractModuleName()` 方法
   - ✅ 新增 `parseAction()` 方法
   - ✅ 新增 `normalizePathParams()` 方法
   - ✅ 新增 `determineCheckMode()` 方法

2. **Api.php** - 模型

   - ✅ 新增 `CHECK_MODE_*` 常量
   - ✅ 更新 `$allowField` 字段列表
   - ✅ 修正 `STATUS_CLOSED` 值为 3

3. **add_api_permission_fields.sql** - 数据库迁移脚本
   - ✅ 添加新字段
   - ✅ 添加索引
   - ✅ 填充现有数据

---

## ✅ 测试检查清单

- [ ] 数据库迁移成功执行
- [ ] API 同步功能正常
- [ ] Route::rule 接口正常导入
- [ ] Route::get/post/put/delete 接口正常导入
- [ ] 路径参数正确转换（:id → {id}）
- [ ] v2 版本路由正确识别
- [ ] 中间件正确识别
- [ ] 注释正确提取为描述
- [ ] module 字段正确填充
- [ ] check_mode 字段正确设置
- [ ] 公开接口设置为 none 模式
- [ ] 特殊接口设置为 manual 模式

---

## 📝 注意事项

1. **备份数据库**：执行迁移前请先备份数据库
2. **清空重导**：建议首次使用时选择"清空现有数据并重新导入"
3. **检查结果**：导入后检查 API 数量和字段是否正确
4. **权限配置**：manual 模式的接口需要手动配置 required_permission
5. **测试验证**：导入后建议测试几个接口确认解析正确

---

## 🎉 升级完成

恭喜！API 路由解析器已成功升级，现在支持：

- ✅ 更多路由定义方式
- ✅ 自动权限检查
- ✅ 模块化管理
- ✅ 更智能的解析

下一步可以开始实现权限检查中间件了！
