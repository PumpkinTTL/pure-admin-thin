# 📁 文件管理模块 API 接口文档

## 📋 目录
- [基础信息](#基础信息)
- [上传相关接口](#上传相关接口)
- [文件管理接口](#文件管理接口)
- [响应格式说明](#响应格式说明)
- [功能特性](#功能特性)
- [错误码说明](#错误码说明)

## 基础信息
- **服务名称**: 文件管理服务
- **基础路径**: `/api/v1`
- **协议**: HTTP/HTTPS
- **数据格式**: JSON
- **字符编码**: UTF-8
- **认证方式**: JWT Token验证

## 上传相关接口

### 1. 文件上传
**接口地址**: `POST /api/v1/upload/uploadFile`
**Content-Type**: `multipart/form-data`

**请求参数**:
```
files: 文件（最多8个，单个最大8MB）
user_id: 用户ID（可选）
remark: 备注信息（可选，默认"未备注信息"）
storage_type: 存储类型（可选，默认0）
bucket_name: 存储桶名称（可选）
device_fingerprint: 设备指纹（可选）
```

**响应示例**:
```json
{
    "code": 200,
    "msg": "上传成功",
    "data": [
        {
            "file_id": 123,
            "original_name": "test.jpg",
            "save_path": "upload/20250819/image/xxx.jpg",
            "file_type": "image",
            "mime_type": "image/jpeg",
            "size": 1024000,
            "url": "http://localhost/pics/20250819/image/xxx.jpg",
            "is_duplicate": false
        }
    ]
}
```

### 2. 批量删除文件（通过URL）
**接口地址**: `POST /api/v1/upload/deleteFiles`
**Content-Type**: `application/json`

**请求参数**:
```json
{
    "urls": [
        "http://localhost/pics/20250819/image/xxx.png",
        "http://localhost/pics/20250819/image/yyy.png"
    ]
}
```

**响应示例**:
```json
{
    "code": 200,
    "msg": "删除完成，成功：2个，失败：0个",
    "data": {
        "total": 2,
        "success_count": 2,
        "failed_count": 0,
        "details": [
            {
                "url": "http://localhost/pics/20250819/image/xxx.png",
                "status": "success",
                "message": "删除成功"
            }
        ]
    }
}
```

## 文件管理接口

### 3. 获取文件列表
**接口地址**: `GET /api/v1/file/list`

**请求参数**:
```
page: 页码（默认1）
page_size: 每页数量（默认100）
status: 状态（active=正常，deleted=已删除，all=全部）
original_name: 文件名模糊搜索
file_type: 文件类型
file_extension: 文件扩展名
user_id: 上传用户ID
storage_type: 存储类型（0=本地，1=阿里云，2=七牛，3=腾讯云）
min_size: 最小文件大小（字节）
max_size: 最大文件大小（字节）
start_date: 开始时间（YYYY-MM-DD）
end_date: 结束时间（YYYY-MM-DD）
sort_field: 排序字段
sort_order: 排序方式（asc/desc）
```

**响应示例**:
```json
{
    "code": 200,
    "data": {
        "total": 100,
        "per_page": 20,
        "current_page": 1,
        "data": [
            {
                "file_id": 123,
                "original_name": "test.jpg",
                "store_name": "xxx.jpg",
                "file_path": "upload/20250819/image/xxx.jpg",
                "file_size": 1024000,
                "file_size_format": "1.00 MB",
                "file_type": "image/jpeg",
                "file_extension": "jpg",
                "storage_type": 0,
                "storage_type_text": "本地存储",
                "http_url": "http://localhost/pics/20250819/image/xxx.jpg",
                "remark": "测试图片",
                "create_time": "2025-08-19 10:30:00",
                "user": {
                    "id": 1,
                    "username": "admin",
                    "avatar": "avatar.jpg"
                }
            }
        ]
    }
}
```

### 4. 获取文件详情
**接口地址**: `GET /api/v1/file/detail`

**请求参数**:
```
file_id: 文件ID（必填）
```

**响应示例**:
```json
{
    "code": 200,
    "message": "获取成功",
    "data": {
        "file_id": 123,
        "original_name": "test.jpg",
        "store_name": "xxx.jpg",
        "file_path": "upload/20250819/image/xxx.jpg",
        "file_size": 1024000,
        "file_size_format": "1.00 MB",
        "file_type": "image/jpeg",
        "file_extension": "jpg",
        "file_hash": "d41d8cd98f00b204e9800998ecf8427e",
        "hash_algorithm": "MD5",
        "storage_type": 0,
        "storage_type_text": "本地存储",
        "bucket_name": null,
        "http_url": "http://localhost/pics/20250819/image/xxx.jpg",
        "device_fingerprint": "device123",
        "remark": "测试图片",
        "create_time": "2025-08-19 10:30:00",
        "update_time": "2025-08-19 10:30:00",
        "user": {
            "id": 1,
            "username": "admin",
            "avatar": "avatar.jpg"
        }
    }
}
```

### 5. 软删除文件
**接口地址**: `POST /api/v1/file/delete`
**Content-Type**: `application/json`

**请求参数**:
```json
{
    "file_id": 123
}
```

**响应示例**:
```json
{
    "code": 200,
    "message": "文件删除成功"
}
```

### 6. 恢复已删除文件
**接口地址**: `POST /api/v1/file/restore`
**Content-Type**: `application/json`

**请求参数**:
```json
{
    "file_id": 123
}
```

**响应示例**:
```json
{
    "code": 200,
    "message": "文件恢复成功"
}
```

### 7. 永久删除文件
**接口地址**: `POST /api/v1/file/forceDelete`
**Content-Type**: `application/json`

**请求参数**:
```json
{
    "file_id": 123,
    "delete_physical": true
}
```

**参数说明**:
- `file_id`: 文件ID（必填）
- `delete_physical`: 是否同时删除物理文件（可选，默认false）
  - `true`: 同时删除数据库记录和物理文件（不可恢复）
  - `false`: 仅删除数据库记录，保留物理文件

**响应示例**:
```json
{
    "code": 200,
    "message": "文件已永久删除"
}
```

**⚠️ 重要说明**:
- 永久删除后数据库记录无法恢复
- 当`delete_physical=true`时，物理文件也会被删除，无法恢复
- 建议在删除前先进行软删除，确认无误后再永久删除

### 8. 批量删除文件（通过ID）
**接口地址**: `POST /api/v1/file/batchDelete`
**Content-Type**: `application/json`

**请求参数**:
```json
{
    "file_ids": [123, 456, 789],
    "is_force": false,
    "delete_physical": true
}
```

**参数说明**:
- `file_ids`: 文件ID数组（必填）
- `is_force`: 是否永久删除（可选，true=永久删除，false=软删除，默认false）
- `delete_physical`: 是否删除物理文件（可选，仅在永久删除时有效，默认false）
  - `true`: 同时删除数据库记录和物理文件（不可恢复）
  - `false`: 仅删除数据库记录，保留物理文件

**响应示例**:
```json
{
    "code": 200,
    "message": "批量删除完成，成功：3，失败：0",
    "data": {
        "fail_ids": []
    }
}
```

**⚠️ 重要说明**:
- 当`is_force=false`时，执行软删除，`delete_physical`参数无效
- 当`is_force=true`且`delete_physical=true`时，会彻底删除文件和数据，无法恢复
- 建议分批处理大量文件，避免超时

### 9. 获取文件统计信息
**接口地址**: `GET /api/v1/file/stats`

**响应示例**:
```json
{
    "code": 200,
    "message": "获取文件统计信息成功",
    "data": {
        "total_count": 1000,
        "active_count": 950,
        "deleted_count": 50,
        "total_size": 1073741824,
        "total_size_format": "1.00 GB",
        "storage_type_stats": [
            {
                "type": 0,
                "type_name": "本地存储",
                "count": 800
            },
            {
                "type": 1,
                "type_name": "阿里云OSS",
                "count": 200
            }
        ],
        "file_type_stats": [
            {
                "file_extension": "jpg",
                "count": 500
            },
            {
                "file_extension": "png",
                "count": 300
            }
        ]
    }
}
```

## 响应格式说明

### 成功响应
```json
{
    "code": 200,
    "msg": "操作成功",
    "data": { ... }
}
```

### 错误响应
```json
{
    "code": 400/500,
    "msg": "错误信息",
    "data": null
}
```

## 功能特性

- ✅ **文件上传**: 支持多文件上传，自动去重，最多8个文件，单个最大8MB
- ✅ **软删除**: 支持软删除和恢复，数据安全可恢复
- ✅ **物理删除**: 支持永久删除，可选择是否删除物理文件
- ✅ **批量操作**: 支持批量删除（URL方式和ID方式）
- ✅ **多条件查询**: 支持按文件名、类型、大小、时间等条件筛选
- ✅ **分页查询**: 支持分页，默认每页100条
- ✅ **统计信息**: 提供详细的文件统计数据
- ✅ **事务处理**: 确保数据一致性，操作失败自动回滚
- ✅ **详细日志**: 记录所有操作日志，便于问题排查
- ✅ **多存储支持**: 支持本地存储、阿里云OSS、七牛云、腾讯云COS
- ✅ **文件去重**: 基于MD5哈希值自动去重，节省存储空间
- ✅ **用户关联**: 支持用户关联，可按用户查询文件

## 错误码说明

| 错误码 | 说明 |
|--------|------|
| 200 | 操作成功 |
| 400 | 请求参数错误 |
| 500 | 服务器内部错误 |
| 502 | 未登录或登录失效 |
| 5003 | Token非法 |
| 5004 | 用户信息解析失败 |
| 5005 | 登录失效 |

## 注意事项

1. **文件大小限制**: 单个文件最大8MB，建议前端进行预检查
2. **文件类型**: 支持图片、视频、文档、音频、压缩包等常见格式
3. **去重机制**: 基于文件MD5哈希值进行去重，相同内容文件会复用
4. **软删除**: 删除的文件可以恢复，永久删除后无法恢复
5. **物理文件**: 永久删除时可选择是否同时删除物理文件
6. **事务处理**: 批量操作在同一事务中处理，任何失败都会回滚
7. **日志记录**: 所有操作都会记录详细日志，包括SQL执行时间

---

**文档版本**: v1.0  
**最后更新**: 2025-08-19  
**维护者**: 开发团队
