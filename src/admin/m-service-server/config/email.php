<?php
/**
 * 邮件配置
 * 
 * 使用说明：
 * 1. 修改 host/username/password 为你的邮箱服务器信息
 * 2. password 是 SMTP 授权码，不是邮箱登录密码
 * 3. QQ邮箱授权码获取：邮箱设置 -> 账户 -> 开启SMTP服务
 */
return [
    // 调试模式：0=关闭, 1=客户端消息, 2=客户端和服务器消息, 3=连接状态, 4=详细调试信息
    'debug' => 0,
    
    // SMTP 认证
    'smtp_auth' => true,
    
    // SMTP 服务器地址
    // QQ邮箱: smtp.qq.com
    // 163邮箱: smtp.163.com
    // Gmail: smtp.gmail.com
    'host' => 'smtp.163.com',
    
    // 加密方式: ssl 或 tls
    'secure' => 'ssl',
    
    // SMTP 端口
    // SSL通常使用 465
    // TLS通常使用 587
    'port' => 465,
    
    // 字符编码
    'charset' => 'UTF-8',
    
    // 发件人名称
    'from_name' => '知识棱镜',
    
    // SMTP 登录账号（邮箱地址）
    'username' => 'weekseven@163.com',
    
    // SMTP 授权码（不是邮箱密码！需要在163邮箱设置中获取）
    'password' => 'NCwZ5AZWaAvdAHdC',
    
    // 发件人邮箱地址（通常与 username 相同）
    'from_address' => 'weekseven@163.com',
];
