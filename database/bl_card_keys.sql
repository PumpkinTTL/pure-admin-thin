-- 卡密主表
-- 删除旧表（如果存在）
DROP TABLE IF EXISTS `bl_card_keys`;

-- 创建卡密表
CREATE TABLE `bl_card_keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `card_key` varchar(64) NOT NULL COMMENT '卡密码',
  `type_id` int(11) NOT NULL COMMENT '卡密类型ID，关联bl_card_types.id',
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态：0-未使用，1-已使用，2-已禁用',
  `user_id` int(11) DEFAULT NULL COMMENT '使用者ID',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `use_time` datetime DEFAULT NULL COMMENT '使用时间',
  `available_time` datetime DEFAULT NULL COMMENT '卡密可兑换截止时间（该字段优先级高于类型表的过期时间）',
  `remark` varchar(255) DEFAULT NULL COMMENT '备注',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_card_key` (`card_key`),
  KEY `idx_type_id` (`type_id`),
  KEY `idx_status` (`status`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_create_time` (`create_time`),
  KEY `idx_available_time` (`available_time`),
  CONSTRAINT `fk_card_keys_type` FOREIGN KEY (`type_id`) REFERENCES `bl_card_types` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='卡密表';

-- 插入示例数据（需要先执行bl_card_types.sql创建类型数据）
INSERT INTO `bl_card_keys` (`card_key`, `type_id`, `status`, `remark`, `create_time`) VALUES
('DEMO-1DAY-2025-AAAA', 1, 0, '1天体验卡示例', NOW()),
('DEMO-1WEEK-2025-BBBB', 2, 0, '7天周卡示例', NOW()),
('DEMO-1MONTH-2025-CCCC', 3, 0, '30天月卡示例', NOW()),
('DEMO-YEAR-2025-DDDD', 4, 0, '365天年卡示例', NOW()),
('DEMO-FOREVER-2025-EEEE', 5, 0, '永久会员卡示例', NOW());

