<?php
// 这是系统自动生成的middleware定义文件
return [
    'app\api\middleware\LogMiddleware',
    'app\api\middleware\PermissionCheck',
    'api_log' => app\api\middleware\ApiLogMiddleware::class,
    'file_access' => app\api\middleware\FileAccessMiddleware::class,
];
