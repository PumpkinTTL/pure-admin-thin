# Redis Key存储说明

## Redis存储结构

Redis是**键值存储**，所有的Key都是**扁平存储**的，不存在真正的树形结构。

### 我们使用的Key格式

```bash
user:{userId}:platform:{platform}:access_token
user:{userId}:platform:{platform}:refresh_token:{refreshToken}
```

### 实际存储效果

在Redis中，这些Key是这样存储的：

```bash
# 用户12345在Web平台的token
user:12345:platform:Web:access_token
user:12345:platform:Web:refresh_token:12345678-1234-4123-8123-123456789012

# 用户12345在iOS平台的token  
user:12345:platform:iOS:access_token
user:12345:platform:iOS:refresh_token:87654321-4321-4321-8321-210987654321

# 用户67890在Web平台的token
user:67890:platform:Web:access_token
user:67890:platform:Web:refresh_token:abcdefgh-1234-4567-8901-123456789abc
```

## 为什么使用冒号分隔

### 1. 命名约定
- 冒号(:)是Redis社区广泛使用的命名约定
- 便于人类阅读和理解Key的层次关系
- 虽然不是真正的树形结构，但在逻辑上有层次感

### 2. 模式匹配
可以使用Redis的模式匹配功能：

```bash
# 查找用户12345的所有token
KEYS user:12345:*

# 查找所有Web平台的access_token
KEYS user:*:platform:Web:access_token

# 查找所有access_token
KEYS *:access_token
```

### 3. 便于管理
```bash
# 删除用户12345的所有token
DEL user:12345:platform:Web:access_token
DEL user:12345:platform:Web:refresh_token:*

# 或者使用脚本批量删除
EVAL "return redis.call('del', unpack(redis.call('keys', 'user:12345:*')))" 0
```

## 实际的Redis命令示例

### 存储AccessToken
```bash
SET user:12345:platform:Web:access_token "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..." EX 900
```

### 存储RefreshToken
```bash
SET user:12345:platform:Web:refresh_token:12345678-1234-4123-8123-123456789012 "{\"user_id\":\"12345\",\"fingerprint\":\"fp_xxx\"}" EX 604800
```

### 查询Token
```bash
GET user:12345:platform:Web:access_token
GET user:12345:platform:Web:refresh_token:12345678-1234-4123-8123-123456789012
```

### 删除Token（登出）
```bash
DEL user:12345:platform:Web:access_token
DEL user:12345:platform:Web:refresh_token:12345678-1234-4123-8123-123456789012
```

## 总结

1. **扁平存储**: Redis中所有Key都是扁平的，没有真正的树形结构
2. **命名约定**: 使用冒号分隔是为了便于管理和理解
3. **独立存储**: AccessToken和RefreshToken是完全独立的Key-Value对
4. **模式匹配**: 可以通过Key的命名模式进行批量操作
5. **TTL控制**: 每个Key都有独立的过期时间控制

这种设计既保持了Redis的简单性，又提供了良好的可管理性。
