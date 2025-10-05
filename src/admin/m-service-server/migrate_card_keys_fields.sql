-- ============================================
-- 卡密表字段重构迁移脚本
-- 执行时间: 2025-10-05
-- 说明: 将旧字段 code, type, available_time 重命名为新字段 card_key, type_id, expire_time
-- ============================================

USE blogs; -- 请根据实际数据库名修改

-- 1. 检查并修改 code 字段为 card_key
ALTER TABLE `bl_card_keys` 
CHANGE COLUMN `code` `card_key` VARCHAR(32) NOT NULL COMMENT '卡密码，格式: XXXX-XXXX-XXXX-XXXX';

-- 2. 添加 type_id 字段（如果type字段还是varchar）
-- 先检查是否有type_id字段，如果没有则添加
ALTER TABLE `bl_card_keys` 
ADD COLUMN `type_id` INT(11) NOT NULL DEFAULT 0 COMMENT '卡密类型ID，关联card_types表' AFTER `card_key`;

-- 3. 将旧的type数据迁移到type_id（可选，如果需要保留数据）
-- UPDATE `bl_card_keys` SET `type_id` = 1 WHERE `type` = '注册邀请码';
-- UPDATE `bl_card_keys` SET `type_id` = 2 WHERE `type` = '商品兑换码';
-- ... 根据实际类型进行映射

-- 4. 删除旧的 type 字段（确认数据迁移完成后）
ALTER TABLE `bl_card_keys` DROP COLUMN `type`;

-- 5. 修改 available_time 为 expire_time
ALTER TABLE `bl_card_keys` 
CHANGE COLUMN `available_time` `expire_time` DATETIME NULL DEFAULT NULL COMMENT '卡密兑换截止时间，NULL表示永久可用';

-- 6. 移除 price 和 membership_duration 字段（这些信息现在存储在card_types表中）
ALTER TABLE `bl_card_keys` DROP COLUMN `price`;
ALTER TABLE `bl_card_keys` DROP COLUMN `membership_duration`;

-- 7. 更新唯一索引
ALTER TABLE `bl_card_keys` DROP INDEX `uk_code`;
ALTER TABLE `bl_card_keys` ADD UNIQUE KEY `uk_card_key` (`card_key`) COMMENT '卡密码唯一索引';

-- 8. 更新type索引
ALTER TABLE `bl_card_keys` DROP INDEX `idx_type`;
ALTER TABLE `bl_card_keys` ADD INDEX `idx_type_id` (`type_id`) COMMENT '类型ID索引';

-- 9. 更新available_time索引
ALTER TABLE `bl_card_keys` DROP INDEX `idx_available_time`;
ALTER TABLE `bl_card_keys` ADD INDEX `idx_expire_time` (`expire_time`) COMMENT '兑换期限索引';

-- 10. 移除不需要的索引
ALTER TABLE `bl_card_keys` DROP INDEX `idx_membership_duration`;

-- ============================================
-- 迁移完成
-- 新表结构应该包含以下字段:
-- id, card_key, type_id, status, create_time, use_time, 
-- user_id, expire_time, remark
-- ============================================

