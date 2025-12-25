-- ============================================
-- 权限数据初始化脚本
-- 用于维护系统所有API接口的权限标识符
-- 执行前请确保 permissions 表已存在
-- ============================================

-- 清空现有权限数据（可选，谨慎使用）
-- TRUNCATE TABLE permissions;
-- TRUNCATE TABLE role_permissions;

-- ============================================
-- 1. 用户管理权限
-- ============================================
INSERT INTO `permissions` (`id`, `name`, `iden`, `description`, `create_time`, `update_time`) VALUES
(1001, '用户管理', 'user', '用户管理模块', NOW(), NOW()),
(1002, '查看用户列表', 'user:list:view', '查看用户列表权限', NOW(), NOW()),
(1003, '查看用户详情', 'user:detail:view', '查看用户详情权限', NOW(), NOW()),
(1004, '添加用户', 'user:create:admin', '添加用户权限', NOW(), NOW()),
(1005, '编辑用户', 'user:update:admin', '编辑用户权限', NOW(), NOW()),
(1006, '删除用户', 'user:delete:admin', '删除用户权限', NOW(), NOW()),
(1007, '恢复用户', 'user:restore:admin', '恢复已删除用户权限', NOW(), NOW()),
(1008, '用户登录', 'user:login:public', '用户登录权限', NOW(), NOW()),
(1009, '用户注册', 'user:register:public', '用户注册权限', NOW(), NOW()),
(1010, '用户登出', 'user:logout:user', '用户登出权限', NOW(), NOW()),
(1011, '刷新Token', 'user:token:refresh', '刷新Token权限', NOW(), NOW()),
(1012, '会员管理', 'user:premium:admin', '会员管理权限', NOW(), NOW());

-- ============================================
-- 2. 角色管理权限
-- ============================================
INSERT INTO `permissions` (`id`, `name`, `iden`, `description`, `create_time`, `update_time`) VALUES
(2001, '角色管理', 'role', '角色管理模块', NOW(), NOW()),
(2002, '查看角色列表', 'role:list:view', '查看角色列表权限', NOW(), NOW()),
(2003, '查看角色详情', 'role:detail:view', '查看角色详情权限', NOW(), NOW()),
(2004, '添加角色', 'role:create:admin', '添加角色权限', NOW(), NOW()),
(2005, '编辑角色', 'role:update:admin', '编辑角色权限', NOW(), NOW()),
(2006, '删除角色', 'role:delete:admin', '删除角色权限', NOW(), NOW()),
(2007, '恢复角色', 'role:restore:admin', '恢复已删除角色权限', NOW(), NOW()),
(2008, '分配角色权限', 'role:permission:assign', '分配角色权限', NOW(), NOW()),
(2009, '查看用户角色', 'role:user:view', '查看用户角色权限', NOW(), NOW());

-- ============================================
-- 3. 权限管理权限
-- ============================================
INSERT INTO `permissions` (`id`, `name`, `iden`, `description`, `create_time`, `update_time`) VALUES
(3001, '权限管理', 'permission', '权限管理模块', NOW(), NOW()),
(3002, '查看权限列表', 'permission:list:view', '查看权限列表权限', NOW(), NOW()),
(3003, '查看权限树', 'permission:tree:view', '查看权限树权限', NOW(), NOW()),
(3004, '添加权限', 'permission:create:admin', '添加权限', NOW(), NOW()),
(3005, '编辑权限', 'permission:update:admin', '编辑权限', NOW(), NOW()),
(3006, '删除权限', 'permission:delete:admin', '删除权限', NOW(), NOW()),
(3007, '恢复权限', 'permission:restore:admin', '恢复已删除权限', NOW(), NOW());

-- ============================================
-- 4. 文章管理权限
-- ============================================
INSERT INTO `permissions` (`id`, `name`, `iden`, `description`, `create_time`, `update_time`) VALUES
(4001, '文章管理', 'article', '文章管理模块', NOW(), NOW()),
(4002, '查看文章列表', 'article:list:view', '查看文章列表权限', NOW(), NOW()),
(4003, '查看文章详情', 'article:detail:view', '查看文章详情权限', NOW(), NOW()),
(4004, '创建文章', 'article:create:user', '创建文章权限', NOW(), NOW()),
(4005, '编辑文章', 'article:update:user', '编辑文章权限', NOW(), NOW()),
(4006, '删除文章', 'article:delete:user', '删除文章权限', NOW(), NOW()),
(4007, '恢复文章', 'article:restore:admin', '恢复已删除文章权限', NOW(), NOW()),
(4008, '审核文章', 'article:audit:admin', '审核文章权限', NOW(), NOW()),
(4009, '发布文章', 'article:publish:user', '发布文章权限', NOW(), NOW());

-- ============================================
-- 5. 评论管理权限
-- ============================================
INSERT INTO `permissions` (`id`, `name`, `iden`, `description`, `create_time`, `update_time`) VALUES
(5001, '评论管理', 'comment', '评论管理模块', NOW(), NOW()),
(5002, '查看评论列表', 'comment:list:view', '查看评论列表权限', NOW(), NOW()),
(5003, '查看评论详情', 'comment:detail:view', '查看评论详情权限', NOW(), NOW()),
(5004, '发表评论', 'comment:create:user', '发表评论权限', NOW(), NOW()),
(5005, '编辑评论', 'comment:update:user', '编辑评论权限', NOW(), NOW()),
(5006, '删除评论', 'comment:delete:user', '删除评论权限', NOW(), NOW()),
(5007, '审核评论', 'comment:audit:admin', '审核评论权限', NOW(), NOW());

-- ============================================
-- 6. 分类管理权限
-- ============================================
INSERT INTO `permissions` (`id`, `name`, `iden`, `description`, `create_time`, `update_time`) VALUES
(6001, '分类管理', 'category', '分类管理模块', NOW(), NOW()),
(6002, '查看分类列表', 'category:list:view', '查看分类列表权限', NOW(), NOW()),
(6003, '查看分类详情', 'category:detail:view', '查看分类详情权限', NOW(), NOW()),
(6004, '添加分类', 'category:create:admin', '添加分类权限', NOW(), NOW()),
(6005, '编辑分类', 'category:update:admin', '编辑分类权限', NOW(), NOW()),
(6006, '删除分类', 'category:delete:admin', '删除分类权限', NOW(), NOW());

-- ============================================
-- 7. 文件管理权限
-- ============================================
INSERT INTO `permissions` (`id`, `name`, `iden`, `description`, `create_time`, `update_time`) VALUES
(7001, '文件管理', 'file', '文件管理模块', NOW(), NOW()),
(7002, '查看文件列表', 'file:list:view', '查看文件列表权限', NOW(), NOW()),
(7003, '上传文件', 'file:upload:user', '上传文件权限', NOW(), NOW()),
(7004, '下载文件', 'file:download:user', '下载文件权限', NOW(), NOW()),
(7005, '删除文件', 'file:delete:user', '删除文件权限', NOW(), NOW());

-- ============================================
-- 8. 通知管理权限
-- ============================================
INSERT INTO `permissions` (`id`, `name`, `iden`, `description`, `create_time`, `update_time`) VALUES
(8001, '通知管理', 'notice', '通知管理模块', NOW(), NOW()),
(8002, '查看通知列表', 'notice:list:view', '查看通知列表权限', NOW(), NOW()),
(8003, '查看通知详情', 'notice:detail:view', '查看通知详情权限', NOW(), NOW()),
(8004, '创建通知', 'notice:create:admin', '创建通知权限', NOW(), NOW()),
(8005, '编辑通知', 'notice:update:admin', '编辑通知权限', NOW(), NOW()),
(8006, '删除通知', 'notice:delete:admin', '删除通知权限', NOW(), NOW()),
(8007, '发送通知', 'notice:send:admin', '发送通知权限', NOW(), NOW());

-- ============================================
-- 9. 日志管理权限
-- ============================================
INSERT INTO `permissions` (`id`, `name`, `iden`, `description`, `create_time`, `update_time`) VALUES
(9001, '日志管理', 'log', '日志管理模块', NOW(), NOW()),
(9002, '查看操作日志', 'log:operation:view', '查看操作日志权限', NOW(), NOW()),
(9003, '查看登录日志', 'log:login:view', '查看登录日志权限', NOW(), NOW()),
(9004, '查看API日志', 'log:api:view', '查看API日志权限', NOW(), NOW()),
(9005, '清理日志', 'log:clean:admin', '清理日志权限', NOW(), NOW());

-- ============================================
-- 10. 系统管理权限
-- ============================================
INSERT INTO `permissions` (`id`, `name`, `iden`, `description`, `create_time`, `update_time`) VALUES
(10001, '系统管理', 'system', '系统管理模块', NOW(), NOW()),
(10002, '系统配置', 'system:config:admin', '系统配置权限', NOW(), NOW()),
(10003, '菜单管理', 'system:menu:admin', '菜单管理权限', NOW(), NOW()),
(10004, '路由管理', 'system:route:admin', '路由管理权限', NOW(), NOW()),
(10005, '缓存管理', 'system:cache:admin', '缓存管理权限', NOW(), NOW()),
(10006, '数据库管理', 'system:database:admin', '数据库管理权限', NOW(), NOW());

-- ============================================
-- 11. 等级管理权限
-- ============================================
INSERT INTO `permissions` (`id`, `name`, `iden`, `description`, `create_time`, `update_time`) VALUES
(11001, '等级管理', 'level', '等级管理模块', NOW(), NOW()),
(11002, '查看等级列表', 'level:list:view', '查看等级列表权限', NOW(), NOW()),
(11003, '查看等级详情', 'level:detail:view', '查看等级详情权限', NOW(), NOW()),
(11004, '添加等级', 'level:create:admin', '添加等级权限', NOW(), NOW()),
(11005, '编辑等级', 'level:update:admin', '编辑等级权限', NOW(), NOW()),
(11006, '删除等级', 'level:delete:admin', '删除等级权限', NOW(), NOW());

-- ============================================
-- 12. 收藏管理权限
-- ============================================
INSERT INTO `permissions` (`id`, `name`, `iden`, `description`, `create_time`, `update_time`) VALUES
(12001, '收藏管理', 'favorite', '收藏管理模块', NOW(), NOW()),
(12002, '查看收藏列表', 'favorite:list:view', '查看收藏列表权限', NOW(), NOW()),
(12003, '添加收藏', 'favorite:create:user', '添加收藏权限', NOW(), NOW()),
(12004, '取消收藏', 'favorite:delete:user', '取消收藏权限', NOW(), NOW());

-- ============================================
-- 13. 点赞管理权限
-- ============================================
INSERT INTO `permissions` (`id`, `name`, `iden`, `description`, `create_time`, `update_time`) VALUES
(13001, '点赞管理', 'like', '点赞管理模块', NOW(), NOW()),
(13002, '查看点赞列表', 'like:list:view', '查看点赞列表权限', NOW(), NOW()),
(13003, '点赞', 'like:create:user', '点赞权限', NOW(), NOW()),
(13004, '取消点赞', 'like:delete:user', '取消点赞权限', NOW(), NOW());

-- ============================================
-- 为超级管理员角色分配所有权限
-- 假设超级管理员角色ID为1
-- ============================================
-- 获取所有权限ID并分配给超级管理员
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `create_time`, `update_time`)
SELECT 1, id, NOW(), NOW() FROM `permissions` WHERE id >= 1001
ON DUPLICATE KEY UPDATE `update_time` = NOW();

-- ============================================
-- 为普通管理员角色分配部分权限
-- 假设普通管理员角色ID为2
-- ============================================
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `create_time`, `update_time`)
SELECT 2, id, NOW(), NOW() FROM `permissions` 
WHERE id IN (
    -- 用户管理（查看）
    1002, 1003,
    -- 角色管理（查看）
    2002, 2003, 2009,
    -- 文章管理（全部）
    4002, 4003, 4004, 4005, 4006, 4008, 4009,
    -- 评论管理（全部）
    5002, 5003, 5004, 5005, 5006, 5007,
    -- 分类管理（全部）
    6002, 6003, 6004, 6005, 6006,
    -- 文件管理（全部）
    7002, 7003, 7004, 7005,
    -- 通知管理（全部）
    8002, 8003, 8004, 8005, 8006, 8007,
    -- 日志管理（查看）
    9002, 9003, 9004
)
ON DUPLICATE KEY UPDATE `update_time` = NOW();

-- ============================================
-- 为普通用户角色分配基础权限
-- 假设普通用户角色ID为18
-- ============================================
INSERT INTO `role_permissions` (`role_id`, `permission_id`, `create_time`, `update_time`)
SELECT 18, id, NOW(), NOW() FROM `permissions` 
WHERE id IN (
    -- 用户基础权限
    1008, 1009, 1010, 1011,
    -- 文章基础权限
    4002, 4003, 4004, 4005, 4006, 4009,
    -- 评论基础权限
    5002, 5003, 5004, 5005, 5006,
    -- 文件基础权限
    7002, 7003, 7004, 7005,
    -- 收藏权限
    12002, 12003, 12004,
    -- 点赞权限
    13002, 13003, 13004
)
ON DUPLICATE KEY UPDATE `update_time` = NOW();

-- ============================================
-- 查询验证
-- ============================================
-- 查看所有权限
-- SELECT * FROM permissions ORDER BY id;

-- 查看超级管理员的权限
-- SELECT p.* FROM permissions p
-- INNER JOIN role_permissions rp ON p.id = rp.permission_id
-- WHERE rp.role_id = 1
-- ORDER BY p.id;

-- 查看普通管理员的权限
-- SELECT p.* FROM permissions p
-- INNER JOIN role_permissions rp ON p.id = rp.permission_id
-- WHERE rp.role_id = 2
-- ORDER BY p.id;

-- 查看普通用户的权限
-- SELECT p.* FROM permissions p
-- INNER JOIN role_permissions rp ON p.id = rp.permission_id
-- WHERE rp.role_id = 18
-- ORDER BY p.id;
