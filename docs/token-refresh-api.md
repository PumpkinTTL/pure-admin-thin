# Token无感续签 - 后端对接文档

## 📋 概述

前端已实现Token无感续签机制，当Token剩余10分钟时会自动调用续签接口，实现用户无感知的登录状态延续。

## 🔧 前端已实现功能

### 自动续签机制
- ✅ Token剩余10分钟时自动续签
- ✅ 防止并发续签请求
- ✅ 续签失败自动重试（最多2次，间隔1秒）
- ✅ 不阻塞当前请求，后台静默处理
- ✅ Cookie自动过期机制

### 请求方式
- ✅ 所有API请求通过 `Authorization: Bearer {token}` 头部发送
- ✅ 续签请求通过 `data` 参数发送token

## 🚀 后端需要实现的接口

### 续签接口规范

**接口地址**: `POST /api/v1/auth/refresh`

**请求参数**:
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
}
```

**成功响应** (HTTP 200):
```json
{
  "code": 200,
  "msg": "Token续签成功",
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "expireTime": 1756050226
}
```

**失败响应** (HTTP 401):
```json
{
  "code": 401,
  "msg": "Token无效或已过期"
}
```

**其他错误响应**:
```json
{
  "code": 400,
  "msg": "Token不能为空"
}
```

```json
{
  "code": 500,
  "msg": "服务器内部错误"
}
```

## 🔍 后端处理逻辑建议

### 基本流程
1. **接收请求**: 从 `data.token` 获取旧token
2. **验证token**: 检查token格式、签名和有效性
3. **允许续签**: 即将过期或刚过期不久的token可以续签
4. **生成新token**: 保持用户信息一致，延长过期时间
5. **返回响应**: 包含新token和过期时间

### 关键实现要点

#### 1. Token验证
```python
# 示例：允许即将过期的token续签
try:
    # 正常验证
    decoded = jwt.decode(old_token, JWT_SECRET, algorithms=['HS256'])
except jwt.ExpiredSignatureError:
    # 允许过期的token在一定时间内续签
    decoded = jwt.decode(old_token, JWT_SECRET, algorithms=['HS256'], 
                        options={"verify_exp": False})
    
    # 检查过期时间，超过1小时不允许续签
    exp_time = decoded.get('exp', 0)
    if time.time() - exp_time > 3600:
        return error_response(401, 'Token已过期太久，请重新登录')
```

#### 2. 新Token生成
```python
# 生成新token，延长24小时
new_expire_time = int(time.time()) + 24 * 60 * 60
new_token = jwt.encode({
    'iss': '',
    'aud': '',
    'iat': int(time.time()),
    'nbf': int(time.time()),
    'exp': new_expire_time,
    'data': {
        'loginTime': int(time.time()),
        'account': decoded.get('data', {}).get('account'),
        'id': user_id,
        'platform': 'Web',
        'fingerprint': decoded.get('data', {}).get('fingerprint')
    }
}, JWT_SECRET, algorithm='HS256')
```

## 🎯 关键要点

### 1. 续签时机
- 前端在token剩余10分钟时自动调用
- 建议允许即将过期或刚过期不久的token续签
- 可设置续签时间窗口（如过期后1小时内可续签）

### 2. 安全考虑
- 验证token的完整性和用户身份
- 设置合理的续签时间窗口
- 生成新token时保持用户信息一致
- 记录续签日志便于审计

### 3. 响应格式要求
- **必须字段**: `code`, `msg`, `token`, `expireTime`
- **expireTime**: 使用秒级时间戳
- **错误处理**: 返回明确的错误码和消息

### 4. 性能优化
- 续签接口应快速响应（建议<500ms）
- 可使用缓存减少数据库查询
- 避免在续签过程中进行复杂业务逻辑

## 🔄 完整交互流程

```
1. 前端检测Token剩余10分钟
2. 前端调用 POST /api/v1/auth/refresh
3. 后端验证旧token
4. 后端生成新token
5. 后端返回新token和过期时间
6. 前端保存新token到Cookie
7. 前端继续使用新token发送原始请求
```

## ✅ 测试用例建议

### 正常场景
- 使用即将过期的有效token测试续签
- 验证返回的新token格式正确
- 验证新token的过期时间延长

### 异常场景
- 使用已过期很久的token
- 使用格式错误的token
- 使用被篡改的token
- 并发发送多个续签请求
- 续签接口超时或返回错误

### 边界场景
- 刚过期几分钟的token
- 空token或null值
- 超长token字符串

## 🚨 注意事项

1. **时区问题**: 确保服务器时间与前端时间同步
2. **并发处理**: 同一用户可能同时发起多个续签请求
3. **日志记录**: 记录续签成功/失败的日志
4. **监控告警**: 监控续签接口的成功率和响应时间
5. **降级策略**: 续签失败时前端会自动跳转登录页

## 📊 监控指标建议

- 续签接口调用量
- 续签成功率
- 续签接口响应时间
- 续签失败原因分布
- 用户重新登录频率

## 🔧 前端技术实现

### TokenManager核心功能
- 智能检测token过期时间
- 并发控制防止重复续签
- 失败重试机制（最多2次）
- 错误分类处理和用户提示

### 集成方式
- HTTP请求拦截器自动检查token
- 白名单机制（登录、续签接口除外）
- 续签成功后自动更新本地存储
- 续签失败自动清理并跳转登录页

---

**文档版本**: v1.0  
**更新时间**: 2025-01-23  
**维护人员**: 前端开发团队
