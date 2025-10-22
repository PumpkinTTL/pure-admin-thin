<?php

namespace app\api\config;

/**
 * 文件上传配置管理类
 * 统一管理文件上传相关的配置信息
 */
class FileConfig
{
    /**
     * 获取文件上传配置
     * 
     * @return array
     */
    public static function getUploadConfig()
    {
        return [
            // 最大文件大小 (字节)
            'max_size' => 8 * 1024 * 1024, // 8MB
            
            // 最大上传文件数量
            'max_count' => 8,
            
            // 允许的文件类型
            'allowed_types' => [
                'image' => ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'],
                'video' => ['mp4', 'avi', 'mov', 'wmv', 'flv', 'mkv', 'webm'],
                'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv'],
                'audio' => ['mp3', 'wav', 'ogg', 'aac', 'flac'],
                'archive' => ['zip', 'rar', '7z', 'tar', 'gz'],
                'code' => ['js', 'ts', 'php', 'java', 'py', 'c', 'cpp', 'go', 'html', 'css', 'vue']
            ],
            
            // 存储路径配置
            'storage' => [
                'development' => [
                    'base_path' => 'D:/upload/',
                    'base_url' => 'http://localhost/pics/'
                ],
                'production' => [
                    'base_path' => '/home/upload/',
                    'base_url' => 'https://your-domain.com/files/'
                ]
            ],
            
            // 启用MIME类型验证
            'enable_mime_check' => true,
            
            // 启用文件哈希去重
            'enable_deduplication' => true,
            
            // 文件命名策略: md5 | uuid | timestamp
            'naming_strategy' => 'md5',
            
            // 是否按日期分目录
            'date_directory' => true,
            
            // 日期目录格式
            'date_format' => 'Ymd',
            
            // 是否按文件类型分目录
            'type_directory' => true
        ];
    }

    /**
     * 获取当前环境
     * 
     * @return string
     */
    public static function getEnvironment()
    {
        // 通过操作系统类型判断环境
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        
        // 也可以通过配置文件或环境变量判断
        // return env('app.environment', $isWindows ? 'development' : 'production');
        
        return $isWindows ? 'development' : 'production';
    }

    /**
     * 获取存储配置
     * 
     * @return array
     */
    public static function getStorageConfig()
    {
        $config = self::getUploadConfig();
        $env = self::getEnvironment();
        
        return $config['storage'][$env] ?? $config['storage']['development'];
    }

    /**
     * 获取最大文件大小(字节)
     * 
     * @return int
     */
    public static function getMaxSize()
    {
        $config = self::getUploadConfig();
        return $config['max_size'];
    }

    /**
     * 获取最大上传数量
     * 
     * @return int
     */
    public static function getMaxCount()
    {
        $config = self::getUploadConfig();
        return $config['max_count'];
    }

    /**
     * 获取允许的文件类型
     * 
     * @return array
     */
    public static function getAllowedTypes()
    {
        $config = self::getUploadConfig();
        return $config['allowed_types'];
    }

    /**
     * 检查扩展名是否允许
     * 
     * @param string $extension
     * @return bool
     */
    public static function isAllowedExtension($extension)
    {
        $allowedTypes = self::getAllowedTypes();
        
        foreach ($allowedTypes as $type => $extensions) {
            if (in_array(strtolower($extension), $extensions)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * 是否启用MIME检查
     * 
     * @return bool
     */
    public static function isMimeCheckEnabled()
    {
        $config = self::getUploadConfig();
        return $config['enable_mime_check'] ?? true;
    }

    /**
     * 是否启用文件去重
     * 
     * @return bool
     */
    public static function isDeduplicationEnabled()
    {
        $config = self::getUploadConfig();
        return $config['enable_deduplication'] ?? true;
    }
}
