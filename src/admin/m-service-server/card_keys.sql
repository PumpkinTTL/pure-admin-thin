 -- ============================================
-- 卡密管理系统数据库脚本
-- 表前缀: bl_
-- 创建时间: 2025-10-01
-- 说明: 包含卡密主表和使用记录表
-- ============================================

-- ============================================
-- 1. 创建卡密主表 bl_card_keys
-- ============================================
DROP TABLE IF EXISTS `bl_card_keys`;
CREATE TABLE `bl_card_keys` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `code` varchar(20) NOT NULL COMMENT '卡密码，格式: XXXX-XXXX-XXXX-XXXX',
  `type` varchar(50) NOT NULL COMMENT '卡密类型(中文)，如: 注册邀请码、商品兑换码',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态: 0=未使用, 1=已使用, 2=已禁用',
  `price` decimal(10,2) DEFAULT NULL COMMENT '价格(可选)，适用于商品兑换码',
  `membership_duration` int(11) NOT NULL DEFAULT 0 COMMENT '兑换后获得的会员时长(分钟)，0表示永久会员',
  `available_time` datetime DEFAULT NULL COMMENT '卡密可兑换截止时间，为NULL表示永久可用',
  `create_time` datetime NOT NULL COMMENT '创建时间',
  `use_time` datetime DEFAULT NULL COMMENT '使用时间',
  `user_id` bigint(20) DEFAULT NULL COMMENT '使用者ID，关联用户表',
  `remark` varchar(255) DEFAULT NULL COMMENT '备注信息',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`) COMMENT '卡密码唯一索引',
  KEY `idx_type` (`type`) COMMENT '类型索引，用于按类型筛选',
  KEY `idx_status` (`status`) COMMENT '状态索引，用于按状态筛选',
  KEY `idx_user_id` (`user_id`) COMMENT '使用者索引，用于查询用户使用记录',
  KEY `idx_create_time` (`create_time`) COMMENT '创建时间索引，用于时间范围查询',
  KEY `idx_membership_duration` (`membership_duration`) COMMENT '会员时长索引，用于按会员时长筛选',
  KEY `idx_available_time` (`available_time`) COMMENT '卡密可用期限索引，用于查询过期卡密'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='卡密主表';

-- ============================================
-- 2. 创建卡密使用记录表 bl_card_key_logs
-- ============================================
DROP TABLE IF EXISTS `bl_card_key_logs`;
CREATE TABLE `bl_card_key_logs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `card_key_id` bigint(20) NOT NULL COMMENT '卡密ID，关联bl_card_keys.id',
  `user_id` bigint(20) NOT NULL COMMENT '操作用户ID',
  `action` varchar(50) NOT NULL COMMENT '操作类型: 使用、验证、禁用等',
  `expire_time` datetime DEFAULT NULL COMMENT '会员到期时间，使用时根据membership_duration计算',
  `ip` varchar(50) DEFAULT NULL COMMENT '操作IP地址',
  `user_agent` varchar(255) DEFAULT NULL COMMENT '用户代理信息',
  `create_time` datetime NOT NULL COMMENT '操作时间',
  `remark` varchar(255) DEFAULT NULL COMMENT '备注信息',
  PRIMARY KEY (`id`),
  KEY `idx_card_key_id` (`card_key_id`) COMMENT '卡密ID索引，用于查询卡密使用记录',
  KEY `idx_user_id` (`user_id`) COMMENT '用户ID索引，用于查询用户操作记录',
  KEY `idx_create_time` (`create_time`) COMMENT '操作时间索引，用于时间范围查询',
  KEY `idx_expire_time` (`expire_time`) COMMENT '过期时间索引，用于查询即将过期的卡密'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='卡密使用记录表';

-- ============================================
-- 3. 插入测试数据（可选）
-- ============================================

-- 插入测试卡密数据
INSERT INTO `bl_card_keys` (`code`, `type`, `status`, `price`, `membership_duration`, `available_time`, `create_time`, `use_time`, `user_id`, `remark`) VALUES
('A8SU-IJGS-82LO-DDTP', '注册邀请码', 0, NULL, 0, NULL, NOW(), NULL, NULL, '测试用永久会员注册码'),
('B9TV-JKHT-93MP-EEUQ', '商品兑换码', 0, 99.00, 43200, DATE_ADD(NOW(), INTERVAL 90 DAY), NOW(), NULL, NULL, '测试用30天会员兑换码，90天内可兑换'),
('C0UW-KLIU-04NQ-FFVR', 'VIP兑换码', 1, 199.00, 10080, NULL, NOW(), NOW(), 1, '测试用7天VIP码-已使用'),
('D1VX-LMJV-15OR-GGWS', '积分兑换码', 2, NULL, 1440, DATE_ADD(NOW(), INTERVAL -1 DAY), NOW(), NULL, NULL, '测试用1天积分码-已禁用，兑换期已过'),
('E2WY-MNKW-26PS-HHXT', '注册邀请码', 0, NULL, 0, NULL, NOW(), NULL, NULL, '测试用永久会员注册码2');

-- 插入测试日志数据
INSERT INTO `bl_card_key_logs` (`card_key_id`, `user_id`, `action`, `expire_time`, `ip`, `user_agent`, `create_time`, `remark`) VALUES
(3, 1, '使用', DATE_ADD(NOW(), INTERVAL 7 DAY), '127.0.0.1', 'Mozilla/5.0', NOW(), 'VIP兑换码使用记录'),
(4, 1, '禁用', NULL, '127.0.0.1', 'Mozilla/5.0', NOW(), '积分兑换码被管理员禁用');

-- ============================================
-- 4. 创建视图（可选）- 方便查询卡密详情
-- ============================================
DROP VIEW IF EXISTS `v_card_keys_detail`;
CREATE VIEW `v_card_keys_detail` AS
SELECT 
  ck.id,
  ck.code,
  ck.type,
  ck.status,
  ck.price,
  ck.membership_duration,
  ck.available_time,
  ck.create_time,
  ck.use_time,
  ck.user_id,
  ck.remark,
  u.username,
  u.nickname,
  CASE 
    WHEN ck.status = 0 THEN '未使用'
    WHEN ck.status = 1 THEN '已使用'
    WHEN ck.status = 2 THEN '已禁用'
    ELSE '未知'
  END AS status_text,
  CASE 
    WHEN ck.membership_duration = 0 THEN '永久会员'
    WHEN ck.membership_duration < 60 THEN CONCAT(ck.membership_duration, '分钟')
    WHEN ck.membership_duration < 1440 THEN CONCAT(FLOOR(ck.membership_duration / 60), '小时')
    ELSE CONCAT(FLOOR(ck.membership_duration / 1440), '天')
  END AS membership_duration_text,
  CASE 
    WHEN ck.status = 1 THEN 0  -- 已使用的不算过期
    WHEN ck.available_time IS NULL THEN 0  -- 没有设置兑换期限
    WHEN ck.available_time < NOW() THEN 1  -- 超过兑换期限
    ELSE 0
  END AS is_expired,
  CASE 
    WHEN ck.status != 1 OR ck.membership_duration = 0 THEN NULL
    ELSE DATE_ADD(ck.use_time, INTERVAL ck.membership_duration MINUTE)
  END AS member_expire_time
FROM bl_card_keys ck
LEFT JOIN users u ON ck.user_id = u.id;

-- ============================================
-- 5. 创建存储过程（可选）- 批量生成卡密
-- ============================================
DROP PROCEDURE IF EXISTS `sp_batch_generate_card_keys`;
DELIMITER $$
CREATE PROCEDURE `sp_batch_generate_card_keys`(
  IN p_type VARCHAR(50),
  IN p_count INT,
  IN p_price DECIMAL(10,2),
  IN p_membership_duration INT,
  IN p_available_time DATETIME,
  IN p_remark VARCHAR(255)
)
BEGIN
  DECLARE i INT DEFAULT 0;
  DECLARE v_code VARCHAR(20);
  
  WHILE i < p_count DO
    -- 生成随机卡密码（这里只是示例，实际应该由PHP生成）
    SET v_code = CONCAT(
      SUBSTRING(MD5(RAND()), 1, 4), '-',
      SUBSTRING(MD5(RAND()), 1, 4), '-',
      SUBSTRING(MD5(RAND()), 1, 4), '-',
      SUBSTRING(MD5(RAND()), 1, 4)
    );
    
    -- 插入卡密
    INSERT INTO bl_card_keys (code, type, status, price, membership_duration, available_time, create_time, remark)
    VALUES (v_code, p_type, 0, p_price, p_membership_duration, p_available_time, NOW(), p_remark);
    
    SET i = i + 1;
  END WHILE;
END$$
DELIMITER ;

-- ============================================
-- 6. 数据库说明
-- ============================================
/*
表设计说明:
1. bl_card_keys - 卡密主表
   - 使用唯一索引确保卡密码不重复
   - status字段控制卡密状态
   - membership_duration: 兑换后获得的会员时长(分钟)，0表示永久会员
   - available_time: 卡密可兑换截止时间，NULL表示永久可用
   - price字段可为NULL，适配不需要价格的卡密类型

2. bl_card_key_logs - 使用记录表
   - 记录所有卡密操作历史
   - expire_time: 会员到期时间，在使用时计算 = use_time + membership_duration
   - 支持追溯完整的卡密生命周期

索引优化:
- 所有常用查询字段都建立了索引
- 使用复合索引可进一步优化特定查询

视图说明:
- v_card_keys_detail 提供了卡密的详细信息视图
- 包含状态文本、有效期文本、是否过期等计算字段

存储过程说明:
- sp_batch_generate_card_keys 用于批量生成卡密
- 实际项目中建议由PHP代码生成，确保算法安全性
*/

-- ============================================
-- 执行完成
-- ============================================

