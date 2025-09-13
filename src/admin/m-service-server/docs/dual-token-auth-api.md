# 双Token认证系统API文档

## 概述

双Token认证系统采用AccessToken + RefreshToken的方式，提供更安全的用户认证机制。

- **AccessToken**: 短期有效（15分钟），JWT格式，存储在Redis中用于主动失效控制
- **RefreshToken**: 长期有效（7天），UUID格式，用于刷新AccessToken
- **设备绑定**: 通过设备指纹、平台等信息绑定设备，确保安全性
- **主动失效**: 支持主动让token失效，实现强制登出功能

## 重要更新

✅ **AccessToken现在是JWT格式**: 完整的JWT token存储在Redis中
✅ **优化Redis Key命名**: 使用 `user:{userId}:platform:{platform}:access_token` 格式
✅ **支持主动失效**: 可以主动让用户的token失效
⚠️ **已移除device_id验证**: 现在只需要设备指纹和平台标识，简化了集成流程

## 接口列表

### 1. 双Token登录

**接口地址**: `POST /api/v2/user/login`

**完整URL**: `http://your-domain/api/v2/user/login`

**请求头**:
```
Content-Type: application/json
X-Fingerprint: 设备指纹 (必需)
X-Platform: 平台标识 (Web/iOS/Android) (必需)
```

**请求参数**:
```json
{
    "user_id": "用户ID"
}
```

**响应示例**:
```json
{
    "code": 200,
    "msg": "登录成功",
    "data": {
        "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "refresh_token": "12345678-1234-4123-8123-123456789012",
        "token_type": "Bearer",
        "expires_in": 900,
        "refresh_expires_in": 604800
    }
}
```

**错误响应**:
```json
{
    "code": 501,
    "msg": "用户ID不能为空"
}
```

### 2. 刷新Token

**接口地址**: `POST /api/v2/user/refreshToken`

**完整URL**: `http://your-domain/api/v2/user/refreshToken`

**请求头**:
```
Content-Type: application/json
X-Fingerprint: 设备指纹 (必需)
X-Platform: 平台标识 (必需)
```

**请求参数**:
```json
{
    "refresh_token": "刷新令牌",
    "user_id": "用户ID"
}
```

**响应示例**:
```json
{
    "code": 200,
    "msg": "刷新token成功",
    "data": {
        "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "token_type": "Bearer",
        "expires_in": 900
    }
}
```

### 3. 登出接口

**接口地址**: `POST /api/v2/user/logout`

**完整URL**: `http://your-domain/api/v2/user/logout`

**请求头**:
```
Content-Type: application/json
X-Platform: 平台标识 (必需)
refreshToken: {refresh_token} (必需)
```

**请求参数**:
```json
{
    "user_id": "用户ID",
    "refresh_token": "刷新令牌"
}
```

**响应示例**:
```json
{
    "code": 200,
    "msg": "登出成功"
}
```

### 4. 强制失效用户Token（管理员功能）

**接口地址**: `POST /api/v2/user/forceLogoutUser`

**完整URL**: `http://your-domain/api/v2/user/forceLogoutUser`

**请求参数**:
```json
{
    "target_user_id": "目标用户ID",
    "platform": "平台标识（可选，不传则失效所有平台）"
}
```

**响应示例**:
```json
{
    "code": 200,
    "msg": "用户 12345 在 Web 平台的Token已失效"
}
```

## 认证中间件使用

### DualTokenAuth中间件

用于保护需要认证的API接口。

**使用方式**:
```php
// 在路由中使用
Route::group('/:version/protected', function () {
    Route::rule('/userInfo', ':version.User/getUserInfo');
})->middleware([\app\api\middleware\DualTokenAuth::class]);
```

**请求头要求**:
```
Authorization: Bearer {access_token}
refreshToken: {refresh_token}
X-Fingerprint: 设备指纹
X-Platform: 平台标识
X-Device-Id: 设备唯一标识
```

## Redis Key设计

**注意**: Redis中的Key是扁平存储的，冒号(:)只是命名约定，不是树形结构。

### AccessToken存储
```
Key: user:{userId}:platform:{platform}:access_token
Value: 完整的JWT token
TTL: 900秒（15分钟）
```

### RefreshToken存储
```
Key: user:{userId}:platform:{platform}:refresh_token:{refreshToken}
Value: JSON格式的设备和用户信息
TTL: 604800秒（7天）
```

### 实际存储示例
```bash
# Redis中实际的Key（扁平存储）
user:12345:platform:Web:access_token
user:12345:platform:Web:refresh_token:12345678-1234-4123-8123-123456789012

# 对应的Value
"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."  # JWT token
"{\"user_id\":\"12345\",\"fingerprint\":\"fp_xxx\",\"platform\":\"Web\"...}"  # JSON数据
```

### Key命名优势
- **易于管理**: 通过前缀可以快速找到用户相关的所有token
- **支持模式匹配**: 可以使用 `user:12345:*` 查找用户的所有token
- **便于监控**: 可以按用户或平台统计token使用情况

## 安全机制

### 1. 设备绑定
- 登录时记录设备指纹、平台、IP地址
- 刷新token时验证设备信息一致性
- 防止token被盗用

### 2. JWT + Redis双重验证
- AccessToken是标准JWT格式
- JWT验证通过后，再验证Redis中是否存在相同token
- 支持主动失效：删除Redis中的token即可让JWT失效

### 3. 过期机制
- AccessToken: 15分钟自动过期
- RefreshToken: 7天自动过期
- 支持主动清除token

### 4. 主动失效控制
- 登出时清除对应的Redis key
- 管理员可强制失效指定用户的token
- 支持按平台失效或全平台失效

## 错误码说明

| 错误码 | 说明 |
|--------|------|
| 200 | 成功 |
| 401 | 认证失败 |
| 403 | 权限不足 |
| 501 | 参数错误 |
| 502 | 设备信息缺失 |
| 500 | 服务器错误 |

## 状态码说明

| 状态 | 说明 |
|------|------|
| TOKEN_ERROR | Token相关错误 |
| AUTH_ERROR | 认证错误 |
| PERMISSION_DENIED | 权限拒绝 |

## 测试说明

### 前端测试界面

访问测试页面：`http://localhost:3000/dual-token-test`

### 后端服务配置

确保后端服务运行在正确的端口，并且路由配置正确：

1. **检查服务状态**: 确保PHP服务和Redis服务正常运行
2. **检查路由**: 确认 `/api/v2/user/login` 路由可访问
3. **检查CORS**: 如果前后端分离，确保CORS配置正确

### 常见问题排查

#### 404错误
- 检查后端服务是否启动
- 确认API路由配置正确
- 检查前端baseURL配置

#### CORS错误
- 配置后端允许跨域请求
- 检查请求头是否被允许

#### Token验证失败
- 检查Redis服务是否正常
- 确认设备信息一致性
- 检查token格式是否正确

## 最佳实践

### 前端集成建议

1. **Token存储**: 使用安全的本地存储方式
2. **自动刷新**: 在AccessToken即将过期时自动刷新
3. **错误处理**: 根据错误码进行相应处理
4. **设备信息**: 确保设备信息的一致性

### 后端安全建议

1. **HTTPS**: 生产环境必须使用HTTPS
2. **日志记录**: 记录认证相关的操作日志
3. **监控告警**: 监控异常登录行为
4. **定期清理**: 定期清理过期的token数据

## 代码优化说明

### 已完成的优化

1. **移除device_id**: 简化设备验证，只保留设备指纹和平台标识
2. **标准化RefreshToken**: 使用UUID v4格式生成RefreshToken
3. **优化Redis Key**: 使用标准命名规范 `rt_{userId}_{refreshToken}`
4. **完善注释**: 添加详细的代码注释和文档说明
5. **错误处理**: 改进异常处理和错误响应
