# 🔐 单Token滑动无感续签 - API调用文档

> **版本**: v1.0  
> **更新时间**: 2025-10-15  
> **适用范围**: Web、移动端、小程序等所有客户端

---

## 📋 目录

1. [认证机制概述](#认证机制概述)
2. [Token结构说明](#token结构说明)
3. [API接口列表](#api接口列表)
4. [客户端集成指南](#客户端集成指南)
5. [错误处理](#错误处理)
6. [最佳实践](#最佳实践)

---

## 认证机制概述

### 🎯 核心特性

| 特性 | 说明 |
|------|------|
| **单Token模式** | 一个JWT Token完成所有认证 |
| **滑动续签** | Token剩余时间≤10分钟时自动续签 |
| **无感刷新** | 前端自动续签，用户无感知 |
| **并发控制** | 防止多个请求同时触发续签 |
| **失败重试** | 续签失败自动重试2次 |
| **Redis管理** | Token存储在Redis，支持强制失效 |

### ⏱️ 时间配置

```typescript
Token有效期: 2小时 (7200秒)
续签阈值: 剩余10分钟时开始续签
最小阈值: 剩余5分钟时强制续签
Redis过期: 与Token同步
```

### 🔄 续签流程图

```mermaid
sequenceDiagram
    participant 客户端
    participant 拦截器
    participant TokenManager
    participant 后端API
    
    客户端->>拦截器: 发起请求
    拦截器->>TokenManager: 检查Token
    
    alt Token剩余时间 > 10分钟
        TokenManager-->>拦截器: ✅ Token有效，直接通过
    else Token剩余时间 ≤ 10分钟
        TokenManager->>后端API: POST /api/v1/auth/refresh
        后端API-->>TokenManager: 返回新Token
        TokenManager->>TokenManager: 保存新Token
        TokenManager-->>拦截器: ✅ 续签成功
    else Token已过期
        TokenManager-->>客户端: ❌ 跳转登录页
    end
    
    拦截器->>后端API: 携带Token发起请求
    后端API-->>客户端: 返回数据
```

---

## Token结构说明

### 📦 JWT Token Payload

```json
{
  "iat": 1697123456,           // 签发时间戳
  "nbf": 1697123456,           // 生效时间戳
  "exp": 1697130656,           // 过期时间戳（2小时后）
  "iss": "",                   // 签发者
  "aud": "",                   // 接收者
  "data": {                    // ⚠️ 用户数据嵌套在data字段中
    "id": 1,                   // 用户ID
    "loginTime": 1697123456,   // 登录时间
    "account": "admin",        // 账号
    "platform": "Web",         // 平台
    "fingerprint": "Web"       // 设备指纹
  }
}
```

### 🔑 Token验证返回结构

```json
{
  "code": 200,
  "msg": "解析成功",
  "data": {                    // ⚠️ 第一层data - JWT解码内容
    "iat": 1697123456,
    "exp": 1697130656,
    "data": {                  // ⚠️ 第二层data - 用户数据
      "id": 1,
      "account": "admin",
      ...
    }
  }
}
```

> ⚠️ **重要**: Token内容是**两层嵌套的data结构**，解析时需要访问 `response['data']['data']['id']`

### 💾 存储位置

| 存储位置 | 键名 | 内容 | 说明 |
|---------|------|------|------|
| **Cookie** | `authorized-token` | `{token, expires}` | 过期自动销毁 |
| **localStorage** | `user-info` | 完整用户信息 | 持久化存储 |
| **Redis** | `lt_{userId}` | 当前有效Token | 用于验证和强制失效 |

---

## API接口列表

### 1. 用户登录

#### **POST** `/api/v1/user/login`

**请求参数**:

```json
{
  "action": "username",      // 登录方式: username(用户名) | email(邮箱) | phone(手机)
  "account": "admin",        // 登录账号
  "password": "admin123"     // 密码
}
```

**请求头**:

```http
Content-Type: application/json
X-Fingerprint: 58b971ecf99973027ae490d7c1097379  # 可选: 设备指纹
```

**成功响应** (200):

```json
{
  "code": 200,
  "msg": "登录成功",
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",    // JWT Token
  "expireTime": 1697130656,                // 过期时间戳（秒）
  "data": {
    "id": 1,
    "username": "admin",
    "nickname": "管理员",
    "avatar": "https://example.com/avatar.jpg",
    "email": "admin@example.com",
    "roles": [                             // 用户角色列表
      {
        "id": 1,
        "name": "超级管理员",
        "iden": "super_admin",
        "permissions": [                   // 角色权限列表
          {
            "id": 1,
            "name": "users:list",
            "description": "查看用户列表"
          }
        ]
      }
    ]
  }
}
```

**错误响应**:

```json
// 账号或密码错误
{
  "code": 401,
  "msg": "账号或密码错误"
}

// 用户已被禁用
{
  "code": 403,
  "msg": "用户已被禁用"
}

// 参数错误
{
  "code": 501,
  "msg": "参数错误",
  "info": {
    "account": "登录账号必须传递",
    "password": "密码必须传递"
  }
}
```

---

### 2. Token续签（刷新）

#### **POST** `/api/v1/auth/refresh`

**请求参数**:

```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."  // 当前的JWT Token
}
```

**请求头**:

```http
Content-Type: application/json
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...  # 可选，但建议携带
```

**成功响应** (200):

```json
{
  "code": 200,
  "msg": "续签成功",
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",    // 新的JWT Token
  "expireTime": 1697137856                // 新的过期时间戳（秒）
}
```

**错误响应**:

```json
// Token验证失败
{
  "code": 401,
  "msg": "Token验证失败：Token过期"
}

// Token已被撤销
{
  "code": 401,
  "msg": "Token已失效，请重新登录"
}

// 用户不存在
{
  "code": 404,
  "msg": "用户不存在"
}

// 用户已被禁用
{
  "code": 403,
  "msg": "用户已被禁用"
}
```

**续签条件检查**:

后端会验证以下内容：
1. ✅ Token格式正确且签名有效
2. ✅ 从Token中能正确解析出用户ID
3. ✅ 用户存在且状态为启用
4. ✅ Redis中的Token与请求Token匹配
5. ✅ Token未被强制失效

---

### 3. 业务接口调用

#### **所有需要认证的接口**

**请求头**:

```http
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
Content-Type: application/json
```

**示例 - 获取文章列表**:

```http
GET /api/v1/article/selectArticleAll?page=1&page_size=10
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

**Token解析逻辑**:

后端中间件会自动解析Token：

```php
// 1. 获取Authorization头
$authHeader = request()->header('authorization');

// 2. 去掉 "Bearer " 前缀
$token = str_replace(['Bearer ', 'bearer '], '', $authHeader);

// 3. 验证并解析Token
$parseToken = JWTUtil::verifyToken($token);

// 4. 获取用户信息（注意两层data嵌套）
$userId = $parseToken['data']['data']['id'];
$userRoles = 查询数据库获取角色;

// 5. 传递给业务逻辑
request()->currentUserId = $userId;
request()->currentUserRoles = $userRoles;
```

---

## 客户端集成指南

### 📱 Web前端（Vue/React/Angular）

#### 1. Token存储

```typescript
// 保存Token（登录成功后）
function saveToken(response) {
  const tokenData = {
    token: response.token,
    expires: new Date(response.expireTime * 1000), // 秒转毫秒
    id: response.data.id,
    username: response.data.username,
    nickname: response.data.nickname,
    avatar: response.data.avatar,
    roles: response.data.roles.map(r => r.iden),
    permissions: extractPermissions(response.data.roles)
  };
  
  // Cookie存储（过期自动销毁）
  Cookies.set('authorized-token', JSON.stringify({
    token: tokenData.token,
    expires: tokenData.expires.getTime()
  }), {
    expires: (tokenData.expires.getTime() - Date.now()) / 86400000
  });
  
  // localStorage存储（持久化）
  localStorage.setItem('user-info', JSON.stringify(tokenData));
}

// 获取Token
function getToken() {
  const cookieToken = Cookies.get('authorized-token');
  if (cookieToken) {
    return JSON.parse(cookieToken);
  }
  return JSON.parse(localStorage.getItem('user-info'));
}

// 清除Token
function clearToken() {
  Cookies.remove('authorized-token');
  localStorage.removeItem('user-info');
}
```

#### 2. 请求拦截器

```typescript
// Axios拦截器示例
import axios from 'axios';

const api = axios.create({
  baseURL: 'http://your-api.com',
  timeout: 5000
});

// 请求拦截
api.interceptors.request.use(async (config) => {
  // 白名单（不需要Token的接口）
  const whiteList = ['/api/v1/user/login', '/api/v1/auth/refresh'];
  if (whiteList.some(url => config.url.endsWith(url))) {
    return config;
  }
  
  // 检查并续签Token
  const isTokenValid = await tokenManager.checkAndRefreshToken();
  if (!isTokenValid) {
    // Token无效，跳转登录
    window.location.href = '/login';
    return Promise.reject('Token invalid');
  }
  
  // 添加Token到请求头
  const tokenData = getToken();
  if (tokenData?.token) {
    config.headers['Authorization'] = `Bearer ${tokenData.token}`;
  }
  
  return config;
});

// 响应拦截
api.interceptors.response.use(
  response => response.data,
  error => {
    if (error.response?.status === 401) {
      // Token失效，跳转登录
      clearToken();
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);
```

#### 3. TokenManager实现

```typescript
class TokenManager {
  private isRefreshing = false;
  private refreshPromise: Promise<boolean> | null = null;
  private readonly REFRESH_THRESHOLD = 10 * 60 * 1000; // 10分钟

  /**
   * 检查Token并在需要时续签
   */
  async checkAndRefreshToken(): Promise<boolean> {
    const tokenData = getToken();
    if (!tokenData?.token || !tokenData?.expires) {
      return false;
    }

    const now = Date.now();
    const expiresTime = typeof tokenData.expires === 'number'
      ? tokenData.expires
      : new Date(tokenData.expires).getTime();
    
    const timeLeft = expiresTime - now;

    // Token已过期
    if (timeLeft <= 0) {
      clearToken();
      return false;
    }

    // Token需要续签
    if (timeLeft <= this.REFRESH_THRESHOLD) {
      console.log(`Token将在${Math.floor(timeLeft/1000/60)}分钟后过期，开始续签`);
      return await this.refreshToken();
    }

    // Token有效，无需续签
    return true;
  }

  /**
   * 续签Token（防并发）
   */
  private async refreshToken(): Promise<boolean> {
    // 如果正在刷新，返回当前的Promise
    if (this.isRefreshing && this.refreshPromise) {
      return await this.refreshPromise;
    }

    this.isRefreshing = true;
    this.refreshPromise = this.doRefresh();

    try {
      return await this.refreshPromise;
    } finally {
      this.isRefreshing = false;
      this.refreshPromise = null;
    }
  }

  /**
   * 执行续签请求
   */
  private async doRefresh(): Promise<boolean> {
    try {
      const currentToken = getToken();
      
      const response = await fetch('/api/v1/auth/refresh', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ token: currentToken.token })
      });

      const data = await response.json();

      if (data.code !== 200) {
        throw new Error(data.msg);
      }

      // 保存新Token
      const newTokenData = {
        ...currentToken,
        token: data.token,
        expires: new Date(data.expireTime * 1000)
      };
      
      saveToken({ token: data.token, expireTime: data.expireTime, data: currentToken });
      
      console.log('Token续签成功');
      return true;

    } catch (error) {
      console.error('Token续签失败:', error);
      clearToken();
      return false;
    }
  }
}

export const tokenManager = new TokenManager();
```

---

### 📱 移动端（iOS/Android/Flutter）

#### Swift (iOS)

```swift
class TokenManager {
    static let shared = TokenManager()
    private let REFRESH_THRESHOLD: TimeInterval = 10 * 60 // 10分钟
    
    func checkAndRefreshToken() async -> Bool {
        guard let tokenData = getToken() else { return false }
        
        let now = Date()
        let timeLeft = tokenData.expires.timeIntervalSince(now)
        
        // Token已过期
        if timeLeft <= 0 {
            clearToken()
            return false
        }
        
        // 需要续签
        if timeLeft <= REFRESH_THRESHOLD {
            return await refreshToken()
        }
        
        return true
    }
    
    func refreshToken() async -> Bool {
        guard let currentToken = getToken() else { return false }
        
        let parameters = ["token": currentToken.token]
        
        do {
            let response = try await AF.request(
                "https://api.example.com/api/v1/auth/refresh",
                method: .post,
                parameters: parameters,
                encoder: JSONParameterEncoder.default
            ).serializingDecodable(RefreshResponse.self).value
            
            if response.code == 200 {
                saveToken(token: response.token, expireTime: response.expireTime)
                return true
            }
            return false
        } catch {
            print("Token续签失败: \\(error)")
            return false
        }
    }
}

// 请求拦截器
class AuthInterceptor: RequestInterceptor {
    func adapt(_ urlRequest: URLRequest, for session: Session, completion: @escaping (Result<URLRequest, Error>) -> Void) {
        var request = urlRequest
        
        Task {
            let isValid = await TokenManager.shared.checkAndRefreshToken()
            if isValid, let token = TokenManager.shared.getToken()?.token {
                request.setValue("Bearer \\(token)", forHTTPHeaderField: "Authorization")
            }
            completion(.success(request))
        }
    }
}
```

#### Kotlin (Android)

```kotlin
class TokenManager private constructor(private val context: Context) {
    companion object {
        private const val REFRESH_THRESHOLD = 10 * 60 * 1000L // 10分钟
        
        @Volatile
        private var instance: TokenManager? = null
        
        fun getInstance(context: Context): TokenManager {
            return instance ?: synchronized(this) {
                instance ?: TokenManager(context).also { instance = it }
            }
        }
    }
    
    suspend fun checkAndRefreshToken(): Boolean {
        val tokenData = getToken() ?: return false
        
        val now = System.currentTimeMillis()
        val timeLeft = tokenData.expires - now
        
        // Token已过期
        if (timeLeft <= 0) {
            clearToken()
            return false
        }
        
        // 需要续签
        if (timeLeft <= REFRESH_THRESHOLD) {
            return refreshToken()
        }
        
        return true
    }
    
    private suspend fun refreshToken(): Boolean = withContext(Dispatchers.IO) {
        val currentToken = getToken() ?: return@withContext false
        
        try {
            val response = apiService.refreshToken(RefreshRequest(currentToken.token))
            
            if (response.code == 200) {
                saveToken(response.token, response.expireTime)
                true
            } else {
                false
            }
        } catch (e: Exception) {
            Log.e("TokenManager", "Token续签失败", e)
            false
        }
    }
}

// OkHttp拦截器
class AuthInterceptor(private val tokenManager: TokenManager) : Interceptor {
    override fun intercept(chain: Interceptor.Chain): Response {
        val original = chain.request()
        
        // 白名单
        if (original.url.encodedPath.endsWith("/login") ||
            original.url.encodedPath.endsWith("/refresh")) {
            return chain.proceed(original)
        }
        
        // 检查并续签Token
        runBlocking {
            tokenManager.checkAndRefreshToken()
        }
        
        // 添加Token到请求头
        val token = tokenManager.getToken()?.token
        val request = if (token != null) {
            original.newBuilder()
                .header("Authorization", "Bearer $token")
                .build()
        } else {
            original
        }
        
        return chain.proceed(request)
    }
}
```

---

### 🌐 小程序（微信/支付宝/抖音）

```javascript
// token-manager.js
class TokenManager {
  constructor() {
    this.isRefreshing = false;
    this.refreshPromise = null;
    this.REFRESH_THRESHOLD = 10 * 60 * 1000; // 10分钟
  }

  async checkAndRefreshToken() {
    const tokenData = this.getToken();
    if (!tokenData || !tokenData.token || !tokenData.expires) {
      return false;
    }

    const now = Date.now();
    const timeLeft = tokenData.expires - now;

    // Token已过期
    if (timeLeft <= 0) {
      this.clearToken();
      return false;
    }

    // 需要续签
    if (timeLeft <= this.REFRESH_THRESHOLD) {
      console.log(`Token将在${Math.floor(timeLeft/1000/60)}分钟后过期，开始续签`);
      return await this.refreshToken();
    }

    return true;
  }

  async refreshToken() {
    // 防并发
    if (this.isRefreshing && this.refreshPromise) {
      return await this.refreshPromise;
    }

    this.isRefreshing = true;
    this.refreshPromise = this.doRefresh();

    try {
      return await this.refreshPromise;
    } finally {
      this.isRefreshing = false;
      this.refreshPromise = null;
    }
  }

  async doRefresh() {
    const currentToken = this.getToken();
    
    return new Promise((resolve, reject) => {
      wx.request({
        url: 'https://api.example.com/api/v1/auth/refresh',
        method: 'POST',
        data: {
          token: currentToken.token
        },
        success: (res) => {
          if (res.data.code === 200) {
            this.saveToken({
              token: res.data.token,
              expireTime: res.data.expireTime,
              data: currentToken
            });
            console.log('Token续签成功');
            resolve(true);
          } else {
            console.error('Token续签失败:', res.data.msg);
            this.clearToken();
            resolve(false);
          }
        },
        fail: (err) => {
          console.error('Token续签请求失败:', err);
          resolve(false);
        }
      });
    });
  }

  getToken() {
    try {
      const token = wx.getStorageSync('token');
      const expires = wx.getStorageSync('token_expires');
      const userInfo = wx.getStorageSync('user_info');
      
      if (token && expires) {
        return {
          token,
          expires: parseInt(expires),
          ...userInfo
        };
      }
    } catch (e) {
      console.error('获取Token失败:', e);
    }
    return null;
  }

  saveToken(response) {
    try {
      const expires = response.expireTime * 1000;
      
      wx.setStorageSync('token', response.token);
      wx.setStorageSync('token_expires', expires);
      wx.setStorageSync('user_info', response.data);
      
      console.log('Token已保存');
    } catch (e) {
      console.error('保存Token失败:', e);
    }
  }

  clearToken() {
    try {
      wx.removeStorageSync('token');
      wx.removeStorageSync('token_expires');
      wx.removeStorageSync('user_info');
      console.log('Token已清除');
    } catch (e) {
      console.error('清除Token失败:', e);
    }
  }
}

export const tokenManager = new TokenManager();

// 请求拦截器
export async function request(options) {
  // 白名单
  const whiteList = ['/api/v1/user/login', '/api/v1/auth/refresh'];
  const needAuth = !whiteList.some(url => options.url.includes(url));

  if (needAuth) {
    // 检查并续签Token
    const isValid = await tokenManager.checkAndRefreshToken();
    if (!isValid) {
      wx.showToast({ title: '登录已过期', icon: 'none' });
      wx.redirectTo({ url: '/pages/login/login' });
      return Promise.reject('Token invalid');
    }

    // 添加Token到请求头
    const tokenData = tokenManager.getToken();
    if (tokenData?.token) {
      options.header = options.header || {};
      options.header['Authorization'] = `Bearer ${tokenData.token}`;
    }
  }

  return new Promise((resolve, reject) => {
    wx.request({
      ...options,
      success: (res) => {
        if (res.statusCode === 401) {
          tokenManager.clearToken();
          wx.redirectTo({ url: '/pages/login/login' });
          reject('Unauthorized');
        } else {
          resolve(res.data);
        }
      },
      fail: reject
    });
  });
}
```

---

## 错误处理

### 🚨 常见错误码

| 错误码 | 说明 | 处理方式 |
|--------|------|----------|
| **200** | 成功 | 正常处理 |
| **401** | 认证失败/Token无效 | 清除Token，跳转登录页 |
| **403** | 权限不足/用户被禁用 | 提示用户，跳转登录页 |
| **404** | 用户不存在 | 清除Token，跳转登录页 |
| **500** | 服务器错误 | 提示用户稍后重试 |
| **501** | 参数错误 | 提示参数错误信息 |

### 🔧 错误处理示例

```typescript
// 统一错误处理
function handleApiError(error: any) {
  const status = error.response?.status;
  const data = error.response?.data;

  switch (status) {
    case 401:
      message.error('登录已过期，请重新登录');
      clearToken();
      router.push('/login');
      break;
      
    case 403:
      message.error(data?.msg || '权限不足');
      if (data?.msg?.includes('禁用')) {
        clearToken();
        router.push('/login');
      }
      break;
      
    case 404:
      message.error('用户不存在');
      clearToken();
      router.push('/login');
      break;
      
    case 500:
      message.error('服务器错误，请稍后重试');
      break;
      
    case 501:
      message.error(data?.msg || '参数错误');
      console.error('参数错误详情:', data?.info);
      break;
      
    default:
      message.error(data?.msg || '请求失败');
  }
}
```

---

## 最佳实践

### ✅ 推荐做法

1. **Token存储**
   - ✅ Web: Cookie + localStorage 双重存储
   - ✅ 移动端: 使用安全存储（Keychain/KeyStore）
   - ✅ 小程序: 使用 Storage API

2. **续签时机**
   - ✅ 在请求拦截器中检查
   - ✅ Token剩余时间≤10分钟时触发
   - ✅ 防止并发续签请求

3. **错误处理**
   - ✅ 401错误立即清除Token并跳转登录
   - ✅ 续签失败重试2次
   - ✅ 网络异常友好提示

4. **安全措施**
   - ✅ HTTPS传输
   - ✅ Token存储加密（移动端）
   - ✅ 退出登录时清除所有Token
   - ✅ Redis Token黑名单机制

### ❌ 避免做法

1. **不要**
   - ❌ 明文存储Token
   - ❌ 在URL中传递Token
   - ❌ 忽略Token过期时间
   - ❌ 不处理续签失败的情况

2. **不要**过度续签
   - ❌ 每次请求都尝试续签
   - ❌ 并发请求多次续签
   - ❌ Token有效期很长时也续签

---

## 🧪 测试工具

### Postman测试

```javascript
// Pre-request Script - 自动添加Token
const tokenData = pm.environment.get("token_data");
if (tokenData) {
    const token = JSON.parse(tokenData).token;
    pm.request.headers.add({
        key: 'Authorization',
        value: 'Bearer ' + token
    });
}

// Tests - 保存登录返回的Token
if (pm.response.code === 200) {
    const response = pm.response.json();
    if (response.token) {
        pm.environment.set("token_data", JSON.stringify({
            token: response.token,
            expireTime: response.expireTime
        }));
    }
}
```

### cURL测试

```bash
# 登录
curl -X POST http://your-api.com/api/v1/user/login \
  -H "Content-Type: application/json" \
  -d '{
    "action": "username",
    "account": "admin",
    "password": "admin123"
  }'

# 续签Token
curl -X POST http://your-api.com/api/v1/auth/refresh \
  -H "Content-Type: application/json" \
  -d '{
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
  }'

# 业务请求（携带Token）
curl -X GET http://your-api.com/api/v1/article/selectArticleAll?page=1 \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..."
```

---

## 📞 技术支持

如有问题，请联系技术团队或参考：
- 后端源码: `src/admin/m-service-server/app/api/controller/v1/User.php`
- 前端源码: `src/utils/tokenManager.ts`
- JWT工具类: `src/admin/m-service-server/extend/utils/JWTUtil.php`

---

**文档版本**: v1.0  
**最后更新**: 2025-10-15  
**维护者**: 开发团队

