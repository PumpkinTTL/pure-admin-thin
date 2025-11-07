-- ============================================
-- 卡密使用记录表扩展 - 支持捐赠功能
-- 执行时间: 2025-10-29
-- 说明: 添加use_type和donation_id字段，支持卡密用于捐赠
-- ============================================

USE blogs; -- 请根据实际数据库名修改

-- 1. 添加 use_type 字段，区分使用类型
ALTER TABLE `bl_card_key_logs` 
ADD COLUMN `use_type` VARCHAR(20) NOT NULL DEFAULT 'redeem' COMMENT '使用类型：redeem-兑换会员，donation-捐赠' AFTER `action`;

-- 2. 添加 donation_id 字段，关联捐赠记录
ALTER TABLE `bl_card_key_logs` 
ADD COLUMN `donation_id` INT(11) NULL DEFAULT NULL COMMENT '捐赠记录ID，关联bl_donations表，仅use_type=donation时有值' AFTER `use_type`;

-- 3. 添加索引
ALTER TABLE `bl_card_key_logs` 
ADD INDEX `idx_use_type` (`use_type`) COMMENT '使用类型索引';

ALTER TABLE `bl_card_key_logs` 
ADD INDEX `idx_donation_id` (`donation_id`) COMMENT '捐赠记录ID索引';

-- 4. 更新现有数据，将所有旧记录的use_type设置为'redeem'
UPDATE `bl_card_key_logs` SET `use_type` = 'redeem' WHERE `use_type` IS NULL OR `use_type` = '';

-- ============================================
-- 验证修改结果
-- ============================================

SHOW COLUMNS FROM `bl_card_key_logs`;
SHOW INDEX FROM `bl_card_key_logs`;

-- ============================================
-- 完成
-- 新表结构应该包含以下字段:
-- id, card_key_id, user_id, action, use_type, donation_id, 
-- expire_time, ip, user_agent, create_time, remark
-- ============================================

