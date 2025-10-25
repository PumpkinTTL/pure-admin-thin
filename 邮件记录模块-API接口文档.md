# 邮件记录模块 - API接口文档

## 基础信息

- **Base URL**: `/api/v1/email-record`
- **认证方式**: Bearer Token
- **返回格式**: JSON

---

## 📬 1. 发送邮件

### 接口信息

- **URL**: `/api/v1/email-record/send`
- **Method**: `POST`
- **说明**: 创建邮件记录并发送邮件

### 请求参数

```json
{
  "notice_id": 1, // 可选，关联公告ID
  "title": "邮件标题",
  "content": "邮件内容",
  "receiver_type": 1, // 1-全部用户, 2-指定用户, 3-单个用户, 4-指定邮箱
  "receiver_ids": [1, 2, 3], // receiver_type=2或3时必填
  "receiver_emails": [
    // receiver_type=4时必填
    "user1@example.com",
    "user2@example.com"
  ]
}
```

### 响应示例

```json
{
  "code": 200,
  "message": "邮件发送成功",
  "data": {
    "record_id": 123,
    "total_count": 10,
    "success_count": 8,
    "failed_count": 2,
    "send_status": 3
  }
}
```

---

## 📋 2. 获取邮件记录列表

### 接口信息

- **URL**: `/api/v1/email-record/list`
- **Method**: `GET`
- **说明**: 获取邮件发送记录列表(分页)

### 请求参数

```
?page=1                    // 页码
&page_size=10              // 每页数量
&title=系统通知            // 邮件标题(模糊搜索)
&sender_name=admin         // 发送者(模糊搜索)
&send_status=2             // 发送状态(0-待发送, 1-发送中, 2-完成, 3-部分失败, 4-全部失败)
&receiver_type=1           // 接收方式
&start_time=2025-10-01     // 开始时间
&end_time=2025-10-31       // 结束时间
&notice_id=1               // 关联公告ID
```

### 响应示例

```json
{
  "code": 200,
  "message": "success",
  "data": {
    "list": [
      {
        "id": 1,
        "notice_id": 1,
        "sender_id": 1,
        "sender_name": "admin",
        "title": "【系统通知】系统升级公告",
        "content": "尊敬的用户...",
        "receiver_type": 1,
        "receiver_type_text": "全部用户",
        "total_count": 150,
        "success_count": 148,
        "failed_count": 2,
        "send_status": 2,
        "send_status_text": "发送完成",
        "send_time": "2025-10-24 10:30:00",
        "created_at": "2025-10-24 10:25:00"
      }
    ],
    "total": 100,
    "page": 1,
    "page_size": 10
  }
}
```

---

## 🔍 3. 获取邮件记录详情

### 接口信息

- **URL**: `/api/v1/email-record/detail/{id}`
- **Method**: `GET`
- **说明**: 获取邮件记录详细信息

### 响应示例

```json
{
  "code": 200,
  "message": "success",
  "data": {
    "id": 1,
    "notice_id": 1,
    "notice_title": "系统升级公告",
    "sender_id": 1,
    "sender_name": "admin",
    "title": "【系统通知】系统升级公告",
    "content": "尊敬的用户...",
    "receiver_type": 1,
    "receiver_type_text": "全部用户",
    "total_count": 150,
    "success_count": 148,
    "failed_count": 2,
    "send_status": 2,
    "send_status_text": "发送完成",
    "send_time": "2025-10-24 10:30:00",
    "created_at": "2025-10-24 10:25:00"
  }
}
```

---

## 👥 4. 获取接收者列表

### 接口信息

- **URL**: `/api/v1/email-record/receivers/{id}`
- **Method**: `GET`
- **说明**: 获取指定邮件记录的接收者列表

### 请求参数

```
?page=1                    // 页码
&page_size=20              // 每页数量
&send_status=1             // 发送状态(0-待发送, 1-成功, 2-失败)
&email=user@example.com    // 邮箱地址(模糊搜索)
```

### 响应示例

```json
{
  "code": 200,
  "message": "success",
  "data": {
    "list": [
      {
        "id": 1,
        "email_record_id": 1,
        "receiver_type": 1,
        "receiver_type_text": "系统用户",
        "user_id": 2,
        "username": "user001",
        "email": "user001@example.com",
        "send_status": 1,
        "send_status_text": "发送成功",
        "error_message": "",
        "send_time": "2025-10-24 10:30:15",
        "read_status": 1,
        "read_status_text": "已读",
        "read_time": "2025-10-24 11:00:00"
      },
      {
        "id": 2,
        "email_record_id": 1,
        "receiver_type": 1,
        "receiver_type_text": "系统用户",
        "user_id": 3,
        "username": "user002",
        "email": "user002@example.com",
        "send_status": 2,
        "send_status_text": "发送失败",
        "error_message": "SMTP连接超时",
        "send_time": "2025-10-24 10:30:16",
        "read_status": 0,
        "read_status_text": "未读",
        "read_time": null
      }
    ],
    "total": 150,
    "page": 1,
    "page_size": 20
  }
}
```

---

## 🔄 5. 重新发送失败邮件

### 接口信息

- **URL**: `/api/v1/email-record/resend/{id}`
- **Method**: `POST`
- **说明**: 重新发送失败的邮件

### 请求参数

```json
{
  "receiver_ids": [1, 2, 3] // 可选，指定要重发的接收者ID，不传则重发所有失败的
}
```

### 响应示例

```json
{
  "code": 200,
  "message": "重新发送成功",
  "data": {
    "total_count": 5,
    "success_count": 4,
    "failed_count": 1
  }
}
```

---

## 🗑️ 6. 删除邮件记录

### 接口信息

- **URL**: `/api/v1/email-record/delete/{id}`
- **Method**: `DELETE`
- **说明**: 软删除邮件记录

### 响应示例

```json
{
  "code": 200,
  "message": "删除成功"
}
```

---

## 🗑️ 7. 批量删除邮件记录

### 接口信息

- **URL**: `/api/v1/email-record/batch-delete`
- **Method**: `POST`
- **说明**: 批量软删除邮件记录

### 请求参数

```json
{
  "ids": [1, 2, 3, 4, 5]
}
```

### 响应示例

```json
{
  "code": 200,
  "message": "批量删除成功",
  "data": {
    "deleted_count": 5
  }
}
```

---

## 📊 8. 获取统计数据

### 接口信息

- **URL**: `/api/v1/email-record/statistics`
- **Method**: `GET`
- **说明**: 获取邮件发送统计数据

### 请求参数

```
?start_time=2025-10-01     // 开始时间
&end_time=2025-10-31       // 结束时间
```

### 响应示例

```json
{
  "code": 200,
  "message": "success",
  "data": {
    "total_records": 100, // 总记录数
    "total_emails": 15000, // 总邮件数
    "success_emails": 14500, // 成功数
    "failed_emails": 500, // 失败数
    "success_rate": 96.67, // 成功率(%)
    "today_records": 10, // 今日记录数
    "today_emails": 1500, // 今日邮件数
    "status_distribution": {
      // 状态分布
      "0": 5,
      "1": 2,
      "2": 80,
      "3": 10,
      "4": 3
    },
    "receiver_type_distribution": {
      // 接收方式分布
      "1": 30,
      "2": 40,
      "3": 20,
      "4": 10
    },
    "trend": [
      // 趋势数据(最近7天)
      {
        "date": "2025-10-24",
        "count": 15,
        "success": 14,
        "failed": 1
      }
    ]
  }
}
```

---

## 📝 状态码说明

### 发送状态 (send_status)

- `0`: 待发送
- `1`: 发送中
- `2`: 发送完成
- `3`: 部分失败
- `4`: 全部失败

### 接收方式 (receiver_type)

- `1`: 全部用户
- `2`: 指定用户
- `3`: 单个用户
- `4`: 指定邮箱

### 接收者类型 (receiver_type in receivers)

- `1`: 系统用户
- `2`: 外部邮箱

### 接收者发送状态 (send_status in receivers)

- `0`: 待发送
- `1`: 发送成功
- `2`: 发送失败

### 阅读状态 (read_status)

- `0`: 未读
- `1`: 已读

---

## 🔒 错误码

| 错误码 | 说明       |
| ------ | ---------- |
| 400    | 参数错误   |
| 401    | 未授权     |
| 403    | 无权限     |
| 404    | 记录不存在 |
| 500    | 服务器错误 |
