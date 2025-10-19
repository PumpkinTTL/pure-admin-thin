-- ================================================
-- 支付方式表优化迁移脚本
-- 从按币种管理改为按链管理
-- ================================================

-- 第一步：备份原有数据
CREATE TABLE `bl_payment_methods_backup` LIKE `bl_payment_methods`;
INSERT INTO `bl_payment_methods_backup` SELECT * FROM `bl_payment_methods`;

-- 第二步：删除冗余字段
ALTER TABLE `bl_payment_methods` 
  DROP COLUMN `code`,
  DROP COLUMN `currency_code`,
  DROP COLUMN `currency_symbol`;

-- 第三步：重命名字段（将 contract_address 改为 wallet_address）
ALTER TABLE `bl_payment_methods` 
  CHANGE COLUMN `contract_address` `wallet_address` varchar(100) DEFAULT NULL COMMENT '收款地址（区块链钱包地址）';

-- 第四步：修改索引（删除原来的 uk_code 唯一索引）
ALTER TABLE `bl_payment_methods` DROP INDEX `uk_code`;

-- 第五步：添加新的索引
ALTER TABLE `bl_payment_methods` 
  ADD UNIQUE KEY `uk_network` (`network`),
  ADD KEY `idx_type_network` (`type`, `network`);

-- 第六步：清空旧数据（可选，根据实际情况决定）
TRUNCATE TABLE `bl_payment_methods`;

-- 第七步：插入新的示例数据（按链管理）
INSERT INTO `bl_payment_methods` (`name`, `type`, `icon`, `network`, `wallet_address`, `status`, `sort_order`, `is_default`) VALUES
-- 传统支付方式
('支付宝', 1, 'fab fa-alipay', NULL, NULL, 1, 100, 1),
('微信支付', 1, 'fab fa-weixin', NULL, NULL, 1, 90, 0),
('银联支付', 1, 'fas fa-credit-card', NULL, NULL, 1, 80, 0),
('PayPal', 1, 'fab fa-paypal', NULL, NULL, 1, 70, 0),

-- 加密货币（按链管理）
('波场网络', 2, 'fas fa-coins', 'TRX', 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t', 1, 60, 0),
('以太坊网络', 2, 'fab fa-ethereum', 'ETH', '0xdAC17F958D2ee523a2206206994597C13D831ec7', 1, 50, 0),
('比特币网络', 2, 'fab fa-bitcoin', 'BTC', '1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa', 1, 40, 0),
('币安智能链', 2, 'fas fa-link', 'BSC', '0x55d398326f99059fF775485246999027B3197955', 1, 30, 0);

-- ================================================
-- 说明
-- ================================================
-- 1. 数据已备份到 bl_payment_methods_backup 表
-- 2. 删除了 code、currency_code、currency_symbol 字段
-- 3. contract_address 改名为 wallet_address
-- 4. 删除了 uk_code 索引，新增了 uk_network 唯一索引
-- 5. 一个链对应一个收款地址，可以接收该链上所有代币
-- 6. 如果需要恢复旧数据，可以从备份表恢复

-- 如果确认迁移成功，可以删除备份表：
-- DROP TABLE `bl_payment_methods_backup`;
