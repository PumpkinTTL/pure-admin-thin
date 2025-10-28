-- =============================================
-- 公告权限控制 - 最终版本 SQL 迁移脚本
-- 表前缀: bl_
-- 创建时间: 2025-10-25
-- 参考设计: 文章模块的 visibility 权限控制
-- =============================================
--
-- 📋 设计说明:
--   1. 添加 visibility 字段，支持4种可见性级别
--   2. 废弃 target_uid 字段，使用中间表管理关系
--   3. 保留 notice_type 字段用于业务分类
--   4. 支持阅读状态跟踪
--   5. 兼容现有的 bl_users 和 bl_user_roles 表
--
-- 🎯 可见性级别 (visibility):
--   - public: 公开（所有人可见，包括未登录用户）
--   - login_required: 登录可见（仅已登录用户可见）
--   - specific_users: 指定用户（通过 bl_notice_target 表管理，target_type=1）
--   - specific_roles: 指定角色（通过 bl_notice_target 表管理，target_type=2）
--
-- 📊 字段关系:
--   - notice_type (保留): 业务分类 (1=全体, 2=部分, 3=个人)
--   - visibility (新增): 权限控制 (public, login_required, specific_users, specific_roles)
--   - target_uid (废弃): 改用 bl_notice_target 中间表
--
-- 🔄 映射关系:
--   notice_type=1 → visibility=public 或 login_required
--   notice_type=2 → visibility=specific_users 或 specific_roles
--   notice_type=3 → visibility=specific_users
--
-- 📝 执行步骤:
--   1. 备份数据库（重要！）
--   2. 执行本脚本
--   3. 验证表结构和数据
--   4. 更新前后端代码
--
-- =============================================

-- =============================================
-- 第一步：创建公告目标中间表
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
-- 第二步：修改公告主表
-- =============================================

-- 2.1 添加 visibility 字段（参考文章模块设计）
ALTER TABLE `bl_notice`
ADD COLUMN `visibility` VARCHAR(20) NOT NULL DEFAULT 'public'
COMMENT '可见性：public-公开，login_required-登录可见，specific_users-指定用户，specific_roles-指定角色'
AFTER `notice_type`;

-- 2.2 为 visibility 字段添加索引（提升查询性能）
ALTER TABLE `bl_notice` ADD INDEX `idx_visibility` (`visibility`);

-- 2.3 废弃 target_uid 字段（保留字段但标记为废弃，便于回滚）
ALTER TABLE `bl_notice`
  MODIFY COLUMN `target_uid` varchar(2000) DEFAULT NULL COMMENT '[已废弃] 目标用户ID，请使用 bl_notice_target 表';

-- 2.4 根据现有 notice_type 初始化 visibility 字段（可选）
/*
UPDATE `bl_notice` SET visibility = 'public' WHERE notice_type = 1;  -- 全体公告 → 公开
UPDATE `bl_notice` SET visibility = 'specific_users' WHERE notice_type = 2;  -- 部分用户 → 指定用户
UPDATE `bl_notice` SET visibility = 'specific_users' WHERE notice_type = 3;  -- 个人通知 → 指定用户
*/

-- =============================================
-- 第三步：数据迁移（如果有历史数据）
-- =============================================
-- 说明：将 bl_notice.target_uid 中的逗号分隔ID迁移到中间表
-- 注意：仅迁移 notice_type=2(部分用户) 和 notice_type=3(个人通知) 的数据
-- 执行前请先备份数据！

/*
-- 迁移脚本（取消注释后执行）
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

-- 迁移完成后，清空已迁移的 target_uid 字段（可选）
UPDATE `bl_notice` SET target_uid = NULL WHERE notice_type IN (2, 3);
*/

-- =============================================
-- 第四步：插入测试数据（可选）
-- =============================================

/*
-- 示例1：公告ID=1，全体公告（notice_type=1）
-- 全体公告不需要插入中间表数据，所有用户都能看到

-- 示例2：公告ID=2，部分用户公告（notice_type=2），指定给用户ID=2,3,4
INSERT INTO `bl_notice_target` (`notice_id`, `target_type`, `target_id`) VALUES
(2, 1, 2),  -- 指定用户ID=2
(2, 1, 3),  -- 指定用户ID=3
(2, 1, 4);  -- 指定用户ID=4

-- 示例3：公告ID=3，部分用户公告（notice_type=2），指定给角色ID=1（管理员角色）
INSERT INTO `bl_notice_target` (`notice_id`, `target_type`, `target_id`) VALUES
(3, 2, 1);  -- 指定角色ID=1，该角色下的所有用户都能看到

-- 示例4：公告ID=4，个人通知（notice_type=3），指定给用户ID=5
INSERT INTO `bl_notice_target` (`notice_id`, `target_type`, `target_id`) VALUES
(4, 1, 5);  -- 指定用户ID=5

-- 示例5：公告ID=5，混合指定（既有用户又有角色）
INSERT INTO `bl_notice_target` (`notice_id`, `target_type`, `target_id`) VALUES
(5, 1, 2),  -- 指定用户ID=2
(5, 1, 3),  -- 指定用户ID=3
(5, 2, 1),  -- 指定角色ID=1
(5, 2, 2);  -- 指定角色ID=2
*/

-- =============================================
-- 第五步：常用查询示例
-- =============================================

-- ========================================
-- 1. 查询某个公告的所有目标用户（直接指定的用户）
-- ========================================
/*
SELECT
  nt.id,
  nt.notice_id,
  nt.target_type,
  nt.target_id,
  u.username,
  u.email,
  nt.read_status,
  nt.read_time
FROM bl_notice_target nt
LEFT JOIN bl_users u ON nt.target_id = u.id
WHERE nt.notice_id = 1
  AND nt.target_type = 1  -- 1=用户
ORDER BY nt.create_time ASC;
*/

-- ========================================
-- 2. 查询某个公告的所有目标角色
-- ========================================
/*
SELECT
  nt.id,
  nt.notice_id,
  nt.target_type,
  nt.target_id,
  r.name AS role_name
FROM bl_notice_target nt
LEFT JOIN bl_user_roles r ON nt.target_id = r.id
WHERE nt.notice_id = 1
  AND nt.target_type = 2  -- 2=角色
ORDER BY nt.create_time ASC;
*/

-- ========================================
-- 3. 查询某个公告的所有目标用户（包括角色展开）
-- ========================================
-- 说明：将指定的角色展开为该角色下的所有用户
/*
SELECT DISTINCT
  COALESCE(u.id, u2.id) AS user_id,
  COALESCE(u.username, u2.username) AS username,
  COALESCE(u.email, u2.email) AS email,
  nt.read_status,
  nt.read_time,
  CASE
    WHEN nt.target_type = 1 THEN '直接指定'
    WHEN nt.target_type = 2 THEN CONCAT('角色: ', r.name)
  END AS target_source
FROM bl_notice_target nt
LEFT JOIN bl_users u ON (nt.target_type = 1 AND nt.target_id = u.id)
LEFT JOIN bl_user_roles r ON (nt.target_type = 2 AND nt.target_id = r.id)
LEFT JOIN bl_users u2 ON (r.id IS NOT NULL AND FIND_IN_SET(r.id, u2.role_ids) > 0)
WHERE nt.notice_id = 1
  AND (u.id IS NOT NULL OR u2.id IS NOT NULL)
ORDER BY user_id ASC;
*/

-- ========================================
-- 4. 查询某个用户可见的所有公告
-- ========================================
-- 说明：包括全体公告、指定用户、指定角色
-- 参数：用户ID和用户的角色ID列表（逗号分隔）
/*
SET @user_id = 5;  -- 替换为实际用户ID
SET @user_role_ids = '1,2';  -- 替换为实际用户的角色ID列表

SELECT DISTINCT
  n.notice_id,
  n.title,
  n.content,
  n.notice_type,
  n.category_type,
  n.status,
  n.priority,
  n.is_top,
  n.publish_time,
  n.expire_time,
  CASE
    WHEN n.notice_type = 1 THEN '全体公告'
    WHEN nt.target_type = 1 THEN '指定给我'
    WHEN nt.target_type = 2 THEN '指定给我的角色'
  END AS visible_reason,
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
    n.notice_type = 1  -- 全体公告
    OR (n.notice_type IN (2, 3) AND nt.id IS NOT NULL)  -- 部分用户或个人通知，且在目标列表中
  )
ORDER BY n.is_top DESC, n.publish_time DESC;
*/

-- ========================================
-- 5. 查询某个用户的未读公告数量
-- ========================================
/*
SET @user_id = 5;  -- 替换为实际用户ID
SET @user_role_ids = '1,2';  -- 替换为实际用户的角色ID列表

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
    n.notice_type = 1  -- 全体公告（需要额外逻辑判断是否已读）
    OR (n.notice_type IN (2, 3) AND nt.id IS NOT NULL AND nt.read_status = 0)
  );
*/

-- ========================================
-- 6. 标记公告为已读
-- ========================================
-- 说明：仅对 target_type=1（用户）的记录有效
/*
UPDATE bl_notice_target
SET read_status = 1, read_time = NOW()
WHERE notice_id = 1
  AND target_type = 1
  AND target_id = 5;  -- 替换为实际用户ID
*/

-- ========================================
-- 7. 查询公告的阅读统计（仅统计直接指定的用户）
-- ========================================
/*
SELECT
  n.notice_id,
  n.title,
  n.notice_type,
  COUNT(nt.id) AS total_targets,
  SUM(CASE WHEN nt.read_status = 1 THEN 1 ELSE 0 END) AS read_count,
  SUM(CASE WHEN nt.read_status = 0 THEN 1 ELSE 0 END) AS unread_count,
  ROUND(SUM(CASE WHEN nt.read_status = 1 THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(nt.id), 0), 2) AS read_rate
FROM bl_notice n
LEFT JOIN bl_notice_target nt ON (n.notice_id = nt.notice_id AND nt.target_type = 1)
WHERE n.notice_id = 1
GROUP BY n.notice_id, n.title, n.notice_type;
*/

-- ========================================
-- 8. 批量插入公告目标（创建公告时使用）
-- ========================================
/*
-- 示例：公告ID=10，指定给用户2,3,4和角色1,2
INSERT INTO `bl_notice_target` (`notice_id`, `target_type`, `target_id`) VALUES
(10, 1, 2),
(10, 1, 3),
(10, 1, 4),
(10, 2, 1),
(10, 2, 2);
*/

-- ========================================
-- 9. 删除公告的所有目标关联（删除公告时使用）
-- ========================================
/*
DELETE FROM bl_notice_target WHERE notice_id = 1;
*/

-- ========================================
-- 10. 更新公告的目标关联（编辑公告时使用）
-- ========================================
/*
-- 先删除旧的关联
DELETE FROM bl_notice_target WHERE notice_id = 1;

-- 再插入新的关联
INSERT INTO `bl_notice_target` (`notice_id`, `target_type`, `target_id`) VALUES
(1, 1, 5),
(1, 1, 6),
(1, 2, 3);
*/

-- =============================================
-- 第六步：性能优化建议
-- =============================================
/*
-- 1. 定期清理已删除公告的关联数据
DELETE nt FROM bl_notice_target nt
LEFT JOIN bl_notice n ON nt.notice_id = n.notice_id
WHERE n.notice_id IS NULL OR n.delete_time IS NOT NULL;

-- 2. 定期清理过期公告的关联数据（可选）
DELETE nt FROM bl_notice_target nt
INNER JOIN bl_notice n ON nt.notice_id = n.notice_id
WHERE n.expire_time IS NOT NULL AND n.expire_time < DATE_SUB(NOW(), INTERVAL 30 DAY);

-- 3. 分析表性能
ANALYZE TABLE bl_notice_target;
*/

-- =============================================
-- 第七步：清理和回滚脚本（谨慎使用！）
-- =============================================

-- 删除中间表（谨慎！会丢失所有关联数据）
-- DROP TABLE IF EXISTS `bl_notice_target`;

-- 恢复 target_uid 字段的注释（如果需要回滚）
-- ALTER TABLE `bl_notice`
--   MODIFY COLUMN `target_uid` varchar(2000) DEFAULT NULL COMMENT '目标用户ID，多个用逗号分隔(Notice_type=2时使用)';

