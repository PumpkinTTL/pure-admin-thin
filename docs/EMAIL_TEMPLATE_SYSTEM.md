# 📧 邮件模板系统使用文档

## 📋 功能概述

邮件模板系统允许您在数据库中管理和维护邮件模板，无需修改代码即可更新邮件内容和样式。

---

## 🗄️ 数据库结构

### 表名：`bl_email_templates`

| 字段          | 类型         | 说明                                                |
| ------------- | ------------ | --------------------------------------------------- |
| `id`          | int          | 模板ID（主键）                                      |
| `name`        | varchar(100) | 模板名称                                            |
| `code`        | varchar(50)  | 模板标识（唯一，用于代码调用）                      |
| `type`        | tinyint      | 模板类型：1=系统通知 2=营销推广 3=业务通知 4=验证类 |
| `subject`     | varchar(255) | 邮件主题（支持变量）                                |
| `content`     | text         | 邮件HTML内容（支持变量）                            |
| `variables`   | text         | 可用变量说明（JSON格式）                            |
| `description` | varchar(500) | 模板描述                                            |
| `is_active`   | tinyint(1)   | 是否启用：0=禁用 1=启用                             |
| `is_system`   | tinyint(1)   | 是否系统模板：0=否 1=是（系统模板不可删除）         |
| `created_by`  | int          | 创建人ID                                            |
| `create_time` | datetime     | 创建时间                                            |
| `update_time` | datetime     | 更新时间                                            |
| `delete_time` | datetime     | 删除时间（软删除）                                  |

---

## 🎨 变量占位符

模板中使用 `{{variable_name}}` 格式的占位符，发送邮件时会被实际数据替换。

### 示例：

```html
<p>尊敬的 <strong>{{username}}</strong>：</p>
<a href="{{reset_url}}">重置密码</a>
<p>此链接将在 {{expire_minutes}} 分钟后失效</p>
```

### 系统自动提供的变量：

- `{{year}}` - 当前年份（自动添加）

---

## 🚀 使用方法

### 1️⃣ 在代码中使用模板发送邮件

```php
use app\api\services\EmailTemplateService;

// 使用模板发送邮件
$result = EmailTemplateService::sendByTemplate(
    'password_reset',  // 模板标识
    'user@example.com', // 收件人邮箱
    [
        'username' => '张三',
        'reset_url' => 'https://example.com/reset?token=xxx',
        'expire_minutes' => 30
    ]
);

if ($result['success']) {
    echo '邮件发送成功';
} else {
    echo '发送失败：' . $result['message'];
}
```

### 2️⃣ 在公告管理中使用

公告模块发送邮件时，可以选择模板：

```php
// 获取可用模板列表
$templates = EmailTemplateService::getActiveTemplates();

// 使用选择的模板发送
EmailTemplateService::sendByTemplate(
    'notice_announcement',
    $email,
    [
        'title' => '系统维护通知',
        'content' => '系统将于...',
        'publish_time' => date('Y-m-d H:i:s')
    ]
);
```

---

## 📦 内置系统模板

### 1. 密码重置模板（`password_reset`）

**变量：**

- `username` - 用户名
- `reset_url` - 重置链接
- `expire_minutes` - 过期分钟数

**使用场景：** 用户请求重置密码时

---

### 2. 公告通知模板（`notice_announcement`）

**变量：**

- `title` - 公告标题
- `content` - 公告内容（支持HTML）
- `publish_time` - 发布时间

**使用场景：** 系统公告推送

---

### 3. 验证码模板（`verification_code`）

**变量：**

- `code` - 验证码
- `expire_minutes` - 过期分钟数

**使用场景：** 发送短信验证码、邮箱验证码等

---

## 🛠️ API 接口（待实现）

### 获取模板列表

```
GET /api/v1/emailTemplate/list
参数：
- name: 模板名称（可选）
- code: 模板标识（可选）
- type: 模板类型（可选）
- is_active: 是否启用（可选）
```

### 创建模板

```
POST /api/v1/emailTemplate/create
{
    "name": "活动通知",
    "code": "activity_notice",
    "type": 2,
    "subject": "{{activity_name}} - 活动邀请",
    "content": "<html>...</html>",
    "variables": {"activity_name":"活动名称","activity_time":"活动时间"},
    "description": "用于活动推广的邮件模板"
}
```

### 更新模板

```
PUT /api/v1/emailTemplate/update
{
    "id": 1,
    "subject": "修改后的主题",
    "content": "<html>...</html>"
}
```

### 删除模板

```
DELETE /api/v1/emailTemplate/delete
{
    "id": 1
}
```

**注意：** 系统模板（`is_system=1`）不允许删除

---

## ⚠️ 注意事项

1. **模板标识（code）唯一**：不能重复
2. **系统模板保护**：系统模板不能删除，code不能修改
3. **变量格式**：必须使用 `{{variable_name}}` 格式
4. **HTML安全**：模板内容会原样输出，请确保HTML安全
5. **测试建议**：修改模板后建议先发送测试邮件

---

## 🎯 最佳实践

### 1. 模板命名规范

- 使用下划线命名：`password_reset`, `activity_notice`
- 见名知义，简洁明了

### 2. 变量命名规范

- 使用小写字母和下划线
- 语义化命名：`user_name`, `expiration_time`

### 3. 样式建议

- 使用内联样式（某些邮箱不支持外部CSS）
- 表格布局比div布局兼容性更好
- 避免使用JavaScript
- 图片使用绝对路径

### 4. 测试

- 测试不同邮箱客户端的显示效果（Gmail、Outlook、QQ邮箱等）
- 测试移动端显示效果

---

## 📚 示例模板

### 简单文本通知

```html
<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8" />
  </head>
  <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
      <h2>{{title}}</h2>
      <p>{{content}}</p>
      <p style="color: #999; font-size: 12px; margin-top: 30px;">
        此邮件由系统自动发送，请勿回复
      </p>
    </div>
  </body>
</html>
```

---

## 🔗 相关文件

- 模型：`app/api/model/EmailTemplate.php`
- 服务：`app/api/services/EmailTemplateService.php`
- SQL：`database/migrations/create_email_templates_table.sql`

---

## 📞 技术支持

如有问题，请联系开发团队。
