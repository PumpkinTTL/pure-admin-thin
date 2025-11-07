# 用户模块 Token 验证测试

## 🎯 测试目的

验证 UserAuth 中间件是否正确处理 Token 验证失败的情况

---

## 📋 测试步骤

### 1️⃣ **准备工作**

确保你已经登录并获得正常的 Token：

- 正常 Token: `eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiIiLCJhdWQiOiIi...`

---

### 2️⃣ **测试场景 1: 正常 Token**

#### 请求接口：

```
GET /api/v1/user/selectUserInfoById?targetUid=103
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...（正常Token）
```

#### 预期结果：

```json
{
  "code": 200,
  "msg": "查询成功",
  "data": {
    "id": 103,
    "username": "testuser",
    "email": "test@example.com",
    ...
  }
}
```

✅ **状态**：应该成功返回用户信息

---

### 3️⃣ **测试场景 2: 篡改的 Token（开头加 "1"）**

#### 步骤：

1. 打开浏览器开发者工具（F12）
2. Application → Cookies → 找到 `Authorization`
3. 修改值为：`1eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...`
4. 刷新页面或手动发送请求

#### 请求接口：

```
GET /api/v1/user/selectUserInfoById?targetUid=103
Authorization: 1eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...（篡改Token）
```

#### 预期结果：

```json
{
  "code": 401,
  "msg": "Token令牌不合法",
  "status": "TOKEN_ERROR"
}
```

✅ **状态**：**应该拒绝访问并返回 401 错误**

---

### 4️⃣ **测试场景 3: 完全无效的 Token**

#### 请求接口：

```
GET /api/v1/user/selectUserInfoById?targetUid=103
Authorization: this_is_not_a_valid_token
```

#### 预期结果：

```json
{
  "code": 401,
  "msg": "Token令牌不合法",
  "status": "TOKEN_ERROR"
}
```

✅ **状态**：应该拒绝访问

---

### 5️⃣ **测试场景 4: 未提供 Token**

#### 请求接口：

```
GET /api/v1/user/selectUserInfoById?targetUid=103
（不携带 Authorization 头）
```

#### 预期结果：

```json
{
  "code": 401,
  "msg": "未提供Token",
  "status": "TOKEN_ERROR"
}
```

✅ **状态**：应该拒绝访问

---

### 6️⃣ **测试场景 5: 过期的 Token**

> 注意：需要等待 Token 过期（默认2小时），或者手动修改 JWTUtil 生成一个过期的 Token

#### 预期结果：

```json
{
  "code": 401,
  "msg": "Token过期",
  "status": "TOKEN_ERROR"
}
```

✅ **状态**：应该拒绝访问

---

## 🔍 对比测试：公告接口（允许游客）

### 测试公告接口（不强制登录）

#### 请求接口：

```
GET /api/v1/notice/list?page=1&page_size=10
Authorization: 1eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...（篡改Token）
```

#### 预期结果：

```json
{
  "code": 200,
  "data": {
    "total": 10,
    "data": [
      // 返回公开的公告列表（游客可见）
    ]
  }
}
```

✅ **状态**：**应该成功返回公开内容**（因为公告模块允许游客访问）

---

## 📊 测试结果总结

| 接口                       | Token 状态   | 预期行为         | 实际行为 |
| -------------------------- | ------------ | ---------------- | -------- |
| `/user/selectUserInfoById` | 正常 Token   | ✅ 返回用户信息  | ？       |
| `/user/selectUserInfoById` | 篡改 Token   | ❌ 返回 401 错误 | ？       |
| `/user/selectUserInfoById` | 无效 Token   | ❌ 返回 401 错误 | ？       |
| `/user/selectUserInfoById` | 未提供 Token | ❌ 返回 401 错误 | ？       |
| `/notice/list`             | 篡改 Token   | ✅ 返回公开内容  | ？       |

---

## 🛠️ 使用 Postman/cURL 测试

### cURL 示例（正常 Token）：

```bash
curl -X GET "http://localhost/api/v1/user/selectUserInfoById?targetUid=103" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
```

### cURL 示例（篡改 Token）：

```bash
curl -X GET "http://localhost/api/v1/user/selectUserInfoById?targetUid=103" \
  -H "Authorization: Bearer 1eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
```

---

## 🎉 预期效果

✅ **UserAuth 中间件应该能正确识别并拒绝篡改/无效的 Token**

- 用户模块接口：Token 无效 → **返回 401 错误**
- 公告模块接口：Token 无效 → **返回公开内容**（视为游客）

这样就实现了：

- 🔒 **需要强制登录的模块**（用户、订单）→ 严格验证
- 🌐 **允许游客访问的模块**（公告、文章）→ 宽松处理

---

## 📝 测试完成后

请在上方表格中填写"实际行为"列，确认是否符合预期！
