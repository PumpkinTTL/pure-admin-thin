-- 权限表结构更新脚本

-- 检查parent_id字段是否存在，如果不存在则添加
ALTER TABLE `think_permissions` ADD COLUMN IF NOT EXISTS `parent_id` INT DEFAULT 0 COMMENT '父级权限ID, 0表示顶级权限';

-- 将所有NULL的parent_id设置为0
UPDATE `think_permissions` SET `parent_id` = 0 WHERE `parent_id` IS NULL;

-- 创建权限分类记录（如果不存在）
INSERT INTO `think_permissions` (`name`, `iden`, `description`, `parent_id`)
SELECT '系统管理', 'system', '系统管理相关权限', 0
WHERE NOT EXISTS (
    SELECT 1 FROM `think_permissions` WHERE `iden` = 'system' AND `parent_id` = 0
);

INSERT INTO `think_permissions` (`name`, `iden`, `description`, `parent_id`)
SELECT '用户管理', 'user', '用户管理相关权限', 0
WHERE NOT EXISTS (
    SELECT 1 FROM `think_permissions` WHERE `iden` = 'user' AND `parent_id` = 0
);

INSERT INTO `think_permissions` (`name`, `iden`, `description`, `parent_id`)
SELECT '角色管理', 'role', '角色管理相关权限', 0
WHERE NOT EXISTS (
    SELECT 1 FROM `think_permissions` WHERE `iden` = 'role' AND `parent_id` = 0
);

INSERT INTO `think_permissions` (`name`, `iden`, `description`, `parent_id`)
SELECT '权限管理', 'permission', '权限管理相关权限', 0
WHERE NOT EXISTS (
    SELECT 1 FROM `think_permissions` WHERE `iden` = 'permission' AND `parent_id` = 0
);

INSERT INTO `think_permissions` (`name`, `iden`, `description`, `parent_id`)
SELECT '菜单管理', 'menu', '菜单管理相关权限', 0
WHERE NOT EXISTS (
    SELECT 1 FROM `think_permissions` WHERE `iden` = 'menu' AND `parent_id` = 0
);

INSERT INTO `think_permissions` (`name`, `iden`, `description`, `parent_id`)
SELECT '内容管理', 'content', '内容管理相关权限', 0
WHERE NOT EXISTS (
    SELECT 1 FROM `think_permissions` WHERE `iden` = 'content' AND `parent_id` = 0
);

-- 将各权限归类到对应的分类下
-- 系统管理权限
UPDATE `think_permissions` p1
JOIN `think_permissions` p2 ON p2.`iden` = 'system' AND p2.`parent_id` = 0
SET p1.`parent_id` = p2.`id`
WHERE p1.`iden` LIKE 'system:%' AND p1.`id` != p2.`id`;

-- 用户管理权限
UPDATE `think_permissions` p1
JOIN `think_permissions` p2 ON p2.`iden` = 'user' AND p2.`parent_id` = 0
SET p1.`parent_id` = p2.`id`
WHERE p1.`iden` LIKE 'user:%' AND p1.`id` != p2.`id`;

-- 角色管理权限
UPDATE `think_permissions` p1
JOIN `think_permissions` p2 ON p2.`iden` = 'role' AND p2.`parent_id` = 0
SET p1.`parent_id` = p2.`id`
WHERE p1.`iden` LIKE 'role:%' AND p1.`id` != p2.`id`;

-- 权限管理权限
UPDATE `think_permissions` p1
JOIN `think_permissions` p2 ON p2.`iden` = 'permission' AND p2.`parent_id` = 0
SET p1.`parent_id` = p2.`id`
WHERE p1.`iden` LIKE 'permission:%' AND p1.`id` != p2.`id`;

-- 菜单管理权限
UPDATE `think_permissions` p1
JOIN `think_permissions` p2 ON p2.`iden` = 'menu' AND p2.`parent_id` = 0
SET p1.`parent_id` = p2.`id`
WHERE p1.`iden` LIKE 'menu:%' AND p1.`id` != p2.`id`;

-- 内容管理权限
UPDATE `think_permissions` p1
JOIN `think_permissions` p2 ON p2.`iden` = 'content' AND p2.`parent_id` = 0
SET p1.`parent_id` = p2.`id`
WHERE p1.`iden` LIKE 'content:%' AND p1.`id` != p2.`id`; 