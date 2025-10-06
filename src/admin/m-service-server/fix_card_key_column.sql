-- ============================================
-- 卡密表字段快速修复脚本
-- 执行时间: 2025-10-06
-- 说明: 修复字段名不匹配问题
-- ============================================

-- 请根据实际数据库名修改
USE blogs;

-- 检查当前表结构
SHOW COLUMNS FROM `bl_card_keys`;

-- ============================================
-- 方案1: 如果表中还有 code 字段，重命名为 card_key
-- ============================================

-- 1. 重命名 code 字段为 card_key
ALTER TABLE `bl_card_keys` 
CHANGE COLUMN `code` `card_key` VARCHAR(32) NOT NULL COMMENT '卡密码，格式: XXXX-XXXX-XXXX-XXXX';

-- 2. 更新唯一索引 (如果存在uk_code索引)
ALTER TABLE `bl_card_keys` DROP INDEX IF EXISTS `uk_code`;
ALTER TABLE `bl_card_keys` ADD UNIQUE KEY `uk_card_key` (`card_key`) COMMENT '卡密码唯一索引';

-- ============================================
-- 方案2: 如果已经有 card_key 字段但没有唯一索引
-- ============================================

-- ALTER TABLE `bl_card_keys` ADD UNIQUE KEY `uk_card_key` (`card_key`) COMMENT '卡密码唯一索引';

-- ============================================
-- 验证修改结果
-- ============================================

SHOW COLUMNS FROM `bl_card_keys`;
SHOW INDEX FROM `bl_card_keys`;

-- ============================================
-- 完成
-- ============================================

