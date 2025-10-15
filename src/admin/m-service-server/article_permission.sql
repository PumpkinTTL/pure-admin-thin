-- =====================================================
-- 文章权限控制 - 数据库结构
-- 表前缀: bl_
-- 创建时间: 2025-10-14
-- =====================================================

-- =====================================================
-- 步骤 1: 为 bl_article 表添加 visibility 字段
-- =====================================================
ALTER TABLE bl_article 
ADD COLUMN visibility VARCHAR(20) NOT NULL DEFAULT 'public' 
COMMENT '可见性：public-公开，login_required-登录可见，specific_users-指定用户，specific_roles-指定角色，private-私密' 
AFTER status;

-- 为 visibility 字段添加索引（提升查询性能）
ALTER TABLE bl_article ADD INDEX idx_visibility (visibility);

-- =====================================================
-- 步骤 2: 创建文章-用户访问权限表
-- =====================================================
CREATE TABLE IF NOT EXISTS bl_article_user_access (
    id INT(11) UNSIGNED PRIMARY KEY AUTO_INCREMENT COMMENT '主键ID',
    article_id INT(11) NOT NULL COMMENT '文章ID',
    user_id INT(11) NOT NULL COMMENT '用户ID',
    create_time DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '授权时间',
    
    -- 索引
    KEY idx_article_id (article_id) COMMENT '文章ID索引',
    KEY idx_user_id (user_id) COMMENT '用户ID索引',
    UNIQUE KEY unique_article_user (article_id, user_id) COMMENT '文章-用户唯一索引，防止重复授权'
    
    -- 外键约束（可选，根据需要启用）
    -- CONSTRAINT fk_article_user_article FOREIGN KEY (article_id) REFERENCES bl_article(id) ON DELETE CASCADE,
    -- CONSTRAINT fk_article_user_user FOREIGN KEY (user_id) REFERENCES bl_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文章-用户访问权限表';

-- =====================================================
-- 步骤 3: 创建文章-角色访问权限表
-- =====================================================
CREATE TABLE IF NOT EXISTS bl_article_role_access (
    id INT(11) UNSIGNED PRIMARY KEY AUTO_INCREMENT COMMENT '主键ID',
    article_id INT(11) NOT NULL COMMENT '文章ID',
    role_id INT(11) NOT NULL COMMENT '角色ID',
    create_time DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '授权时间',
    
    -- 索引
    KEY idx_article_id (article_id) COMMENT '文章ID索引',
    KEY idx_role_id (role_id) COMMENT '角色ID索引',
    UNIQUE KEY unique_article_role (article_id, role_id) COMMENT '文章-角色唯一索引，防止重复授权'
    
    -- 外键约束（可选，根据需要启用）
    -- CONSTRAINT fk_article_role_article FOREIGN KEY (article_id) REFERENCES bl_article(id) ON DELETE CASCADE,
    -- CONSTRAINT fk_article_role_role FOREIGN KEY (role_id) REFERENCES bl_roles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文章-角色访问权限表';

-- =====================================================
-- 步骤 4: 插入测试数据（可选）
-- =====================================================
-- 如果需要测试，可以插入一些示例数据

-- 示例1：将文章ID=10001的可见性设置为"指定用户"
-- UPDATE bl_article SET visibility = 'specific_users' WHERE id = 10001;

-- 示例2：授权用户ID=1和2可以访问文章ID=10001
-- INSERT INTO bl_article_user_access (article_id, user_id) VALUES (10001, 1), (10001, 2);

-- 示例3：将文章ID=10002的可见性设置为"指定角色"
-- UPDATE bl_article SET visibility = 'specific_roles' WHERE id = 10002;

-- 示例4：授权角色ID=2（如VIP角色）可以访问文章ID=10002
-- INSERT INTO bl_article_role_access (article_id, role_id) VALUES (10002, 2);

-- =====================================================
-- 步骤 5: 验证表结构
-- =====================================================
-- 查看 bl_article 表结构
-- DESCRIBE bl_article;

-- 查看 bl_article_user_access 表结构
-- DESCRIBE bl_article_user_access;

-- 查看 bl_article_role_access 表结构
-- DESCRIBE bl_article_role_access;

-- =====================================================
-- 步骤 6: 查询示例（供测试使用）
-- =====================================================

-- 查询所有公开文章
-- SELECT id, title, visibility FROM bl_article WHERE visibility = 'public';

-- 查询用户ID=1可以访问的所有文章（包括公开、自己创建、指定用户）
-- SELECT a.* FROM bl_article a
-- LEFT JOIN bl_article_user_access aua ON a.id = aua.article_id AND aua.user_id = 1
-- WHERE a.visibility = 'public' 
--    OR a.author_id = 1 
--    OR (a.visibility = 'specific_users' AND aua.user_id IS NOT NULL);

-- 查询角色ID=2可以访问的所有文章
-- SELECT a.* FROM bl_article a
-- LEFT JOIN bl_article_role_access ara ON a.id = ara.article_id AND ara.role_id = 2
-- WHERE a.visibility = 'public' 
--    OR (a.visibility = 'specific_roles' AND ara.role_id IS NOT NULL);

-- =====================================================
-- 回滚脚本（如果需要删除这些修改）
-- =====================================================
-- 警告：执行以下命令会删除所有权限相关的数据和表结构！

-- 删除文章表的 visibility 字段
-- ALTER TABLE bl_article DROP COLUMN visibility;

-- 删除文章-用户访问权限表
-- DROP TABLE IF EXISTS bl_article_user_access;

-- 删除文章-角色访问权限表
-- DROP TABLE IF EXISTS bl_article_role_access;

-- =====================================================
-- 完成！
-- =====================================================
-- 执行完成后，请验证：
-- 1. bl_article 表是否有 visibility 字段
-- 2. bl_article_user_access 表是否创建成功
-- 3. bl_article_role_access 表是否创建成功
-- 4. 所有索引是否正确创建
-- =====================================================

