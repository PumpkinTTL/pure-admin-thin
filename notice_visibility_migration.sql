-- =============================================
-- 公告权限控制 - 最终版本 SQL 迁移脚本
-- 表前缀: bl_
-- 创建时间: 2025-10-25
-- 参考设计: 文章模块的 visibility 权限控制
-- =============================================
-- 
-- 📋 设计说明:
--   1. 添加 visibility 字段，支持4种可见性级别
--   2. 创建 bl_notice_target 中间表管理用户/角色关系
--   3. 废弃 target_uid 字段（保留但标记废弃）
--   4. 保留 notice_type 字段用于业务分类
--   5. 支持阅读状态跟踪
-- 
-- 🎯 可见性级别 (visibility):
--   - public: 公开（所有人可见，包括未登录用户）
--   - login_required: 登录可见（仅已登录用户可见）
--   - specific_users: 指定用户（通过 bl_notice_target 表管理，target_type=1）
--   - specific_roles: 指定角色（通过 bl_notice_target 表管理，target_type=2）
-- 
-- 📝 执行步骤:
--   1. ⚠️ 备份数据库（重要！）
--   2. 执行本脚本
--   3. 验证表结构和数据
--   4. 更新前后端代码
-- 
-- =============================================

-- =============================================
-- 步骤 1: 创建公告目标中间表
-- =============================================
DROP TABLE IF EXISTS `bl_notice_target`;
CREATE TABLE `bl_notice_target` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `notice_id` int(11) UNSIGNED NOT NULL COMMENT '公告ID，关联 bl_notice.notice_id',
  `target_type` tinyint(4) NOT NULL DEFAULT 1 COMMENT '目标类型: 1-指定用户, 2-指定角色',
  `target_id` int(11) UNSIGNED NOT NULL COMMENT '目标ID: target_type=1时关联bl_users.id, target_type=2时关联bl_user_roles.id',
  `read_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '阅读状态: 0-未读, 1-已读 (仅target_type=1时有效)',
  `read_time` datetime DEFAULT NULL COMMENT '阅读时间 (仅target_type=1时有效)',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_notice_id` (`notice_id`) COMMENT '公告ID索引，用于查询某公告的所有目标',
  KEY `idx_target` (`target_type`, `target_id`) COMMENT '目标类型和ID联合索引，用于查询某用户/角色的所有公告',
  KEY `idx_read_status` (`read_status`) COMMENT '阅读状态索引，用于统计未读数量',
  KEY `idx_user_unread` (`target_type`, `target_id`, `read_status`) COMMENT '用户未读公告查询优化索引',
  UNIQUE KEY `uk_notice_target` (`notice_id`, `target_type`, `target_id`) COMMENT '防止重复关联：同一公告不能重复指定同一目标'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='公告目标关联表 - 管理公告与用户/角色的多对多关系';

-- =============================================
-- 步骤 2: 修改公告主表，添加 visibility 字段
-- =============================================

-- 2.1 添加 visibility 字段
ALTER TABLE `bl_notice` 
ADD COLUMN `visibility` VARCHAR(20) NOT NULL DEFAULT 'public' 
COMMENT '可见性：public-公开，login_required-登录可见，specific_users-指定用户，specific_roles-指定角色' 
AFTER `notice_type`;

-- 2.2 为 visibility 字段添加索引（提升查询性能）
ALTER TABLE `bl_notice` ADD INDEX `idx_visibility` (`visibility`);

-- 2.3 废弃 target_uid 字段（保留字段但标记为废弃，便于回滚）
ALTER TABLE `bl_notice` 
MODIFY COLUMN `target_uid` varchar(2000) DEFAULT NULL COMMENT '[已废弃] 目标用户ID，请使用 bl_notice_target 表';

-- =============================================
-- 步骤 3: 数据迁移（将现有数据迁移到新结构）
-- =============================================

-- 3.1 根据 notice_type 初始化 visibility 字段
UPDATE `bl_notice` SET visibility = 'public' WHERE notice_type = 1;  -- 全体公告 → 公开
UPDATE `bl_notice` SET visibility = 'specific_users' WHERE notice_type = 2;  -- 部分用户 → 指定用户
UPDATE `bl_notice` SET visibility = 'specific_users' WHERE notice_type = 3;  -- 个人通知 → 指定用户

-- 3.2 迁移 target_uid 数据到中间表（如果有历史数据）
-- 说明：将逗号分隔的用户ID拆分并插入到中间表
INSERT INTO `bl_notice_target` (`notice_id`, `target_type`, `target_id`, `create_time`)
SELECT 
  n.notice_id,
  1 AS target_type, -- 1表示用户类型
  CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(n.target_uid, ',', numbers.n), ',', -1) AS UNSIGNED) AS target_id,
  n.create_time
FROM `bl_notice` n
CROSS JOIN (
  SELECT 1 n UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5
  UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9 UNION ALL SELECT 10
  UNION ALL SELECT 11 UNION ALL SELECT 12 UNION ALL SELECT 13 UNION ALL SELECT 14 UNION ALL SELECT 15
  UNION ALL SELECT 16 UNION ALL SELECT 17 UNION ALL SELECT 18 UNION ALL SELECT 19 UNION ALL SELECT 20
) numbers
WHERE n.notice_type IN (2, 3)  -- 仅迁移部分用户和个人通知
  AND n.target_uid IS NOT NULL 
  AND n.target_uid != ''
  AND n.target_uid != '0'
  AND CHAR_LENGTH(n.target_uid) - CHAR_LENGTH(REPLACE(n.target_uid, ',', '')) >= numbers.n - 1
  AND CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(n.target_uid, ',', numbers.n), ',', -1) AS UNSIGNED) > 0
ON DUPLICATE KEY UPDATE update_time = NOW();

-- 3.3 迁移完成后，清空已迁移的 target_uid 字段（可选）
-- UPDATE `bl_notice` SET target_uid = NULL WHERE notice_type IN (2, 3);

-- =============================================
-- 步骤 4: 验证数据
-- =============================================

-- 4.1 查看 bl_notice 表结构
-- DESCRIBE bl_notice;

-- 4.2 查看 bl_notice_target 表结构
-- DESCRIBE bl_notice_target;

-- 4.3 验证数据迁移结果
-- SELECT COUNT(*) AS total_notices FROM bl_notice;
-- SELECT COUNT(*) AS total_targets FROM bl_notice_target;
-- SELECT visibility, COUNT(*) AS count FROM bl_notice GROUP BY visibility;

-- =============================================
-- 步骤 5: 插入测试数据（可选）
-- =============================================

/*
-- 示例1：公开公告（所有人可见）
INSERT INTO `bl_notice` (title, content, notice_type, visibility, publisher_id, status) VALUES
('系统维护通知', '系统将于今晚进行维护...', 1, 'public', 1, 1);

-- 示例2：登录可见公告
INSERT INTO `bl_notice` (title, content, notice_type, visibility, publisher_id, status) VALUES
('会员福利通知', '尊敬的会员...', 1, 'login_required', 1, 1);

-- 示例3：指定用户公告（需要配合中间表）
INSERT INTO `bl_notice` (title, content, notice_type, visibility, publisher_id, status) VALUES
('个人通知', '您有一条新消息...', 3, 'specific_users', 1, 1);
-- 假设公告ID=100，指定给用户2,3,4
INSERT INTO `bl_notice_target` (notice_id, target_type, target_id) VALUES
(100, 1, 2),
(100, 1, 3),
(100, 1, 4);

-- 示例4：指定角色公告（需要配合中间表）
INSERT INTO `bl_notice` (title, content, notice_type, visibility, publisher_id, status) VALUES
('管理员通知', '管理员专属消息...', 2, 'specific_roles', 1, 1);
-- 假设公告ID=101，指定给角色1（管理员）
INSERT INTO `bl_notice_target` (notice_id, target_type, target_id) VALUES
(101, 2, 1);
*/

-- =============================================
-- 步骤 6: 常用查询示例
-- =============================================

-- 6.1 查询用户可见的所有公告（核心查询）
/*
SET @user_id = 5;  -- 替换为实际用户ID
SET @user_role_ids = '1,2';  -- 替换为实际用户的角色ID列表（逗号分隔）

SELECT DISTINCT 
  n.notice_id,
  n.title,
  n.content,
  n.visibility,
  n.status,
  n.priority,
  n.is_top,
  n.publish_time,
  nt.read_status,
  nt.read_time
FROM bl_notice n
LEFT JOIN bl_notice_target nt ON (
  n.notice_id = nt.notice_id 
  AND (
    (nt.target_type = 1 AND nt.target_id = @user_id)  -- 直接指定给该用户
    OR (nt.target_type = 2 AND FIND_IN_SET(nt.target_id, @user_role_ids))  -- 指定给该用户的角色
  )
)
WHERE n.delete_time IS NULL
  AND n.status = 1  -- 1=已发布
  AND (n.expire_time IS NULL OR n.expire_time > NOW())  -- 未过期
  AND (
    n.visibility = 'public'  -- 公开公告
    OR n.visibility = 'login_required'  -- 登录可见
    OR (n.visibility = 'specific_users' AND nt.id IS NOT NULL)  -- 指定用户
    OR (n.visibility = 'specific_roles' AND nt.id IS NOT NULL)  -- 指定角色
  )
ORDER BY n.is_top DESC, n.publish_time DESC;
*/

-- 6.2 标记公告为已读
/*
UPDATE bl_notice_target 
SET read_status = 1, read_time = NOW() 
WHERE notice_id = 1 
  AND target_type = 1 
  AND target_id = 5;  -- 替换为实际用户ID
*/

-- 6.3 查询用户未读公告数量
/*
SET @user_id = 5;
SET @user_role_ids = '1,2';

SELECT COUNT(DISTINCT n.notice_id) AS unread_count
FROM bl_notice n
LEFT JOIN bl_notice_target nt ON (
  n.notice_id = nt.notice_id 
  AND (
    (nt.target_type = 1 AND nt.target_id = @user_id)
    OR (nt.target_type = 2 AND FIND_IN_SET(nt.target_id, @user_role_ids))
  )
)
WHERE n.delete_time IS NULL
  AND n.status = 1
  AND (n.expire_time IS NULL OR n.expire_time > NOW())
  AND (
    (n.visibility = 'specific_users' AND nt.id IS NOT NULL AND nt.read_status = 0)
    OR (n.visibility = 'specific_roles' AND nt.id IS NOT NULL AND nt.read_status = 0)
  );
*/

-- 6.4 查询某个公告的所有目标用户
/*
SELECT 
  nt.target_id AS user_id,
  u.username,
  u.email,
  nt.read_status,
  nt.read_time
FROM bl_notice_target nt
LEFT JOIN bl_users u ON nt.target_id = u.id
WHERE nt.notice_id = 1 
  AND nt.target_type = 1
ORDER BY nt.create_time ASC;
*/

-- 6.5 查询某个公告的所有目标角色
/*
SELECT 
  nt.target_id AS role_id,
  r.name AS role_name
FROM bl_notice_target nt
LEFT JOIN bl_user_roles r ON nt.target_id = r.id
WHERE nt.notice_id = 1 
  AND nt.target_type = 2
ORDER BY nt.create_time ASC;
*/

-- =============================================
-- 步骤 7: 性能优化（可选）
-- =============================================

/*
-- 定期清理已删除公告的关联数据
DELETE nt FROM bl_notice_target nt
LEFT JOIN bl_notice n ON nt.notice_id = n.notice_id
WHERE n.notice_id IS NULL OR n.delete_time IS NOT NULL;

-- 定期清理过期公告的关联数据（可选）
DELETE nt FROM bl_notice_target nt
INNER JOIN bl_notice n ON nt.notice_id = n.notice_id
WHERE n.expire_time IS NOT NULL AND n.expire_time < DATE_SUB(NOW(), INTERVAL 30 DAY);

-- 分析表性能
ANALYZE TABLE bl_notice;
ANALYZE TABLE bl_notice_target;
*/

-- =============================================
-- 步骤 8: 回滚脚本（谨慎使用！）
-- =============================================

/*
-- 删除中间表
DROP TABLE IF EXISTS `bl_notice_target`;

-- 删除 visibility 字段
ALTER TABLE `bl_notice` DROP INDEX `idx_visibility`;
ALTER TABLE `bl_notice` DROP COLUMN `visibility`;

-- 恢复 target_uid 字段注释
ALTER TABLE `bl_notice` 
MODIFY COLUMN `target_uid` varchar(2000) DEFAULT NULL COMMENT '目标用户ID，多个用逗号分隔(Notice_type=2时使用)';
*/

-- =============================================
-- 迁移完成！
-- =============================================
-- 
-- ✅ 下一步：
--   1. 验证表结构和数据是否正确
--   2. 更新后端代码（Model、Service、Controller、Middleware）
--   3. 更新前端代码（API、常量、组件）
--   4. 测试权限控制功能
-- 
-- =============================================

