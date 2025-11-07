# 卡密系统修复总结

## 🐛 发现的问题

### 1. 会员兑换卡密记录问题

**现象**：使用卡密兑换会员后，`bl_card_key_logs` 表中的记录没有 `use_type`、`related_id`、`related_type` 字段值

**原因**：`CardKeyService::use()` 方法直接使用 `CardKeyLog::create()` 创建日志，没有使用 `CardKeyLog::addLog()` 方法

**影响**：

- 所有会员兑换记录的 `use_type` 字段为空或默认值
- 无法区分卡密的使用类型
- 统计和查询功能受影响

### 2. 卡密捐赠记录问题

**现象**：使用卡密进行捐赠后，记录显示为 `membership` 而不是 `donation`，且没有创建捐赠记录

**原因**：可能是数据库迁移SQL没有执行，或者前端提交的数据格式不正确

## ✅ 已完成的修复

### 1. 后端代码修复

#### CardKeyService.php

修复了3处日志创建代码，全部改为使用 `CardKeyLog::addLog()` 方法：

**修复1：use() 方法（第300-313行）**

```php
// 修复前
CardKeyLog::create([
    'card_key_id' => $cardKeyModel->id,
    'user_id' => $userId,
    'action' => 'use',
    'ip' => $extra['ip'] ?? '',
    'user_agent' => $extra['user_agent'] ?? '',
    'remark' => $logRemark,
    'create_time' => date('Y-m-d H:i:s')
]);

// 修复后
CardKeyLog::addLog(
    $cardKeyModel->id,
    $userId,
    CardKeyLog::ACTION_USE,
    [
        'use_type' => CardKeyLog::USE_TYPE_MEMBERSHIP,
        'related_id' => null,
        'related_type' => null,
        'expire_time' => $membershipInfo['expiration_time'] ?? null,
        'ip' => $extra['ip'] ?? request()->ip(),
        'user_agent' => $extra['user_agent'] ?? request()->header('user-agent'),
        'remark' => $logRemark
    ]
);
```

**修复2：disable() 方法（第480-488行）**

```php
// 修复前
CardKeyLog::create([
    'card_key_id' => $cardKey->id,
    'user_id' => $userId,
    'action' => 'disable',
    'remark' => $reason,
    'create_time' => date('Y-m-d H:i:s')
]);

// 修复后
CardKeyLog::addLog(
    $cardKey->id,
    $userId,
    CardKeyLog::ACTION_DISABLE,
    [
        'remark' => $reason
    ]
);
```

**修复3：reset() 方法（第637-645行）**

```php
// 修复前
CardKeyLog::create([
    'card_key_id' => $cardKey->id,
    'user_id' => $userId,
    'action' => 'reset',
    'remark' => $reason ?: '测试重置',
    'create_time' => date('Y-m-d H:i:s')
]);

// 修复后
CardKeyLog::addLog(
    $cardKey->id,
    $userId,
    'reset',
    [
        'remark' => $reason ?: '测试重置'
    ]
);
```

#### CardKeyLog.php

- ✅ 添加了使用类型常量（6种）
- ✅ 添加了关联类型常量（4种）
- ✅ 更新了 `addLog()` 方法，支持新字段

#### DonationService.php

- ✅ 引入 `CardKeyLog` Model
- ✅ 修改 `processCardKeyDonation()` 方法，标记卡密为已使用并记录日志
- ✅ 修改 `add()` 方法，创建捐赠记录后更新卡密日志的 `related_id`

### 2. 数据库迁移SQL

创建了两个迁移脚本：

1. **bl_card_key_logs_add_donation.sql** - 添加基础字段
2. **bl_card_key_logs_update_use_type.sql** - 优化字段支持更多类型

## 📋 需要执行的操作

### 步骤1：检查数据库表结构

执行检查SQL：

```bash
mysql -u your_username -p your_database < database/check_card_key_logs_structure.sql
```

查看输出，确认是否有以下字段：

- `use_type` (varchar)
- `related_id` (int)
- `related_type` (varchar)

### 步骤2：执行数据库迁移

**如果字段不存在或不完整**，按顺序执行以下SQL：

```bash
# 第一步：添加基础字段（如果还没执行过）
mysql -u your_username -p your_database < database/bl_card_key_logs_add_donation.sql

# 第二步：优化字段支持更多类型
mysql -u your_username -p your_database < database/bl_card_key_logs_update_use_type.sql
```

### 步骤3：清理旧数据（可选）

如果需要清理之前没有 `use_type` 的记录：

```sql
-- 将所有空的 use_type 设置为 'membership'
UPDATE `bl_card_key_logs`
SET `use_type` = 'membership'
WHERE `use_type` IS NULL OR `use_type` = '';

-- 或者删除旧的测试数据
DELETE FROM `bl_card_key_logs`
WHERE `use_type` IS NULL OR `use_type` = '';
```

### 步骤4：测试功能

#### 测试1：会员兑换

1. 创建一个会员卡密
2. 使用卡密兑换会员
3. 查询 `bl_card_key_logs` 表，确认：
   - `use_type` = 'membership'
   - `related_id` = NULL
   - `related_type` = NULL
   - `expire_time` 有值

#### 测试2：卡密捐赠

1. 创建一个捐赠卡密（99元）
2. 在捐赠管理页面，选择"卡密兑换"渠道
3. 输入卡密码和捐赠者信息
4. 提交表单
5. 查询 `bl_card_key_logs` 表，确认：
   - `use_type` = 'donation'
   - `related_id` = 捐赠记录ID
   - `related_type` = 'donation'
6. 查询 `bl_donations` 表，确认捐赠记录已创建

## 🔍 排查指南

### 问题1：会员兑换后 use_type 还是空的

**可能原因**：

1. 数据库迁移SQL没有执行
2. 后端代码没有更新（需要重启PHP服务）

**解决方法**：

```bash
# 1. 检查表结构
SHOW COLUMNS FROM `bl_card_key_logs`;

# 2. 如果字段不存在，执行迁移SQL
mysql -u your_username -p your_database < database/bl_card_key_logs_update_use_type.sql

# 3. 重启PHP服务（如果使用PHP-FPM）
sudo systemctl restart php-fpm
# 或者
sudo service php-fpm restart
```

### 问题2：卡密捐赠后没有创建捐赠记录

**可能原因**：

1. 卡密已经被使用过
2. 卡密已过期
3. 前端提交的数据格式不正确

**解决方法**：

```bash
# 1. 检查卡密状态
SELECT * FROM `bl_card_keys` WHERE `card_key` = '你的卡密码';

# 2. 如果卡密已使用，重置状态（仅测试环境）
UPDATE `bl_card_keys`
SET `status` = 0, `user_id` = NULL, `use_time` = NULL
WHERE `card_key` = '你的卡密码';

# 3. 查看后端日志
tail -f /path/to/your/php/error.log
```

### 问题3：use_type 显示为 'membership' 而不是 'donation'

**可能原因**：

1. 使用了错误的API接口（使用了卡密兑换接口而不是捐赠接口）
2. 前端提交的 `channel` 不是 'cardkey'

**解决方法**：

- 确保在捐赠管理页面提交，而不是在卡密管理页面
- 确保选择的渠道是"卡密兑换"

## 📊 验证SQL

### 查看最近的卡密使用记录

```sql
SELECT
    ckl.id,
    ckl.card_key_id,
    ck.card_key,
    ckl.user_id,
    u.username,
    ckl.action,
    ckl.use_type,
    ckl.related_id,
    ckl.related_type,
    ckl.create_time,
    ckl.remark
FROM `bl_card_key_logs` ckl
LEFT JOIN `bl_card_keys` ck ON ckl.card_key_id = ck.id
LEFT JOIN `bl_users` u ON ckl.user_id = u.id
ORDER BY ckl.create_time DESC
LIMIT 20;
```

### 查看捐赠类型的卡密使用记录

```sql
SELECT
    ckl.*,
    d.donation_no,
    d.donor_name,
    d.card_key_value
FROM `bl_card_key_logs` ckl
LEFT JOIN `bl_donations` d ON ckl.related_id = d.id
WHERE ckl.use_type = 'donation'
ORDER BY ckl.create_time DESC;
```

### 统计各种使用类型

```sql
SELECT
    use_type,
    COUNT(*) as count,
    COUNT(DISTINCT card_key_id) as unique_cards,
    COUNT(DISTINCT user_id) as unique_users
FROM `bl_card_key_logs`
GROUP BY use_type
ORDER BY count DESC;
```

## ✨ 功能说明

### 使用类型（use_type）

| 值         | 说明     | related_id   | related_type |
| ---------- | -------- | ------------ | ------------ |
| membership | 兑换会员 | NULL         | NULL         |
| donation   | 捐赠     | 捐赠记录ID   | 'donation'   |
| register   | 注册邀请 | 新用户ID     | 'user'       |
| product    | 商品兑换 | 订单ID       | 'order'      |
| points     | 积分兑换 | 积分记录ID   | 'points'     |
| other      | 其他     | 根据实际情况 | 根据实际情况 |

### 操作类型（action）

- `使用` - 使用卡密
- `验证` - 验证卡密
- `禁用` - 禁用卡密
- `启用` - 启用卡密
- `reset` - 重置卡密（测试用）

## 🎉 完成状态

- ✅ 后端代码修复完成
- ✅ 数据库迁移SQL准备完成
- ✅ 文档和说明完成
- ⏳ 等待执行数据库迁移
- ⏳ 等待测试验证
