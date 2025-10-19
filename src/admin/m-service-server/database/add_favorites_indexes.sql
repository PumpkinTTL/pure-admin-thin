-- ============================================
-- 收藏表索引优化脚本
-- 功能：添加索引优化查询性能 + 防止重复收藏
-- 注意：如果 idx_target 已存在，请先手动删除
-- ============================================

-- 方案：先查看现有索引
SHOW INDEX FROM `bl_favorites`;

-- 如果 idx_target 已存在，请执行下面这条删除语句：
-- ALTER TABLE `bl_favorites` DROP INDEX `idx_target`;

-- 添加新索引（优化查询性能 + 防止重复收藏）
ALTER TABLE `bl_favorites` ADD UNIQUE INDEX `uk_user_target` (`user_id`, `target_type`, `target_id`);

-- 验证索引
SHOW INDEX FROM `bl_favorites`;
