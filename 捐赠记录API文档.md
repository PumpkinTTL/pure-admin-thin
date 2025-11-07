# 📋 捐赠记录 API 文档 (客户端)

## 📌 基础信息

**Base URL**: `/api/v1/donation`
**数据格式**: JSON
**字段命名**: 下划线格式 (snake_case)

---

## 🎯 客户端接口

### 1. 添加捐赠记录

**接口**: `POST /api/v1/donation/add`

#### 1.1 微信/支付宝捐赠

```json
{
  "channel": "wechat",
  "donor_name": "张三",
  "email": "zhangsan@example.com",
  "iden": "user_unique_id",
  "amount": 100.0,
  "order_no": "WX20250129XXXX",
  "is_anonymous": 0,
  "is_public": 1,
  "remark": "支持项目"
}
```

#### 1.2 加密货币捐赠

```json
{
  "channel": "crypto",
  "donor_name": "李四",
  "email": "lisi@example.com",
  "iden": "user_unique_id",
  "amount": 100.0,
  "crypto_type": "USDT",
  "crypto_network": "TRC20",
  "transaction_hash": "0x1234567890abcdef",
  "is_anonymous": 0,
  "is_public": 1
}
```

#### 1.3 卡密捐赠 ⭐

```json
{
  "channel": "cardkey",
  "card_key_code": "ABCD-1234-EFGH-5678",
  "donor_name": "王五",
  "email": "wangwu@example.com",
  "iden": "user_unique_id",
  "is_anonymous": 0,
  "is_public": 1,
  "remark": "使用卡密捐赠"
}
```

**成功响应**:

```json
{
  "code": 200,
  "message": "添加成功",
  "data": {
    "id": 1,
    "donation_no": "DON20250129XXXX",
    "card_key_value": 99.0,
    "status": 0
  }
}
```

**卡密捐赠说明**:

- 后端自动验证卡密并标记为已使用
- 自动获取卡密价值
- 无需前端传递 `card_key_value`

**错误响应**:

```json
{
  "code": 400,
  "message": "卡密不存在/已使用/已过期"
}
```

---

### 2. 查询我的捐赠记录

**接口**: `GET /api/v1/donation/query`

**请求参数** (至少提供一个):

```
email=zhangsan@example.com
或
iden=user_unique_id
或
user_id=123
或
email=zhangsan@example.com&iden=user_unique_id&user_id=123
```

**说明**:

- 可以单独使用 `email`、`iden` 或 `user_id`
- 也可以组合使用,使用 OR 查询
- 返回所有匹配条件的捐赠记录

**响应**:

```json
{
  "code": 200,
  "message": "查询成功",
  "data": [
    {
      "id": 1,
      "donation_no": "DON20250129XXXX",
      "donor_name": "张三",
      "amount": 100.0,
      "channel": "cardkey",
      "channel_text": "卡密兑换",
      "status": 1,
      "status_text": "已确认",
      "card_key_value": 99.0,
      "create_time": "2025-01-29 12:00:00"
    }
  ]
}
```

---

### 3. 获取捐赠详情

**接口**: `GET /api/v1/donation/detail`

**请求参数**:

```
id=1
```

**响应**:

```json
{
  "code": 200,
  "message": "获取成功",
  "data": {
    "id": 1,
    "donation_no": "DON20250129XXXX",
    "donor_name": "张三",
    "email": "zhangsan@example.com",
    "amount": 99.0,
    "channel": "cardkey",
    "status": 1,
    "card_key_code": "ABCD-****-****-5678",
    "card_key_value": 99.0,
    "create_time": "2025-01-29 12:00:00"
  }
}
```

---

## 📊 数据字段说明

### 捐赠渠道 (channel)

- `wechat` - 微信支付
- `alipay` - 支付宝
- `crypto` - 加密货币
- `cardkey` - 卡密兑换

### 捐赠状态 (status)

- `0` - 待确认
- `1` - 已确认
- `2` - 已完成
- `3` - 已取消

### 通用字段

- `donor_name` - 捐赠者姓名
- `email` - 邮箱 (用于查询捐赠记录)
- `iden` - 唯一标识符 (用于查询捐赠记录)
- `is_anonymous` - 是否匿名 (0=否, 1=是)
- `is_public` - 是否公开 (0=否, 1=是)
- `remark` - 备注

---

## ⚠️ 重要说明

### 卡密捐赠流程

1. **提交卡密码**: 客户端提交 `card_key_code`
2. **后端自动验证**: 检查卡密是否存在、未使用、未过期
3. **自动标记使用**: 卡密状态变为已使用
4. **自动获取价值**: 从卡密类型获取价值
5. **创建捐赠记录**: 保存捐赠信息

### 查询捐赠记录

- 支持 `email`、`iden` 或 `user_id` 查询
- 可同时提供多个参数 (OR查询)
- 只返回未删除的记录
- 按创建时间倒序排列

### 必填字段

**所有捐赠**:

- `channel` - 捐赠渠道

**卡密捐赠**:

- `card_key_code` - 卡密码

**建议提供** (用于查询):

- `email`、`iden` 或 `user_id` - 至少提供一个

---

**文档版本**: v1.0 (客户端)
**最后更新**: 2025-10-29
