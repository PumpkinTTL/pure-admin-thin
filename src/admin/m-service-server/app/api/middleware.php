<?php
// 这是系统自动生成的middleware定义文件
return [
    // 日志中间件（最先执行）
    'app\api\middleware\LogMiddleware',

    // 身份认证中间件（验证Token，设置userId）
    'app\api\middleware\Auth',

    // 权限检查中间件（检查细粒度权限）
    'app\api\middleware\PermissionCheck',

    // API日志中间件
    'api_log' => app\api\middleware\ApiLogMiddleware::class,

    // 文件访问中间件
    'file_access' => app\api\middleware\FileAccessMiddleware::class,
];
