-- ============================================
-- 卡密类型表优化 - 添加使用类型字段
-- 执行时间: 2025-10-29
-- 说明: 在卡密类型表中添加use_type字段，明确卡密的用途
-- ============================================

USE blogs; -- 请根据实际数据库名修改

-- 1. 添加 use_type 字段
ALTER TABLE `bl_card_types` 
ADD COLUMN `use_type` VARCHAR(20) NOT NULL DEFAULT 'membership' COMMENT '使用类型：membership-兑换会员，donation-捐赠，register-注册邀请，product-商品兑换，points-积分兑换，other-其他' AFTER `type_code`;

-- 2. 添加索引
ALTER TABLE `bl_card_types` 
ADD INDEX `idx_use_type` (`use_type`) COMMENT '使用类型索引';

-- 3. 更新现有数据（根据type_code或type_name判断）
-- 如果type_code或type_name包含"会员"、"VIP"等关键词，设置为membership
UPDATE `bl_card_types` 
SET `use_type` = 'membership' 
WHERE `type_name` LIKE '%会员%' 
   OR `type_name` LIKE '%VIP%' 
   OR `type_code` LIKE '%member%' 
   OR `type_code` LIKE '%vip%'
   OR `membership_duration` IS NOT NULL;

-- 如果type_code或type_name包含"捐赠"、"donation"等关键词，设置为donation
UPDATE `bl_card_types` 
SET `use_type` = 'donation' 
WHERE `type_name` LIKE '%捐赠%' 
   OR `type_code` LIKE '%donation%'
   OR `type_code` LIKE '%donate%';

-- 如果type_code或type_name包含"注册"、"邀请"等关键词，设置为register
UPDATE `bl_card_types` 
SET `use_type` = 'register' 
WHERE `type_name` LIKE '%注册%' 
   OR `type_name` LIKE '%邀请%' 
   OR `type_code` LIKE '%register%' 
   OR `type_code` LIKE '%invite%';

-- 如果type_code或type_name包含"商品"、"兑换"等关键词，设置为product
UPDATE `bl_card_types` 
SET `use_type` = 'product' 
WHERE `type_name` LIKE '%商品%' 
   OR `type_name` LIKE '%兑换%' 
   OR `type_code` LIKE '%product%' 
   OR `type_code` LIKE '%goods%';

-- 如果type_code或type_name包含"积分"等关键词，设置为points
UPDATE `bl_card_types` 
SET `use_type` = 'points' 
WHERE `type_name` LIKE '%积分%' 
   OR `type_code` LIKE '%point%';

-- ============================================
-- 使用类型说明
-- ============================================
/*
use_type 字段值说明：

1. membership - 兑换会员
   - 用户使用卡密兑换会员时长
   - 需要设置 membership_duration 字段
   - 示例：7天会员卡、30天会员卡、永久会员卡

2. donation - 捐赠
   - 用户使用卡密进行捐赠
   - 需要设置 price 字段（等值金额）
   - 会创建捐赠记录
   - 示例：99元捐赠卡、199元捐赠卡

3. register - 注册邀请
   - 新用户使用邀请码注册
   - 可以设置 price 字段（邀请奖励）
   - 示例：注册邀请码

4. product - 商品兑换
   - 用户使用卡密兑换商品
   - 需要设置 price 字段（商品价值）
   - 会创建订单记录
   - 示例：实物商品兑换码、虚拟商品兑换码

5. points - 积分兑换
   - 用户使用卡密兑换积分
   - 需要设置 price 字段（积分数量）
   - 会创建积分记录
   - 示例：1000积分兑换码

6. other - 其他
   - 其他特殊用途
   - 根据实际需求自定义
*/

-- ============================================
-- 验证修改结果
-- ============================================

-- 查看表结构
SHOW COLUMNS FROM `bl_card_types`;

-- 查看现有数据的use_type分布
SELECT 
    use_type,
    COUNT(*) as count,
    GROUP_CONCAT(type_name SEPARATOR ', ') as type_names
FROM `bl_card_types`
GROUP BY use_type;

-- 查看所有类型的详细信息
SELECT 
    id,
    type_name,
    type_code,
    use_type,
    membership_duration,
    price,
    status
FROM `bl_card_types`
ORDER BY use_type, id;

-- ============================================
-- 完成
-- 新表结构应该包含以下字段:
-- id, type_name, type_code, use_type, description, icon,
-- membership_duration, price, available_days, sort_order, status,
-- create_time, update_time
-- ============================================

