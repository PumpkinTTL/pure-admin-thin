<?php

namespace app\api\services;

use app\api\model\File;
use think\facade\Request;
use think\facade\Db;
use think\facade\Log;
use think\facade\Filesystem;
use utils\FileUtil;
use app\api\services\LogService;

class FileService
{
    /**
     * 获取文件列表（支持分页和条件查询）
     * @param array $params 查询参数
     * @return \think\Paginator
     */
    public static function getFileList(array $params = [])
    {
        // 默认每页100条
        $pageSize = $params['page_size'] ?? 100;
        $page = $params['page'] ?? 1;

        // 构建基础查询
        $query = File::with(['user' => function ($query) {
            $query->field(['id', 'username', 'avatar']);
        }]);

        // 添加动态查询条件
        $query = self::buildDynamicConditions($query, $params);

        // 根据status参数处理软删除查询
        if (!isset($params['status']) || $params['status'] === 'active') {
            // 查询未删除的数据（默认行为）
        } elseif ($params['status'] === 'deleted') {
            // 只查询已删除的数据
            $query->onlyTrashed();
        } else {
            // 查询所有数据（包括已删除和未删除的）
            $query->withTrashed();
        }

        // 分页查询
        return $query->paginate([
            'list_rows' => $pageSize,
            'page' => $page,
            'query' => Request::get()
        ]);
    }

    /**
     * 构建动态查询条件
     * @param \think\db\Query $query 查询构建器
     * @param array $params 查询参数
     * @return \think\db\Query
     */
    protected static function buildDynamicConditions($query, $params)
    {
        // 按原始文件名模糊查询
        if (!empty($params['original_name'])) {
            $query->where('original_name', 'like', '%' . $params['original_name'] . '%');
        }

        // 按文件类型查询
        if (!empty($params['file_type'])) {
            $query->where('file_type', 'like', '%' . $params['file_type'] . '%');
        }

        // 按文件扩展名查询
        if (!empty($params['file_extension'])) {
            $query->where('file_extension', $params['file_extension']);
        }

        // 按上传用户ID查询
        if (!empty($params['user_id'])) {
            $query->where('user_id', $params['user_id']);
        }

        // 按文件哈希查询
        if (!empty($params['file_hash'])) {
            $query->where('file_hash', $params['file_hash']);
        }

        // 按存储类型查询
        if (isset($params['storage_type']) && $params['storage_type'] !== '') {
            $query->where('storage_type', (int)$params['storage_type']);
        }

        // 按设备指纹查询
        if (!empty($params['device_fingerprint'])) {
            $query->where('device_fingerprint', $params['device_fingerprint']);
        }

        // 文件大小范围查询
        if (!empty($params['min_size'])) {
            $query->where('file_size', '>=', (int)$params['min_size']);
        }
        if (!empty($params['max_size'])) {
            $query->where('file_size', '<=', (int)$params['max_size']);
        }

        // 创建时间范围查询
        if (!empty($params['start_date'])) {
            $query->where('create_time', '>=', $params['start_date']);
        }
        if (!empty($params['end_date'])) {
            $query->where('create_time', '<=', $params['end_date']);
        }

        // 排序处理
        if (!empty($params['sort_field']) && !empty($params['sort_order'])) {
            $query->order($params['sort_field'], $params['sort_order']);
        } else {
            // 默认排序：按创建时间倒序
            $query->order('create_time', 'desc');
        }

        return $query;
    }

    /**
     * 上传文件
     * @param array $fileInfo 文件信息
     * @param array $metadata 元数据
     * @return array
     */
    public static function uploadFile($fileInfo, array $metadata = []): array
    {
        try {
            Db::startTrans();
            
            // 生成唯一文件存储名
            $storeName = md5(uniqid(microtime(true), true));
            
            // 获取文件扩展名
            $extension = $fileInfo->getOriginalExtension();
            $storeNameWithExt = $storeName . '.' . $extension;
            
            // 计算文件哈希值
            $filePath = $fileInfo->getPathname();
            $fileHash = md5_file($filePath);
            
            // 检查是否已有相同哈希值的文件
            $existingFile = File::where([
                'file_hash' => $fileHash,
                'storage_type' => $metadata['storage_type'] ?? 0,
                'bucket_name' => $metadata['bucket_name'] ?? null
            ])->find();
            
            if ($existingFile) {
                // 文件已存在，返回已有文件信息
                Db::rollback();
                return [
                    'success' => true,
                    'message' => '文件已存在',
                    'data' => $existingFile,
                    'duplicate' => true
                ];
            }
            
            // 确定存储路径
            $savePath = 'uploads/' . date('Ymd');
            
            // 存储文件（本地存储）
            if (($metadata['storage_type'] ?? 0) == 0) {
                // 使用框架提供的文件系统存储文件
                $savedPath = Filesystem::disk('public')->putFile($savePath, $fileInfo, function() use ($storeNameWithExt) {
                    return $storeNameWithExt;
                });
                
                $fullPath = $savePath . '/' . $storeNameWithExt;
            } else {
                // 第三方云存储逻辑（实际使用时需要根据不同的云存储服务实现）
                // 这里仅做演示，实际应根据storage_type调用不同的上传SDK
                $fullPath = $savePath . '/' . $storeNameWithExt;
                // 云存储上传逻辑...
            }
            
            // 创建文件记录
            $fileData = [
                'user_id' => $metadata['user_id'] ?? 0,
                'original_name' => $fileInfo->getOriginalName(),
                'store_name' => $storeNameWithExt,
                'file_path' => $fullPath,
                'file_size' => $fileInfo->getSize(),
                'file_type' => $fileInfo->getMime(),
                'file_extension' => $extension,
                'file_hash' => $fileHash,
                'hash_algorithm' => $metadata['hash_algorithm'] ?? 'MD5',
                'device_fingerprint' => $metadata['device_fingerprint'] ?? null,
                'storage_type' => $metadata['storage_type'] ?? 0,
                'bucket_name' => $metadata['bucket_name'] ?? null
            ];
            
            $file = File::create($fileData);
            
            // 提交事务
            Db::commit();
            
            return [
                'success' => true,
                'message' => '文件上传成功',
                'data' => $file
            ];
        } catch (\Throwable $e) {
            // 回滚事务
            Db::rollback();
            
            // 记录错误日志
            Log::error('文件上传失败：' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'original_name' => $fileInfo->getOriginalName() ?? 'unknown'
            ]);
            
            return [
                'success' => false,
                'message' => '文件上传失败：' . $e->getMessage(),
                'error_code' => 500
            ];
        }
    }

    /**
     * 根据ID获取文件详情
     * @param int $fileId 文件ID
     * @return array
     */
    public static function getFileById(int $fileId): array
    {
        try {
            $file = File::with(['user' => function($query) {
                $query->field(['id', 'username', 'avatar']);
            }])->find($fileId);
            
            if (!$file) {
                throw new \Exception('文件不存在');
            }
            
            return [
                'success' => true,
                'message' => '获取成功',
                'data' => $file
            ];
        } catch (\Throwable $e) {
            Log::error('获取文件详情失败：' . $e->getMessage(), [
                'file_id' => $fileId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => '获取文件详情失败：' . $e->getMessage(),
                'error_code' => 500
            ];
        }
    }

    /**
     * 软删除文件
     * @param int $fileId 文件ID
     * @return array
     */
    public static function deleteFile(int $fileId): array
    {
        try {
            $file = File::find($fileId);
            
            if (!$file) {
                throw new \Exception('文件不存在');
            }
            
            // 执行软删除
            $file->delete();
            
            return [
                'success' => true,
                'message' => '文件删除成功'
            ];
        } catch (\Throwable $e) {
            Log::error('文件删除失败：' . $e->getMessage(), [
                'file_id' => $fileId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => '文件删除失败：' . $e->getMessage(),
                'error_code' => 500
            ];
        }
    }

    /**
     * 恢复被软删除的文件
     * @param int $fileId 文件ID
     * @return array
     */
    public static function restoreFile(int $fileId): array
    {
        try {
            // 查找已删除的文件
            $file = File::onlyTrashed()->find($fileId);
            
            if (!$file) {
                throw new \Exception('文件不存在或未被删除');
            }
            
            // 恢复文件
            $file->restore();
            
            return [
                'success' => true,
                'message' => '文件恢复成功'
            ];
        } catch (\Throwable $e) {
            Log::error('文件恢复失败：' . $e->getMessage(), [
                'file_id' => $fileId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => '文件恢复失败：' . $e->getMessage(),
                'error_code' => 500
            ];
        }
    }

    /**
     * 永久删除文件
     * @param int $fileId 文件ID
     * @param bool $deletePhysicalFile 是否同时删除物理文件
     * @return array
     */
    public static function forceDeleteFile(int $fileId, bool $deletePhysicalFile = false): array
    {
        try {
            // 可以同时查询正常和已删除的文件
            $file = File::withTrashed()->find($fileId);
            
            if (!$file) {
                throw new \Exception('文件不存在');
            }
            
            // 如果需要删除物理文件
            if ($deletePhysicalFile) {
                // 使用FileUtil工具类删除物理文件
                $deleteResult = FileUtil::deleteFile($file['http_url']);

                // 记录物理文件删除结果
                LogService::log('物理文件删除', [
                    'file_id' => $fileId,
                    'http_url' => $file['http_url'],
                    'delete_result' => $deleteResult
                ], 'info');
            }
            
            // 永久删除数据库记录
            $file->force()->delete();
            
            return [
                'success' => true,
                'message' => '文件已永久删除'
            ];
        } catch (\Throwable $e) {
            Log::error('永久删除文件失败：' . $e->getMessage(), [
                'file_id' => $fileId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => '永久删除文件失败：' . $e->getMessage(),
                'error_code' => 500
            ];
        }
    }

    /**
     * 批量删除文件
     * @param array $fileIds 文件ID数组
     * @param bool $isForce 是否强制删除（true=物理删除+SQL删除，false=软删除）
     * @return array
     */
    public static function batchDeleteFiles(array $fileIds, bool $isForce = false): array
    {
        try {
            Db::startTrans();
            
            $successCount = 0;
            $failCount = 0;
            $failIds = [];
            
            foreach ($fileIds as $fileId) {
                try {
                    if ($isForce) {
                        // 强制删除：物理删除文件 + 删除SQL记录
                        $result = self::forceDeleteFile($fileId, true);
                    } else {
                        // 软删除：仅标记删除
                        $result = self::deleteFile($fileId);
                    }

                    if ($result['success']) {
                        $successCount++;
                    } else {
                        $failCount++;
                        $failIds[] = $fileId;
                    }
                } catch (\Throwable $e) {
                    $failCount++;
                    $failIds[] = $fileId;
                    Log::error('批量删除文件失败：' . $e->getMessage(), [
                        'file_id' => $fileId,
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }
            
            Db::commit();
            
            return [
                'success' => true,
                'message' => "批量删除完成，成功：{$successCount}，失败：{$failCount}",
                'fail_ids' => $failIds
            ];
        } catch (\Throwable $e) {
            Db::rollback();
            
            Log::error('批量删除文件失败：' . $e->getMessage(), [
                'file_ids' => $fileIds,
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => '批量删除文件失败：' . $e->getMessage(),
                'error_code' => 500
            ];
        }
    }

    /**
     * 获取文件统计信息
     * @return array
     */
    public static function getFileStats(): array
    {
        try {
            // 文件总数
            $totalCount = File::count();
            
            // 已删除文件数
            $deletedCount = File::onlyTrashed()->count();
            
            // 文件总大小
            $totalSize = File::sum('file_size');
            
            // 按存储类型统计
            $storageTypeStats = File::group('storage_type')
                ->field('storage_type, count(*) as count')
                ->select()
                ->toArray();
            
            // 格式化存储类型统计
            $formattedStorageStats = [];
            $storageTypes = [
                0 => '本地存储',
                1 => '阿里云OSS',
                2 => '七牛云',
                3 => '腾讯云COS'
            ];
            
            foreach ($storageTypeStats as $stat) {
                $type = $stat['storage_type'];
                $formattedStorageStats[] = [
                    'type' => $type,
                    'type_name' => $storageTypes[$type] ?? '未知',
                    'count' => $stat['count']
                ];
            }
            
            // 按文件类型统计（取前10种）
            $fileTypeStats = Db::name('bl_files')
                ->field('file_extension, count(*) as count')
                ->group('file_extension')
                ->order('count desc')
                ->limit(10)
                ->select()
                ->toArray();
            
            return [
                'success' => true,
                'data' => [
                    'total_count' => $totalCount,
                    'active_count' => $totalCount - $deletedCount,
                    'deleted_count' => $deletedCount,
                    'total_size' => $totalSize,
                    'total_size_format' => self::formatFileSize($totalSize),
                    'storage_type_stats' => $formattedStorageStats,
                    'file_type_stats' => $fileTypeStats
                ],
                'message' => '获取文件统计信息成功'
            ];
        } catch (\Throwable $e) {
            Log::error('获取文件统计信息失败：' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => '获取文件统计信息失败：' . $e->getMessage(),
                'error_code' => 500
            ];
        }
    }

    /**
     * 格式化文件大小
     * @param int $size 字节数
     * @return string 格式化后的大小
     */
    private static function formatFileSize(int $size): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }
        return round($size, 2) . ' ' . $units[$i];
    }
} 