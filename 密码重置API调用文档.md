# 密码重置 API 调用文档

## 接口概述

| 接口          | 地址                                | 方法 | 认证 |
| ------------- | ----------------------------------- | ---- | ---- |
| 请求密码重置  | `/api/v1/user/requestPasswordReset` | POST | 否   |
| 校验重置Token | `/api/v1/user/verifyResetToken`     | POST | 否   |
| 重置密码      | `/api/v1/user/resetPassword`        | POST | 否   |

---

## 1. 请求密码重置（发送邮件）

**接口地址**：`POST /api/v1/user/requestPasswordReset`

### 请求参数

```json
{
  "email": "user@example.com"
}
```

### 调用方式

```javascript
axios.post("/api/v1/user/requestPasswordReset", {
  email: "user@example.com"
});
```

### 响应示例

**成功 (code: 200)**

```json
{
  "code": 200,
  "msg": "重置链接已发送到您的邮箱，10分钟内有效",
  "data": {
    "token": "a1b2c3...",
    "expire_time": "2025-11-19 18:45:00"
  }
}
```

**失败 (code: 404)**

```json
{
  "code": 404,
  "msg": "该邮箱未注册"
}
```

---

## 2. 校验重置Token

**接口地址**：`POST /api/v1/user/verifyResetToken`

**说明**：用户打开重置页面时调用，立即校验token有效性并返回绑定的邮箱信息

### 请求参数

```json
{
  "token": "从URL参数获取的token"
}
```

### 调用方式

```javascript
axios.post("/api/v1/user/verifyResetToken", {
  token: "a1b2c3..."
});
```

### 响应示例

**成功 (code: 200)**

```json
{
  "code": 200,
  "msg": "Token有效",
  "data": {
    "email": "u***@example.com",
    "username": "user123",
    "valid": true
  }
}
```

**失败 (code: 400)**

```json
{
  "code": 400,
  "msg": "令牌无效"
}
```

```json
{
  "code": 400,
  "msg": "令牌已过期，请重新申请"
}
```

**失败 (code: 404)**

```json
{
  "code": 404,
  "msg": "用户不存在"
}
```

---

## 3. 重置密码

**接口地址**：`POST /api/v1/user/resetPassword`

### 请求参数

```json
{
  "token": "从邮件链接获取的token",
  "new_password": "新密码（最少6位）"
}
```

### 调用方式

```javascript
axios.post("/api/v1/user/resetPassword", {
  token: "a1b2c3...",
  new_password: "newPassword123"
});
```

### 响应示例

**成功 (code: 200)**

```json
{
  "code": 200,
  "msg": "密码重置成功，请使用新密码登录",
  "data": null
}
```

**失败 (code: 400)**

```json
{
  "code": 400,
  "msg": "令牌已过期，请重新申请"
}
```

---

## 响应码说明

| code | 说明                   |
| ---- | ---------------------- |
| 200  | 成功                   |
| 400  | Token无效或过期        |
| 404  | 邮箱未注册或用户不存在 |
| 500  | 服务器错误             |
| 501  | 参数错误               |

---

## 调用流程

```
1. 用户打开重置页面（URL带token参数）
   ↓
2. 立即调用接口2（verifyResetToken）校验token
   ↓
3. 如果有效，显示重置表单和绑定邮箱
   ↓
4. 用户输入新密码，调用接口3（resetPassword）
   ↓
5. 重置成功，跳转登录页
```

---

## 注意事项

- Token有效期：**10分钟**
- Token一次性使用，使用后立即失效
- 密码要求：**最少6位**
- 邮箱必须是已注册的邮箱
- 重置成功后需重新登录
- 邮箱显示会脱敏处理（如：u\*\*\*@example.com）
