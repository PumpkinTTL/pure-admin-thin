-- 检查 bl_card_key_logs 表结构
SHOW COLUMNS FROM `bl_card_key_logs`;

-- 查看最近的卡密使用记录
SELECT 
    id,
    card_key_id,
    user_id,
    action,
    use_type,
    related_id,
    related_type,
    create_time,
    remark
FROM `bl_card_key_logs`
ORDER BY create_time DESC
LIMIT 10;

-- 统计各种使用类型的数量
SELECT 
    use_type,
    COUNT(*) as count
FROM `bl_card_key_logs`
GROUP BY use_type;

