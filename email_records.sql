-- =============================================
-- 邮件记录模块 SQL 脚本
-- 表前缀: bl_
-- 创建时间: 2025-10-25
-- 说明: 双表设计，统一时间字段命名为 _time 结尾
-- =============================================

-- 邮件记录主表
DROP TABLE IF EXISTS `bl_email_records`;
CREATE TABLE `bl_email_records` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '邮件记录ID',
  `notice_id` int(11) DEFAULT NULL COMMENT '关联公告ID(可为空)',
  `sender_id` int(11) NOT NULL COMMENT '发送者用户ID',
  `title` varchar(200) NOT NULL DEFAULT '' COMMENT '邮件标题',
  `content` text NOT NULL COMMENT '邮件内容',
  `receiver_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '接收方式: 1-全部用户, 2-指定多个用户, 3-单个用户, 4-指定邮箱',
  `total_count` int(11) NOT NULL DEFAULT '0' COMMENT '总发送数量',
  `success_count` int(11) NOT NULL DEFAULT '0' COMMENT '成功发送数量',
  `failed_count` int(11) NOT NULL DEFAULT '0' COMMENT '失败发送数量',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '发送状态: 0-待发送, 1-发送中, 2-发送完成, 3-部分失败, 4-全部失败',
  `send_time` datetime DEFAULT NULL COMMENT '发送时间',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `delete_time` datetime DEFAULT NULL COMMENT '删除时间(软删除)',
  PRIMARY KEY (`id`),
  KEY `idx_notice_id` (`notice_id`),
  KEY `idx_sender_id` (`sender_id`),
  KEY `idx_status` (`status`),
  KEY `idx_create_time` (`create_time`),
  KEY `idx_delete_time` (`delete_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='邮件发送记录表';

-- 邮件接收者详情表
DROP TABLE IF EXISTS `bl_email_receivers`;
CREATE TABLE `bl_email_receivers` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '接收者记录ID',
  `record_id` int(11) unsigned NOT NULL COMMENT '邮件记录ID',
  `user_id` int(11) DEFAULT NULL COMMENT '用户ID(为空表示外部邮箱)',
  `email` varchar(200) NOT NULL DEFAULT '' COMMENT '邮箱地址',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '发送状态: 0-待发送, 1-发送成功, 2-发送失败',
  `error_msg` varchar(500) DEFAULT '' COMMENT '错误信息',
  `send_time` datetime DEFAULT NULL COMMENT '发送时间',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_record_id` (`record_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='邮件接收者详情表';

-- 插入测试数据(可选)
INSERT INTO `bl_email_records` (`id`, `notice_id`, `sender_id`, `title`, `content`, `receiver_type`, `total_count`, `success_count`, `failed_count`, `status`, `send_time`, `create_time`) VALUES
(1, 1, 1, '【系统通知】系统升级公告', '尊敬的用户，系统将于今晚进行升级维护...', 1, 150, 148, 2, 2, '2025-10-24 10:30:00', '2025-10-24 10:25:00'),
(2, NULL, 1, '【重要通知】账号安全提醒', '请注意保护您的账号安全...', 2, 25, 25, 0, 2, '2025-10-23 15:20:00', '2025-10-23 15:18:00'),
(3, 2, 1, '【活动通知】双十一活动开始', '双十一活动火热进行中...', 4, 10, 8, 2, 3, '2025-10-22 09:00:00', '2025-10-22 08:55:00');

INSERT INTO `bl_email_receivers` (`record_id`, `user_id`, `email`, `status`, `send_time`) VALUES
(1, 2, 'user001@example.com', 1, '2025-10-24 10:30:15'),
(1, 3, 'user002@example.com', 1, '2025-10-24 10:30:16'),
(1, 4, 'user003@example.com', 2, '2025-10-24 10:30:17'),
(2, 5, 'user004@example.com', 1, '2025-10-23 15:20:10'),
(3, NULL, 'external@example.com', 1, '2025-10-22 09:00:05'),
(3, NULL, 'test@example.com', 2, '2025-10-22 09:00:06');

