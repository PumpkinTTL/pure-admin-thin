-- 评论表字段重构：将 article_id 改为 target_id，添加 target_type 字段
-- 这样评论系统可以支持文章、商品、用户等多种目标类型
USE blogs;

-- 1. 添加新字段
ALTER TABLE bl_comments ADD COLUMN target_id INT(11) NOT NULL DEFAULT 0 COMMENT '目标ID（文章、商品等）';
ALTER TABLE bl_comments ADD COLUMN target_type VARCHAR(50) NOT NULL DEFAULT 'article' COMMENT '目标类型（article、product、user等）';

-- 2. 将现有的 article_id 数据迁移到 target_id，并设置 target_type 为 'article'
UPDATE bl_comments SET target_id = article_id, target_type = 'article' WHERE article_id > 0;

-- 3. 添加索引提升性能
ALTER TABLE bl_comments ADD INDEX idx_target (target_type, target_id);
ALTER TABLE bl_comments ADD INDEX idx_target_parent (target_type, target_id, parent_id);

-- 4. 验证数据迁移是否正确
SELECT 
    '=== 验证数据迁移结果 ===' as step,
    COUNT(*) as total_comments,
    COUNT(CASE WHEN target_id > 0 AND target_type = 'article' THEN 1 END) as migrated_comments,
    COUNT(CASE WHEN article_id > 0 THEN 1 END) as old_article_comments
FROM bl_comments 
WHERE delete_time IS NULL;

-- 5. 显示迁移后的数据示例
SELECT 
    id, 
    article_id as old_article_id,
    target_id as new_target_id,
    target_type as new_target_type,
    content,
    create_time
FROM bl_comments 
WHERE delete_time IS NULL 
LIMIT 5;

-- 注意：执行完成后，确认数据正确后可以删除 article_id 字段：
-- ALTER TABLE bl_comments DROP COLUMN article_id;

-- 可选：如果想要立即删除旧字段，取消下面注释
-- ALTER TABLE bl_comments DROP COLUMN article_id;

-- 检查最终表结构
DESCRIBE bl_comments;