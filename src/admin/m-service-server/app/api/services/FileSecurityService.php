<?php

namespace app\api\services;

use think\facade\Log;
use app\api\config\FileConfig;

/**
 * 文件安全验证服务
 */
class FileSecurityService
{
    /**
     * 允许的MIME类型和对应的文件扩展名映射
     */
    private static $allowedMimeTypes = [
        // 图片
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/gif' => ['gif'],
        'image/bmp' => ['bmp'],
        'image/webp' => ['webp'],
        'image/svg+xml' => ['svg'],

        // 视频
        'video/mp4' => ['mp4'],
        'video/mpeg' => ['mpeg', 'mpg'],
        'video/quicktime' => ['mov'],
        'video/x-msvideo' => ['avi'],
        'video/x-ms-wmv' => ['wmv'],
        'video/x-flv' => ['flv'],
        'video/x-matroska' => ['mkv'],
        'video/webm' => ['webm'],

        // 音频
        'audio/mpeg' => ['mp3'],
        'audio/wav' => ['wav'],
        'audio/ogg' => ['ogg'],
        'audio/aac' => ['aac'],
        'audio/flac' => ['flac'],
        'audio/x-m4a' => ['m4a'],

        // 文档
        'application/pdf' => ['pdf'],
        'application/msword' => ['doc'],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx'],
        'application/vnd.ms-excel' => ['xls'],
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['xlsx'],
        'application/vnd.ms-powerpoint' => ['ppt'],
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => ['pptx'],
        'text/plain' => ['txt'],
        'text/csv' => ['csv'],
        'text/html' => ['html', 'htm'],
        'text/css' => ['css'],
        'application/json' => ['json'],
        'application/xml' => ['xml'],
        'text/xml' => ['xml'],
        'text/markdown' => ['md'],

        // 压缩包
        'application/zip' => ['zip'],
        'application/x-rar-compressed' => ['rar'],
        'application/x-7z-compressed' => ['7z'],
        'application/x-tar' => ['tar'],
        'application/gzip' => ['gz'],

        // 代码文件
        'text/javascript' => ['js'],
        'application/javascript' => ['js'],
        'text/x-php' => ['php'],
        'application/x-php' => ['php'],
        'text/x-python' => ['py'],
        'text/x-java' => ['java'],
        'text/x-c' => ['c', 'h'],
        'text/x-c++' => ['cpp', 'hpp'],
    ];

    /**
     * 危险文件扩展名黑名单（已废弃，使用 FileConfig::getForbiddenExtensions() 代替）
     * @deprecated 使用 FileConfig::getForbiddenExtensions() 代替
     */
    // private static $dangerousExtensions = [];

    /**
     * 验证文件MIME类型和扩展名是否匹配
     * 
     * @param string $filePath 文件路径
     * @param string $extension 文件扩展名
     * @param string $declaredMime 声明的MIME类型(来自浏览器)
     * @return array ['valid' => bool, 'message' => string, 'mime' => string]
     */
    public static function validateFile($filePath, $extension, $declaredMime = '')
    {
        $extension = strtolower($extension);

        // 1. 检查危险扩展名（使用配置类中的禁止列表）
        if (FileConfig::isForbiddenExtension($extension)) {
            Log::warning('危险文件类型被拒绝', [
                'extension' => $extension,
                'path' => $filePath,
                'declared_mime' => $declaredMime
            ]);

            return [
                'valid' => false,
                'message' => "禁止上传 .{$extension} 类型的文件（安全限制）",
                'mime' => ''
            ];
        }

        // 2. 检查扩展名是否在允许列表中
        if (!FileConfig::isAllowedExtension($extension)) {
            Log::warning('不允许的文件扩展名', [
                'extension' => $extension,
                'path' => $filePath
            ]);

            return [
                'valid' => false,
                'message' => "不支持上传 .{$extension} 类型的文件",
                'mime' => ''
            ];
        }

        // 3. 获取文件真实MIME类型
        $realMime = self::getRealMimeType($filePath);

        if (!$realMime) {
            return [
                'valid' => false,
                'message' => '无法识别文件类型',
                'mime' => ''
            ];
        }

        // 4. 检查MIME类型是否在允许列表中
        if (!isset(self::$allowedMimeTypes[$realMime])) {
            // 对于某些特殊MIME类型，尝试模糊匹配
            $mimePrefix = explode('/', $realMime)[0];
            $allowedPrefixes = ['image', 'video', 'audio', 'text', 'application'];

            if (!in_array($mimePrefix, $allowedPrefixes)) {
                Log::warning('不允许的MIME类型', [
                    'mime' => $realMime,
                    'extension' => $extension,
                    'path' => $filePath
                ]);

                return [
                    'valid' => false,
                    'message' => "不支持的文件类型: {$realMime}",
                    'mime' => $realMime
                ];
            }
        }

        // 5. 验证MIME类型和扩展名是否匹配
        if (isset(self::$allowedMimeTypes[$realMime])) {
            $allowedExtensions = self::$allowedMimeTypes[$realMime];

            if (!in_array($extension, $allowedExtensions)) {
                Log::warning('文件扩展名与MIME类型不匹配', [
                    'mime' => $realMime,
                    'extension' => $extension,
                    'allowed_extensions' => $allowedExtensions,
                    'path' => $filePath
                ]);

                return [
                    'valid' => false,
                    'message' => "文件扩展名(.{$extension})与文件内容不符",
                    'mime' => $realMime
                ];
            }
        }

        // 6. 验证通过
        return [
            'valid' => true,
            'message' => '文件验证通过',
            'mime' => $realMime
        ];
    }

    /**
     * 获取文件真实MIME类型(基于文件内容)
     * 
     * @param string $filePath
     * @return string|false
     */
    private static function getRealMimeType($filePath)
    {
        if (!file_exists($filePath)) {
            return false;
        }

        // 方法1: 使用finfo (推荐)
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $filePath);
            finfo_close($finfo);

            if ($mime) {
                return $mime;
            }
        }

        // 方法2: 使用mime_content_type (备用)
        if (function_exists('mime_content_type')) {
            $mime = mime_content_type($filePath);
            if ($mime) {
                return $mime;
            }
        }

        // 方法3: 通过文件头识别(最后的备用方案)
        return self::getMimeByFileHeader($filePath);
    }

    /**
     * 通过文件头(magic bytes)识别MIME类型
     * 
     * @param string $filePath
     * @return string|false
     */
    private static function getMimeByFileHeader($filePath)
    {
        $file = fopen($filePath, 'rb');
        if (!$file) {
            return false;
        }

        $header = fread($file, 12);
        fclose($file);

        // 常见文件头识别
        $signatures = [
            'ffd8ff' => 'image/jpeg',          // JPEG
            '89504e47' => 'image/png',          // PNG
            '47494638' => 'image/gif',          // GIF
            '25504446' => 'application/pdf',    // PDF
            '504b0304' => 'application/zip',    // ZIP
            '504b0506' => 'application/zip',    // ZIP (empty)
            'd0cf11e0' => 'application/msword', // DOC/XLS/PPT
            '52617221' => 'application/x-rar-compressed', // RAR
        ];

        $hex = bin2hex(substr($header, 0, 4));

        foreach ($signatures as $signature => $mime) {
            if (strpos($hex, $signature) === 0) {
                return $mime;
            }
        }

        return 'application/octet-stream';
    }

    /**
     * 检查文件大小是否合规
     * 
     * @param int $fileSize 文件大小(字节)
     * @param int $maxSize 最大大小(字节),默认8MB
     * @return array ['valid' => bool, 'message' => string]
     */
    public static function validateFileSize($fileSize, $maxSize = 8388608)
    {
        if ($fileSize > $maxSize) {
            $maxSizeMB = round($maxSize / 1024 / 1024, 2);
            $fileSizeMB = round($fileSize / 1024 / 1024, 2);

            return [
                'valid' => false,
                'message' => "文件大小({$fileSizeMB}MB)超过限制({$maxSizeMB}MB)"
            ];
        }

        return [
            'valid' => true,
            'message' => '文件大小验证通过'
        ];
    }
}
