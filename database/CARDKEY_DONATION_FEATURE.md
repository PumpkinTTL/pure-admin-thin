# 卡密捐赠功能实现文档

## 📋 功能概述

本次更新扩展了卡密管理系统，支持用户使用卡密进行捐赠。卡密使用记录表（`bl_card_key_logs`）现在支持多种使用类型，包括兑换会员、捐赠、注册邀请、商品兑换等。

## 🗄️ 数据库变更

### 1. 卡密使用记录表扩展

执行以下SQL迁移脚本：

#### 第一步：添加基础字段

文件：`database/bl_card_key_logs_add_donation.sql`

- 添加 `use_type` 字段：区分使用类型
- 添加 `donation_id` 字段：关联捐赠记录

#### 第二步：优化字段支持更多类型

文件：`database/bl_card_key_logs_update_use_type.sql`

- 将 `donation_id` 重命名为 `related_id`（更通用）
- 添加 `related_type` 字段：明确关联的表类型
- 扩展 `use_type` 支持更多使用场景

### 2. 新表结构

```sql
bl_card_key_logs 表字段：
- id (int) - 主键ID
- card_key_id (int) - 卡密ID
- user_id (int) - 用户ID
- action (varchar) - 操作类型：使用、验证、禁用等
- use_type (varchar) - 使用类型：membership/donation/register/product/points/other
- related_id (int) - 关联ID（根据use_type不同关联不同的表）
- related_type (varchar) - 关联类型：donation/order/user/points等
- expire_time (datetime) - 会员到期时间
- ip (varchar) - 操作IP地址
- user_agent (varchar) - 用户代理信息
- create_time (datetime) - 操作时间
- remark (varchar) - 备注信息
```

### 3. 使用类型说明

| use_type   | 说明             | related_id   | related_type |
| ---------- | ---------------- | ------------ | ------------ |
| membership | 兑换会员（默认） | NULL         | NULL         |
| donation   | 捐赠             | 捐赠记录ID   | 'donation'   |
| register   | 注册邀请         | 新用户ID     | 'user'       |
| product    | 商品兑换         | 订单ID       | 'order'      |
| points     | 积分兑换         | 积分记录ID   | 'points'     |
| other      | 其他             | 根据实际情况 | 根据实际情况 |

## 🔧 后端代码变更

### 1. Model层 - CardKeyLog.php

**文件路径**：`src/admin/m-service-server/app/api/model/CardKeyLog.php`

**新增常量**：

```php
// 使用类型常量
const USE_TYPE_MEMBERSHIP = 'membership';  // 兑换会员
const USE_TYPE_DONATION = 'donation';      // 捐赠
const USE_TYPE_REGISTER = 'register';      // 注册邀请
const USE_TYPE_PRODUCT = 'product';        // 商品兑换
const USE_TYPE_POINTS = 'points';          // 积分兑换
const USE_TYPE_OTHER = 'other';            // 其他

// 关联类型常量
const RELATED_TYPE_DONATION = 'donation';  // 捐赠记录
const RELATED_TYPE_ORDER = 'order';        // 订单
const RELATED_TYPE_USER = 'user';          // 用户
const RELATED_TYPE_POINTS = 'points';      // 积分记录
```

**修改方法**：

- `addLog()` - 添加 `use_type`、`related_id`、`related_type` 参数支持

### 2. Service层 - DonationService.php

**文件路径**：`src/admin/m-service-server/app/api/services/DonationService.php`

**新增引用**：

```php
use app\api\model\CardKeyLog;
```

**修改方法**：

#### `add()` 方法

- 在创建捐赠记录后，更新卡密日志的 `related_id` 字段

#### `processCardKeyDonation()` 方法

- 添加 `$donationId` 参数
- 标记卡密为已使用状态
- 记录卡密使用日志，设置 `use_type` 为 `donation`
- 验证卡密是否过期

## 🎨 前端代码变更

### 1. 图标统一

**文件路径**：`src/views/basic/donation.vue`

**修改内容**：

- 将所有卡密相关的 `key` 图标统一改为 `ticket` 图标
- 更新 `getChannelIcon()` 方法中的图标映射

**修改位置**：

```javascript
const getChannelIcon = (channel: string) => {
  const iconMap: Record<string, any> = {
    "wechat": ["fab", "weixin"],
    "alipay": ["fab", "alipay"],
    "crypto": ["fab", "bitcoin"],
    "cardkey": ["fas", "ticket"]  // 从 "key" 改为 "ticket"
  };
  return iconMap[channel] || ["fas", "question"];
};
```

## 📊 业务流程

### 卡密捐赠流程

1. **用户提交捐赠**

   - 选择捐赠渠道为"卡密兑换"
   - 输入卡密码
   - 填写捐赠者信息

2. **后端验证卡密**

   - 检查卡密是否存在
   - 验证卡密状态（未使用）
   - 验证卡密是否过期
   - 获取卡密价值

3. **标记卡密已使用**

   - 更新卡密状态为"已使用"
   - 记录使用者ID和使用时间

4. **记录使用日志**

   - 创建卡密使用记录
   - 设置 `use_type` 为 `donation`
   - 设置 `related_type` 为 `donation`

5. **创建捐赠记录**

   - 保存捐赠信息
   - 关联卡密ID和价值

6. **更新日志关联**
   - 将卡密日志的 `related_id` 更新为捐赠记录ID

## 🔍 查询示例

### 查询所有捐赠类型的卡密使用记录

```sql
SELECT
    ckl.*,
    ck.card_key,
    d.donation_no,
    d.donor_name,
    d.amount
FROM bl_card_key_logs ckl
LEFT JOIN bl_card_keys ck ON ckl.card_key_id = ck.id
LEFT JOIN bl_donations d ON ckl.related_id = d.id
WHERE ckl.use_type = 'donation'
ORDER BY ckl.create_time DESC;
```

### 查询某个捐赠记录关联的卡密信息

```sql
SELECT
    d.*,
    ck.card_key,
    ck.type_id,
    ct.type_name,
    ct.price
FROM bl_donations d
LEFT JOIN bl_card_keys ck ON d.card_key_id = ck.id
LEFT JOIN bl_card_types ct ON ck.type_id = ct.id
WHERE d.channel = 'cardkey'
AND d.id = ?;
```

### 统计各种使用类型的卡密数量

```sql
SELECT
    use_type,
    COUNT(*) as count,
    COUNT(DISTINCT card_key_id) as unique_cards
FROM bl_card_key_logs
GROUP BY use_type
ORDER BY count DESC;
```

## ✅ 测试要点

### 1. 卡密捐赠测试

- [ ] 使用有效卡密进行捐赠
- [ ] 使用已使用的卡密（应失败）
- [ ] 使用已禁用的卡密（应失败）
- [ ] 使用过期的卡密（应失败）
- [ ] 使用不存在的卡密（应失败）

### 2. 数据一致性测试

- [ ] 捐赠记录正确关联卡密ID
- [ ] 卡密状态正确更新为"已使用"
- [ ] 卡密日志正确记录使用类型和关联ID
- [ ] 捐赠金额等于卡密价值

### 3. 日志查询测试

- [ ] 查询捐赠类型的卡密使用记录
- [ ] 查询某个用户的所有卡密使用记录
- [ ] 统计各种使用类型的数量

## 🚀 后续扩展

### 1. 注册邀请功能

- 用户使用邀请码注册
- 记录 `use_type` 为 `register`
- `related_id` 为新用户ID

### 2. 商品兑换功能

- 用户使用卡密兑换商品
- 记录 `use_type` 为 `product`
- `related_id` 为订单ID

### 3. 积分兑换功能

- 用户使用卡密兑换积分
- 记录 `use_type` 为 `points`
- `related_id` 为积分记录ID

## 📝 注意事项

1. **事务处理**：卡密使用和捐赠记录创建必须在同一个事务中
2. **并发控制**：需要考虑卡密并发使用的情况
3. **日志完整性**：确保每次卡密使用都有对应的日志记录
4. **数据回滚**：如果捐赠记录创建失败，需要回滚卡密状态

## 🎉 完成状态

- ✅ 数据库表结构扩展
- ✅ 后端Model层更新
- ✅ 后端Service层更新
- ✅ 前端图标统一
- ✅ 卡密捐赠功能实现
- ⏳ 前端卡密捐赠表单（待实现）
- ⏳ 卡密使用记录查询页面（待实现）
