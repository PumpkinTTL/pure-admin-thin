-- 测试评论点赞统计
USE blogs;

-- 1. 查看当前评论和点赞数据
SELECT '=== 1. 当前评论数据 ===' as step;
SELECT id, content, user_id, article_id, parent_id, create_time FROM bl_comments WHERE id IN (6, 7, 9);

SELECT '=== 2. 当前点赞数据 ===' as step;
SELECT id, user_id, target_type, target_id, create_time FROM bl_likes WHERE target_type = 'comment';

-- 3. 测试评论点赞统计查询（模拟withCount效果）
SELECT '=== 3. 评论点赞统计测试 ===' as step;
SELECT 
    c.id,
    c.content,
    c.user_id,
    (SELECT COUNT(*) FROM bl_likes l WHERE l.target_type = 'comment' AND l.target_id = c.id) as likes_count
FROM bl_comments c 
WHERE c.id IN (6, 7, 9)
ORDER BY c.id;

-- 4. 验证关联关系是否正确
SELECT '=== 4. 验证关联关系 ===' as step;
SELECT 
    l.id as like_id,
    l.user_id as liker_id,
    l.target_id as comment_id,
    c.content as comment_content,
    c.user_id as comment_author_id
FROM bl_likes l
LEFT JOIN bl_comments c ON l.target_id = c.id AND l.target_type = 'comment'
WHERE l.target_type = 'comment'
ORDER BY l.create_time DESC;