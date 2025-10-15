-- ===========================================
-- 文章权限控制 - 快速执行版
-- 表前缀: bl_
-- ===========================================

-- 1. 添加 visibility 字段
ALTER TABLE bl_article 
ADD COLUMN visibility VARCHAR(20) NOT NULL DEFAULT 'public' 
COMMENT '可见性：public-公开，login_required-登录可见，specific_users-指定用户，specific_roles-指定角色，private-私密' 
AFTER status;

-- 2. 添加索引
ALTER TABLE bl_article ADD INDEX idx_visibility (visibility);

-- 3. 创建文章-用户访问权限表
CREATE TABLE IF NOT EXISTS bl_article_user_access (
    id INT(11) UNSIGNED PRIMARY KEY AUTO_INCREMENT COMMENT '主键ID',
    article_id INT(11) NOT NULL COMMENT '文章ID',
    user_id INT(11) NOT NULL COMMENT '用户ID',
    create_time DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '授权时间',
    KEY idx_article_id (article_id),
    KEY idx_user_id (user_id),
    UNIQUE KEY unique_article_user (article_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文章-用户访问权限表';

-- 4. 创建文章-角色访问权限表
CREATE TABLE IF NOT EXISTS bl_article_role_access (
    id INT(11) UNSIGNED PRIMARY KEY AUTO_INCREMENT COMMENT '主键ID',
    article_id INT(11) NOT NULL COMMENT '文章ID',
    role_id INT(11) NOT NULL COMMENT '角色ID',
    create_time DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '授权时间',
    KEY idx_article_id (article_id),
    KEY idx_role_id (role_id),
    UNIQUE KEY unique_article_role (article_id, role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文章-角色访问权限表';

