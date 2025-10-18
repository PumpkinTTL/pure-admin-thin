-- ============================================
-- 点赞模块集成测试脚本
-- ============================================

USE blogs;

-- 1. 测试基础数据查询
SELECT '=== 1. 检查表结构 ===' as test_step;
DESCRIBE bl_likes;

-- 2. 测试文章点赞统计
SELECT '=== 2. 文章点赞统计测试 ===' as test_step;
-- 查询文章及其点赞数
SELECT 
    a.id,
    a.title,
    COUNT(l.id) as likes_count
FROM bl_article a 
LEFT JOIN bl_likes l ON l.target_type = 'article' AND l.target_id = a.id
WHERE a.id = 11355
GROUP BY a.id, a.title;

-- 3. 测试评论点赞统计  
SELECT '=== 3. 评论点赞统计测试 ===' as test_step;
-- 查询评论及其点赞数
SELECT 
    c.id,
    c.content,
    COUNT(l.id) as likes_count
FROM bl_comments c 
LEFT JOIN bl_likes l ON l.target_type = 'comment' AND l.target_id = c.id
WHERE c.id IN (6, 7, 9)
GROUP BY c.id, c.content;

-- 4. 测试点赞记录查询
SELECT '=== 4. 点赞记录查询测试 ===' as test_step;
SELECT 
    l.id,
    l.user_id,
    l.target_type,
    l.target_id,
    l.create_time,
    CASE 
        WHEN l.target_type = 'article' THEN (SELECT title FROM bl_article WHERE id = l.target_id)
        WHEN l.target_type = 'comment' THEN (SELECT CONCAT('评论: ', LEFT(content, 20), '...') FROM bl_comments WHERE id = l.target_id)
    END as target_info
FROM bl_likes l
ORDER BY l.create_time DESC
LIMIT 10;

-- 5. 测试用户点赞行为统计
SELECT '=== 5. 用户点赞行为统计 ===' as test_step;
SELECT 
    user_id,
    target_type,
    COUNT(*) as like_count,
    MIN(create_time) as first_like,
    MAX(create_time) as last_like
FROM bl_likes 
GROUP BY user_id, target_type
ORDER BY user_id, target_type;

-- 6. 验证约束和索引
SELECT '=== 6. 验证表索引 ===' as test_step;
SHOW INDEX FROM bl_likes;

-- 7. 性能测试查询
SELECT '=== 7. 性能测试查询 ===' as test_step;
-- 查询最受欢迎的文章（按点赞数排序）
SELECT 
    a.id,
    a.title,
    COUNT(l.id) as likes_count
FROM bl_article a 
LEFT JOIN bl_likes l ON l.target_type = 'article' AND l.target_id = a.id
GROUP BY a.id, a.title
ORDER BY likes_count DESC
LIMIT 10;

-- 8. 数据完整性检查
SELECT '=== 8. 数据完整性检查 ===' as test_step;
-- 检查是否有无效的target_id
SELECT 
    '无效文章引用' as issue_type,
    COUNT(*) as count
FROM bl_likes l 
WHERE l.target_type = 'article' 
AND NOT EXISTS (SELECT 1 FROM bl_article WHERE id = l.target_id)

UNION ALL

SELECT 
    '无效评论引用' as issue_type,
    COUNT(*) as count
FROM bl_likes l 
WHERE l.target_type = 'comment' 
AND NOT EXISTS (SELECT 1 FROM bl_comments WHERE id = l.target_id);

SELECT '=== 点赞模块集成测试完成 ===' as test_result;