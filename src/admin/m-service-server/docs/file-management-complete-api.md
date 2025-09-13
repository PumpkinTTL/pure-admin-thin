# 📁 文件管理模块完整API文档

## 📋 基础信息
- **服务名称**: 文件管理服务
- **基础路径**: `/api/v1`
- **协议**: HTTP/HTTPS
- **数据格式**: JSON
- **字符编码**: UTF-8
- **认证方式**: JWT Token验证（accessToken + refreshToken）

## 🚀 接口列表

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

**JavaScript调用示例**:
```javascript
const formData = new FormData();
formData.append('files', file1);
formData.append('files', file2);
formData.append('user_id', 1);
formData.append('remark', '测试上传');

const response = await axios.post('/api/v1/upload/uploadFile', formData, {
    headers: {
        'Content-Type': 'multipart/form-data',
        'accessToken': 'your_access_token',
        'refreshToken': 'your_refresh_token'
    }
});
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
    "file_id": 123
}
```

**响应示例**:
```json
{
    "code": 200,
    "message": "文件已永久删除"
}
```

**⚠️ 重要说明**:
- 永久删除会同时删除数据库记录和物理文件
- 删除后无法恢复，请谨慎操作

### 8. 批量删除文件（通过ID）
**接口地址**: `POST /api/v1/file/batchDelete`
**Content-Type**: `application/json`

**请求参数**:
```json
{
    "file_ids": [123, 456, 789],
    "is_force": false
}
```

**参数说明**:
- `file_ids`: 文件ID数组（必填）
- `is_force`: false=软删除（可恢复），true=永久删除（物理文件+SQL记录）

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
            }
        ],
        "file_type_stats": [
            {
                "file_extension": "jpg",
                "count": 500
            }
        ]
    }
}
```

## 📊 通用响应格式

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

## 🔧 删除功能说明

### 软删除 vs 永久删除
| 操作 | 数据库记录 | 物理文件 | 可恢复 |
|------|------------|----------|--------|
| **软删除** | 标记删除 | 保留 | ✅ 是 |
| **永久删除** | 彻底删除 | 彻底删除 | ❌ 否 |

### 删除接口对比
| 接口 | 删除方式 | 参数 |
|------|----------|------|
| `/upload/deleteFiles` | 通过URL批量删除 | `{"urls": [...]}` |
| `/file/delete` | 单个软删除 | `{"file_id": 123}` |
| `/file/forceDelete` | 单个永久删除 | `{"file_id": 123}` |
| `/file/batchDelete` | 批量删除（可选模式） | `{"file_ids": [...], "is_force": true/false}` |

## ✨ 功能特性

- ✅ **多文件上传**: 支持最多8个文件同时上传
- ✅ **文件去重**: 基于MD5哈希值自动去重
- ✅ **软删除**: 支持软删除和恢复功能
- ✅ **永久删除**: 支持彻底删除文件和记录
- ✅ **批量操作**: 支持批量删除（URL和ID两种方式）
- ✅ **多条件查询**: 支持按各种条件筛选文件
- ✅ **分页查询**: 支持分页，提高查询效率
- ✅ **统计信息**: 提供详细的文件统计数据
- ✅ **多存储支持**: 支持本地存储和云存储
- ✅ **用户关联**: 支持用户关联和权限控制
- ✅ **详细日志**: 记录所有操作日志
- ✅ **事务处理**: 确保数据一致性

## 🚨 注意事项

1. **文件大小限制**: 单个文件最大8MB
2. **文件数量限制**: 单次上传最多8个文件
3. **永久删除不可恢复**: 请谨慎使用永久删除功能
4. **批量操作建议**: 单次批量操作建议不超过100个文件
5. **认证要求**: 所有接口都需要有效的JWT Token

---

**文档版本**: v2.0  
**最后更新**: 2025-08-19  
**接口状态**: ✅ 已测试通过
