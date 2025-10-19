-- ============================================
-- 收藏表多态字段迁移脚本
-- 功能：将 article_id 改为 target_id + target_type
-- 执行前请备份数据库！
-- ============================================

-- 1. 添加新字段
ALTER TABLE `bl_favorites` 
ADD COLUMN `target_id` INT(11) NOT NULL COMMENT '目标ID（通用）' AFTER `user_id`,
ADD COLUMN `target_type` VARCHAR(20) NOT NULL DEFAULT 'article' COMMENT '目标类型：article-文章, product-商品, video-视频, resource-资源, comment-评论' AFTER `target_id`;

-- 2. 迁移现有数据：将 article_id 的值复制到 target_id
UPDATE `bl_favorites` SET `target_id` = `article_id`, `target_type` = 'article' WHERE `article_id` IS NOT NULL;

-- 3. 添加索引（优化查询性能 + 防止重复收藏）
ALTER TABLE `bl_favorites` 
ADD INDEX `idx_target` (`target_type`, `target_id`),
ADD UNIQUE INDEX `uk_user_target` (`user_id`, `target_type`, `target_id`);

-- 4. 删除旧字段（可选，如果确认数据迁移无误后执行）
-- ALTER TABLE `bl_favorites` DROP COLUMN `article_id`;

-- ============================================
-- 验证迁移结果
-- ============================================

-- 查看表结构
DESC `bl_favorites`;

-- 查看数据示例
SELECT * FROM `bl_favorites` LIMIT 10;

-- 统计各类型收藏数量
SELECT `target_type`, COUNT(*) as count FROM `bl_favorites` GROUP BY `target_type`;
