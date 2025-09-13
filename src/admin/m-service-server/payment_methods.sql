-- 支付方式表（精简版）
-- 支持传统支付方式和加密货币支付
-- 包含FontAwesome图标支持

CREATE TABLE `bl_payment_methods` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '支付方式ID',
  `name` varchar(100) NOT NULL COMMENT '支付方式名称',
  `code` varchar(50) NOT NULL COMMENT '支付方式代码，唯一标识',
  `type` tinyint(2) NOT NULL DEFAULT '1' COMMENT '支付类型：1=传统支付，2=加密货币，3=数字钱包',
  `icon` varchar(100) DEFAULT NULL COMMENT 'FontAwesome图标类名',
  `currency_code` varchar(10) DEFAULT 'CNY' COMMENT '货币代码：CNY,USD,BTC,ETH等',
  `currency_symbol` varchar(10) DEFAULT '¥' COMMENT '货币符号',
  `is_crypto` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否为加密货币：0=否，1=是',
  `network` varchar(50) DEFAULT NULL COMMENT '区块链网络（仅加密货币）',
  `contract_address` varchar(100) DEFAULT NULL COMMENT '合约地址（代币）',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：0=禁用，1=启用',
  `sort_order` int(11) DEFAULT '0' COMMENT '排序权重',
  `is_default` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否为默认支付方式',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_type_status` (`type`, `status`),
  KEY `idx_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='支付方式表';

-- 插入默认支付方式数据
INSERT INTO `bl_payment_methods` (`name`, `code`, `type`, `icon`, `currency_code`, `currency_symbol`, `is_crypto`, `network`, `contract_address`, `status`, `sort_order`, `is_default`) VALUES
('支付宝', 'alipay', 1, 'fab fa-alipay', 'CNY', '¥', 0, NULL, NULL, 1, 100, 1),
('微信支付', 'wechat', 1, 'fab fa-weixin', 'CNY', '¥', 0, NULL, NULL, 1, 90, 0),
('银联支付', 'unionpay', 1, 'fas fa-credit-card', 'CNY', '¥', 0, NULL, NULL, 1, 80, 0),
('PayPal', 'paypal', 1, 'fab fa-paypal', 'USD', '$', 0, NULL, NULL, 1, 70, 0),
('比特币', 'bitcoin', 2, 'fab fa-bitcoin', 'BTC', '₿', 1, 'BTC', NULL, 1, 60, 0),
('以太坊', 'ethereum', 2, 'fab fa-ethereum', 'ETH', 'Ξ', 1, 'ETH', NULL, 1, 50, 0),
('USDT-TRC20', 'usdt_trc20', 2, 'fas fa-coins', 'USDT', '$', 1, 'TRX', 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t', 1, 40, 0),
('USDT-ERC20', 'usdt_erc20', 2, 'fas fa-coins', 'USDT', '$', 1, 'ETH', '0xdAC17F958D2ee523a2206206994597C13D831ec7', 1, 30, 0);
