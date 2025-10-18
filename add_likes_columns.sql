-- 为文章表和评论表添加点赞数字段
USE blogs;

-- 检查并为文章表添加点赞数字段
SET @table_name = 'bl_article';
SET @column_name = 'likes';

SELECT COUNT(*) INTO @column_exists
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = @table_name 
  AND COLUMN_NAME = @column_name;

SET @sql = IF(@column_exists = 0, 
    CONCAT('ALTER TABLE ', @table_name, ' ADD COLUMN ', @column_name, ' int(11) NOT NULL DEFAULT 0 COMMENT ''点赞数'''),
    'SELECT ''Column already exists'' as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 检查并为评论表添加点赞数字段
SET @table_name = 'bl_comments';
SET @column_name = 'likes';

SELECT COUNT(*) INTO @column_exists
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = @table_name 
  AND COLUMN_NAME = @column_name;

SET @sql = IF(@column_exists = 0, 
    CONCAT('ALTER TABLE ', @table_name, ' ADD COLUMN ', @column_name, ' int(11) NOT NULL DEFAULT 0 COMMENT ''点赞数'''),
    'SELECT ''Column already exists'' as message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 更新现有数据的点赞数（可选）
-- UPDATE bl_article SET likes = (
--     SELECT COUNT(*) FROM bl_likes 
--     WHERE target_type = 'article' 
--       AND target_id = bl_article.id 
--       AND delete_time IS NULL
-- );

-- UPDATE bl_comments SET likes = (
--     SELECT COUNT(*) FROM bl_likes 
--     WHERE target_type = 'comment' 
--       AND target_id = bl_comments.id 
--       AND delete_time IS NULL
-- );

SELECT '字段添加完成！' as message;