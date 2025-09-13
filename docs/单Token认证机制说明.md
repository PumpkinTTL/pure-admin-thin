# 单Token认证机制说明文档

## 📋 概述

本项目采用单Token认证机制，通过智能续签实现无感刷新，确保安全性和良好的用户体验。

## 🏗️ 架构设计

### 核心理念
- **单一Token**: 使用一个访问令牌，简化管理
- **智能续签**: 提前检测过期时间，自动续签
- **无感刷新**: 用户无感知的token更新
- **双重存储**: Cookie + LocalStorage，确保数据安全和可用性

### 文件结构
```
src/
├── utils/
│   ├── auth.ts              # Token管理核心工具
│   ├── tokenManager.ts      # Token自动续签管理器
│   └── http/
│       └── index.ts         # HTTP拦截器
├── store/modules/
│   └── user.ts              # 用户状态管理
├── api/
│   └── user.ts              # 用户相关API
└── views/login/
    └── index.vue            # 登录页面
```

## 🔧 核心组件详解

### 1. Token数据结构 (`src/utils/auth.ts`)

```typescript
export interface DataInfo<T> {
  token: string;              // 访问令牌
  expires: T;                 // token过期时间戳
  id?: number;               // 用户ID
  avatar?: string;           // 头像URL
  username?: string;         // 用户名
  nickname?: string;         // 昵称
  roles?: Array<string>;     // 角色数组
  permissions?: Array<string>; // 权限数组
}
```

### 2. Token存储策略

```typescript
// Cookie存储（带过期时间）
const cookieData = {
  token: "eyJhbGciOiJIUzUxMiJ9...",
  expires: 1640995199000  // 时间戳
};
Cookies.set("authorized-token", JSON.stringify(cookieData), {
  expires: (expires - Date.now()) / 86400000  // 天数
});

// LocalStorage存储（用户信息）
const userInfo = {
  id: 1,
  avatar: "avatar.jpg", 
  username: "admin",
  nickname: "管理员",
  roles: ["admin"],
  permissions: ["*:*:*"],
  token: "eyJhbGciOiJIUzUxMiJ9...",
  expires: 1640995199000
};
localStorage.setItem("user-info", JSON.stringify(userInfo));
```

### 3. 智能续签机制 (`src/utils/tokenManager.ts`)

#### 核心特性
- **提前续签**: Token剩余10分钟时开始续签
- **并发保护**: 防止多个请求同时触发续签
- **重试机制**: 续签失败时自动重试
- **错误处理**: 完善的异常处理和用户提示

#### 工作流程
```typescript
// 1. 检查Token是否需要续签
const timeLeft = expiresTime - now;
if (timeLeft <= REFRESH_THRESHOLD) {
  // 2. 调用续签接口
  const response = await http.request('post', '/api/v1/auth/refresh', {
    data: { token: currentToken.token }
  });
  
  // 3. 保存新Token
  setToken(newTokenData);
}
```

## 🔄 API接口规范

### 1. 登录接口

**URL**: `POST /api/v1/user/login`

**请求参数**:
```json
{
  "account": "admin",
  "password": "123456", 
  "action": "pwd"
}
```

**响应格式**:
```json
{
  "code": 200,
  "msg": "登录成功",
  "token": "eyJhbGciOiJIUzUxMiJ9...",
  "expireTime": 1640995199,  // 过期时间戳（秒）
  "data": {
    "id": 1,
    "avatar": "avatar.jpg",
    "username": "admin",
    "nickname": "管理员", 
    "roles": [
      {
        "iden": "admin",
        "permissions": [
          {"name": "*:*:*"}
        ]
      }
    ]
  }
}
```

### 2. Token续签接口

**URL**: `POST /api/v1/auth/refresh`

**请求参数**:
```json
{
  "token": "current_token_here"
}
```

**响应格式**:
```json
{
  "code": 200,
  "msg": "续签成功",
  "token": "new_token_here",
  "expireTime": 1640998799  // 新的过期时间戳（秒）
}
```

## 🔄 完整工作流程

### 登录流程
1. 用户输入用户名密码
2. 前端调用 `/api/v1/user/login` 接口
3. 后端验证凭据，返回用户信息和Token
4. 前端解析数据，保存到Cookie和LocalStorage
5. 跳转到主页面

### API请求流程
1. 发起API请求
2. 拦截器检查Token是否即将过期（剩余10分钟）
3. 如果即将过期，自动调用续签接口
4. 获取新Token，更新本地存储
5. 重新发起原始请求
6. 返回API响应

### Token续签流程
1. tokenManager检测到Token即将过期
2. 使用当前Token调用 `/api/v1/auth/refresh` 接口
3. 后端验证Token有效性
4. 返回新的Token和过期时间
5. 更新本地存储
6. 继续执行原始请求

## 🛡️ 安全特性

### 多重保护
- **时效性**: Token短期有效（2小时），降低泄露风险
- **双重存储**: Cookie和LocalStorage互为备份
- **自动清理**: Token过期或无效时自动清除本地数据
- **请求队列**: 避免并发续签导致的问题

### 异常处理
- **网络异常**: 自动重试机制（最多2次）
- **Token无效**: 自动跳转登录页
- **续签失败**: 清除本地数据，要求重新登录

## 📱 多标签页支持

通过Cookie共享机制，支持多个浏览器标签页同时使用：
- 一个标签页刷新Token，其他标签页自动获取新Token
- 一个标签页退出登录，其他标签页同步退出

## 🎯 使用示例

### 前端调用
```typescript
// 登录
const userInfo = await useUserStoreHook().loginByUsername({
  account: "admin",
  password: "123456",
  action: "pwd"
});

// 获取当前Token
const tokenData = getToken();

// 手动触发续签
await tokenManager.forceRefreshToken();

// 退出登录
useUserStoreHook().logOut();
```

### HTTP请求自动处理
```typescript
// 所有API请求都会自动添加Token到请求头
const response = await http.request('get', '/api/v1/user/profile');

// Token即将过期时会自动续签，用户无感知
```

## 📋 总结

单Token认证机制具有以下优势：

1. **实现简单**: 只需要管理一种token
2. **性能更好**: 减少了token存储和验证的复杂度
3. **足够安全**: 通过短期有效期+智能续签保证安全性
4. **用户体验好**: 无感刷新，长期保持登录状态
5. **维护方便**: 代码逻辑清晰，易于调试和维护

这套单Token机制经过精心设计，完全满足现代Web应用的认证需求。
