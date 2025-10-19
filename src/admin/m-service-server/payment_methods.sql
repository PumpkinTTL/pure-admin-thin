-- 支付方式表（按链管理版）
-- 支持传统支付方式和加密货币支付
-- 包含FontAwesome图标支持
-- 一个区块链网络对应一个收款地址，可接收该链上所有代币

CREATE TABLE `bl_payment_methods` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '支付方式ID',
  `name` varchar(100) NOT NULL COMMENT '支付方式名称，如：支付宝、波场网络、以太坊网络',
  `type` tinyint(2) NOT NULL DEFAULT '1' COMMENT '支付类型：1=传统支付，2=加密货币，3=数字钱包',
  `icon` varchar(100) DEFAULT NULL COMMENT 'FontAwesome图标类名',
  `network` varchar(50) DEFAULT NULL COMMENT '区块链网络标识：TRX、ETH、BTC、BSC等',
  `wallet_address` varchar(100) DEFAULT NULL COMMENT '收款地址（区块链钱包地址）',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：0=禁用，1=启用',
  `sort_order` int(11) DEFAULT '0' COMMENT '排序权重',
  `is_default` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否为默认支付方式',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_network` (`network`),
  KEY `idx_type_status` (`type`, `status`),
  KEY `idx_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='支付方式表';

-- 插入默认支付方式数据（按链管理）
INSERT INTO `bl_payment_methods` (`name`, `type`, `icon`, `network`, `wallet_address`, `status`, `sort_order`, `is_default`) VALUES
-- 传统支付方式
('支付宝', 1, 'fab fa-alipay', NULL, NULL, 1, 100, 1),
('微信支付', 1, 'fab fa-weixin', NULL, NULL, 1, 90, 0),
('银联支付', 1, 'fas fa-credit-card', NULL, NULL, 1, 80, 0),
('PayPal', 1, 'fab fa-paypal', NULL, NULL, 1, 70, 0),

-- 加密货币（按链管理，一个地址可收多种币）
('波场网络', 2, 'fas fa-coins', 'TRX', 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t', 1, 60, 0),
('以太坊网络', 2, 'fab fa-ethereum', 'ETH', '0xdAC17F958D2ee523a2206206994597C13D831ec7', 1, 50, 0),
('比特币网络', 2, 'fab fa-bitcoin', 'BTC', '1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa', 1, 40, 0),
('币安智能链', 2, 'fas fa-link', 'BSC', '0x55d398326f99059fF775485246999027B3197955', 1, 30, 0);
