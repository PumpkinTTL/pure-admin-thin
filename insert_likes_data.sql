-- 点赞表测试数据插入脚本
USE blogs;

INSERT INTO bl_likes (user_id, target_type, target_id, create_time, update_time) VALUES
(1001, 'article', 11355, NOW(), NOW()),
(1002, 'article', 11355, NOW(), NOW()),
(1003, 'article', 11355, NOW(), NOW()),
(1004, 'article', 11355, NOW(), NOW()),
(1005, 'comment', 6, NOW(), NOW()),
(1006, 'comment', 6, NOW(), NOW()),
(1007, 'comment', 7, NOW(), NOW()),
(1008, 'comment', 7, NOW(), NOW()),
(1009, 'comment', 9, NOW(), NOW()),
(1010, 'comment', 9, NOW(), NOW());

SELECT '点赞数据插入完成！' as message;
SELECT COUNT(*) as total_likes FROM bl_likes;