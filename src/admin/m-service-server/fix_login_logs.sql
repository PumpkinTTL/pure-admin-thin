-- 修复 bl_login_logs 表，允许 user_id 为 NULL（用于记录登录失败的情况）
ALTER TABLE `bl_login_logs` MODIFY COLUMN `user_id` INT(11) NULL DEFAULT NULL COMMENT '用户ID（登录失败时可为空）';
