-- ============================================
-- 卡密使用记录表优化 - 扩展更多使用类型
-- 执行时间: 2025-10-29
-- 说明: 优化use_type字段，支持更多使用场景
-- ============================================

USE blogs; -- 请根据实际数据库名修改

-- 1. 修改 use_type 字段，扩展支持更多类型
ALTER TABLE `bl_card_key_logs` 
MODIFY COLUMN `use_type` VARCHAR(20) NOT NULL DEFAULT 'membership' COMMENT '使用类型：membership-兑换会员，donation-捐赠，register-注册邀请，product-商品兑换，points-积分兑换，other-其他';

-- 2. 添加 related_id 字段（通用关联ID，根据use_type不同关联不同的表）
-- 如果已经有donation_id，我们重命名为related_id使其更通用
ALTER TABLE `bl_card_key_logs` 
CHANGE COLUMN `donation_id` `related_id` INT(11) NULL DEFAULT NULL COMMENT '关联ID：donation-捐赠记录ID，product-商品订单ID，register-用户ID等';

-- 3. 添加 related_type 字段，明确关联的表类型
ALTER TABLE `bl_card_key_logs` 
ADD COLUMN `related_type` VARCHAR(50) NULL DEFAULT NULL COMMENT '关联类型：donation-bl_donations，order-bl_orders，user-bl_users等' AFTER `related_id`;

-- 4. 更新现有数据
-- 将use_type='redeem'的记录更新为'membership'
UPDATE `bl_card_key_logs` SET `use_type` = 'membership' WHERE `use_type` = 'redeem';

-- 如果related_id有值，说明是捐赠记录，设置related_type
UPDATE `bl_card_key_logs` SET `related_type` = 'donation' WHERE `use_type` = 'donation' AND `related_id` IS NOT NULL;

-- 5. 添加索引
ALTER TABLE `bl_card_key_logs` 
ADD INDEX `idx_related_id` (`related_id`) COMMENT '关联ID索引';

ALTER TABLE `bl_card_key_logs` 
ADD INDEX `idx_related_type` (`related_type`) COMMENT '关联类型索引';

-- ============================================
-- 使用类型说明
-- ============================================
/*
use_type 字段值说明：
1. membership - 兑换会员（默认）
   - 用户使用卡密兑换会员时长
   - related_id: NULL
   - related_type: NULL

2. donation - 捐赠
   - 用户使用卡密进行捐赠
   - related_id: 捐赠记录ID（bl_donations.id）
   - related_type: 'donation'

3. register - 注册邀请
   - 新用户使用邀请码注册
   - related_id: 新注册用户ID（bl_users.id）
   - related_type: 'user'

4. product - 商品兑换
   - 用户使用卡密兑换商品
   - related_id: 订单ID（bl_orders.id）
   - related_type: 'order'

5. points - 积分兑换
   - 用户使用卡密兑换积分
   - related_id: 积分记录ID
   - related_type: 'points'

6. other - 其他
   - 其他特殊用途
   - related_id: 根据实际情况
   - related_type: 根据实际情况
*/

-- ============================================
-- 验证修改结果
-- ============================================

SHOW COLUMNS FROM `bl_card_key_logs`;
SHOW INDEX FROM `bl_card_key_logs`;

-- 查看现有数据
SELECT use_type, related_type, COUNT(*) as count 
FROM `bl_card_key_logs` 
GROUP BY use_type, related_type;

-- ============================================
-- 完成
-- 新表结构应该包含以下字段:
-- id, card_key_id, user_id, action, use_type, related_id, related_type,
-- expire_time, ip, user_agent, create_time, remark
-- ============================================

