-- ============================================
-- 捐赠记录表 bl_donations
-- 创建时间: 2025-01-28
-- 说明: 支持多种捐赠渠道（微信、支付宝、加密货币、卡密兑换）
-- ============================================

DROP TABLE IF EXISTS `bl_donations`;
CREATE TABLE `bl_donations` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `donation_no` varchar(64) NOT NULL COMMENT '捐赠单号，格式: DON20250101XXXX',
  `user_id` bigint(20) DEFAULT NULL COMMENT '捐赠用户ID，关联bl_users.id，匿名捐赠可为NULL',
  `donor_name` varchar(100) DEFAULT NULL COMMENT '捐赠者姓名（可匿名）',
  `donor_contact` varchar(100) DEFAULT NULL COMMENT '捐赠者联系方式（邮箱/手机）',
  `amount` decimal(10,2) DEFAULT NULL COMMENT '捐赠金额（微信/支付宝/加密货币）',
  `channel` varchar(50) NOT NULL COMMENT '捐赠渠道: wechat=微信, alipay=支付宝, crypto=加密货币, cardkey=卡密兑换',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态: 0=待确认, 1=已确认, 2=已完成, 3=已取消',
  
  -- 微信/支付宝支付字段
  `order_no` varchar(100) DEFAULT NULL COMMENT '第三方订单号（微信/支付宝）',
  `transaction_id` varchar(100) DEFAULT NULL COMMENT '第三方交易流水号',
  `payment_time` datetime DEFAULT NULL COMMENT '支付时间',
  
  -- 加密货币支付字段
  `crypto_type` varchar(50) DEFAULT NULL COMMENT '加密货币类型: USDT, BTC, ETH等',
  `crypto_network` varchar(50) DEFAULT NULL COMMENT '区块链网络: TRC20, ERC20, BTC等',
  `transaction_hash` varchar(255) DEFAULT NULL COMMENT '区块链交易哈希值',
  `from_address` varchar(255) DEFAULT NULL COMMENT '发送地址',
  `to_address` varchar(255) DEFAULT NULL COMMENT '接收地址',
  `confirmation_count` int(11) DEFAULT 0 COMMENT '区块确认数',
  
  -- 卡密兑换字段
  `card_key_id` bigint(20) DEFAULT NULL COMMENT '卡密ID，关联bl_card_keys.id',
  `card_key_code` varchar(64) DEFAULT NULL COMMENT '卡密码（冗余字段，便于查询）',
  `card_key_value` decimal(10,2) DEFAULT NULL COMMENT '卡密等值金额',
  
  -- 通用字段
  `remark` text DEFAULT NULL COMMENT '备注信息',
  `admin_remark` text DEFAULT NULL COMMENT '管理员备注',
  `is_anonymous` tinyint(1) DEFAULT 0 COMMENT '是否匿名捐赠: 0=否, 1=是',
  `is_public` tinyint(1) DEFAULT 1 COMMENT '是否公开展示: 0=否, 1=是',
  `create_time` datetime NOT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  `confirm_time` datetime DEFAULT NULL COMMENT '确认时间',
  `delete_time` datetime DEFAULT NULL COMMENT '软删除时间',
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_donation_no` (`donation_no`) COMMENT '捐赠单号唯一索引',
  KEY `idx_user_id` (`user_id`) COMMENT '用户ID索引',
  KEY `idx_channel` (`channel`) COMMENT '捐赠渠道索引',
  KEY `idx_status` (`status`) COMMENT '状态索引',
  KEY `idx_order_no` (`order_no`) COMMENT '订单号索引',
  KEY `idx_transaction_hash` (`transaction_hash`) COMMENT '交易哈希索引',
  KEY `idx_card_key_id` (`card_key_id`) COMMENT '卡密ID索引',
  KEY `idx_create_time` (`create_time`) COMMENT '创建时间索引',
  KEY `idx_payment_time` (`payment_time`) COMMENT '支付时间索引'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='捐赠记录表';

-- ============================================
-- 插入测试数据
-- ============================================
INSERT INTO `bl_donations` (`donation_no`, `user_id`, `donor_name`, `donor_contact`, `amount`, `channel`, `status`, `order_no`, `transaction_id`, `payment_time`, `remark`, `is_anonymous`, `is_public`, `create_time`, `update_time`, `confirm_time`) VALUES
('DON202501010001', 1, '张三', 'zhangsan@example.com', 100.00, 'wechat', 2, 'WX20250101123456', 'WX_TXN_123456789', '2025-01-01 12:34:56', '感谢网站提供的优质内容', 0, 1, '2025-01-01 12:30:00', '2025-01-01 12:35:00', '2025-01-01 12:35:00'),
('DON202501010002', 2, '李四', 'lisi@example.com', 200.00, 'alipay', 2, 'ALI20250101234567', 'ALI_TXN_987654321', '2025-01-01 14:20:30', '支持网站发展', 0, 1, '2025-01-01 14:15:00', '2025-01-01 14:21:00', '2025-01-01 14:21:00'),
('DON202501010003', 3, '匿名用户', NULL, 50.00, 'crypto', 1, NULL, NULL, NULL, NULL, 1, 1, '2025-01-01 16:00:00', '2025-01-01 16:10:00', '2025-01-01 16:10:00'),
('DON202501010004', NULL, '匿名捐赠', NULL, NULL, 'cardkey', 2, NULL, NULL, NULL, '通过卡密兑换捐赠', 1, 1, '2025-01-01 18:00:00', '2025-01-01 18:05:00', '2025-01-01 18:05:00');

-- 更新第3条记录的加密货币信息
UPDATE `bl_donations` SET 
  `crypto_type` = 'USDT',
  `crypto_network` = 'TRC20',
  `transaction_hash` = '0x1234567890abcdef1234567890abcdef1234567890abcdef1234567890abcdef',
  `from_address` = 'TXxxxxxxxxxxxxxxxxxxxxxxxxxxx',
  `to_address` = 'TYyyyyyyyyyyyyyyyyyyyyyyyyyyyy',
  `confirmation_count` = 20,
  `payment_time` = '2025-01-01 16:08:00'
WHERE `donation_no` = 'DON202501010003';

-- 更新第4条记录的卡密信息
UPDATE `bl_donations` SET 
  `card_key_id` = 1,
  `card_key_code` = 'A8SU-IJGS-82LO-DDTP',
  `card_key_value` = 99.00,
  `payment_time` = '2025-01-01 18:03:00'
WHERE `donation_no` = 'DON202501010004';

-- ============================================
-- 表设计说明
-- ============================================
/*
1. 多渠道支持
   - wechat: 微信支付，需要 order_no, transaction_id, amount
   - alipay: 支付宝，需要 order_no, transaction_id, amount
   - crypto: 加密货币，需要 crypto_type, crypto_network, transaction_hash, amount
   - cardkey: 卡密兑换，需要 card_key_id, card_key_code, card_key_value

2. 灵活字段设计
   - 根据不同渠道，相关字段可为NULL
   - 卡密兑换时，amount可为空，使用card_key_value代替

3. 状态管理
   - 0: 待确认 - 刚创建的捐赠记录
   - 1: 已确认 - 管理员确认收到捐赠
   - 2: 已完成 - 捐赠流程完成
   - 3: 已取消 - 捐赠被取消

4. 匿名和公开控制
   - is_anonymous: 是否匿名捐赠
   - is_public: 是否在前台公开展示

5. 软删除支持
   - delete_time: 软删除时间戳
   - 支持数据恢复功能

6. 索引优化
   - 所有常用查询字段都建立了索引
   - 支持高效的多条件筛选和排序
*/

