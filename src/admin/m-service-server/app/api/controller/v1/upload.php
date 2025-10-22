<?php

namespace app\api\controller\v1;

use app\api\model\File;
use think\facade\Request;
use think\facade\Db;
use think\facade\Env;
use think\file\UploadedFile;
use utils\FileUtil;
use app\api\services\LogService;
use app\api\services\FileSecurityService;
use app\api\config\FileConfig;

class Upload
{

    public function uploadFile(): \think\response\Json
    {
        try {
            // 获取上传文件（确保总是返回数组）
            $files = request()->file();
            if (empty($files)) {
                return json(['code' => 400, 'msg' => '未选择上传文件']);
            }

            // 如果是单个文件上传，转换为数组
            if (!is_array($files)) {
                $files = [$files];
            }

            // 验证文件数量
            $maxCount = FileConfig::getMaxCount();
            if (count($files) > $maxCount) {
                return json(['code' => 400, 'msg' => "最多上传{$maxCount}个文件"]);
            }

            // 获取上传用户ID
            $userId = request()->param('user_id', 0);
            
            // 获取存储类型和其他元数据
            $storageType = request()->param('storage_type', 0);
            $bucketName = request()->param('bucket_name', '');
            $deviceFingerprint = request()->param('device_fingerprint', '');
            $remark = request()->param('remark', '未备注信息');
            
            // 从配置类获取存储配置
            $storageConfig = FileConfig::getStorageConfig();
            $basePath = $storageConfig['base_path'];
            $baseUrl = $storageConfig['base_url'];
            $maxSize = FileConfig::getMaxSize();

            $result = [];
            
            // 开始事务
            Db::startTrans();
            
            foreach ($files as $key => $file) {
                // 检查文件是否有效
                if (!($file instanceof UploadedFile) || !$file->isValid()) {
                    continue; // 跳过无效文件
                }

                // 验证单个文件大小
                $fileSize = $file->getSize();
                $sizeValidation = FileSecurityService::validateFileSize($fileSize, $maxSize);
                if (!$sizeValidation['valid']) {
                    throw new \Exception($sizeValidation['message']);
                }

                // 获取文件类型分类和扩展名
                $ext = $file->extension();
                $declaredMimeType = $file->getMime();
                
                // 安全验证: MIME类型和扩展名匹配检查
                $tempPath = $file->getPathname();
                $validation = FileSecurityService::validateFile($tempPath, $ext, $declaredMimeType);
                
                if (!$validation['valid']) {
                    LogService::log('文件验证失败', [
                        'file_name' => $file->getOriginalName(),
                        'extension' => $ext,
                        'declared_mime' => $declaredMimeType,
                        'reason' => $validation['message']
                    ], 'warning');
                    
                    throw new \Exception($validation['message']);
                }
                
                // 使用验证后的真实MIME类型
                $realMimeType = $validation['mime'];
                
                // 获取文件类型分类(简短字符串,避免过长)
                $fileType = $this->getFileType($ext);
                
                // 计算文件哈希值
                $fileHash = md5_file($tempPath);
                
                // 检查是否已有相同哈希值的文件(避免重复存储)
                $existingFile = File::where([
                    'file_hash' => $fileHash,
                    'storage_type' => $storageType,
                    'bucket_name' => $bucketName
                ])->find();
                
                if ($existingFile) {
                    // 文件已存在，直接复用现有记录
                    $fileRecord = $existingFile;
                    $isDuplicate = true;
                    
                    // 构建响应数据
                    $resultItem = [
                        'file_id' => $fileRecord->file_id,
                        'original_name' => $file->getOriginalName(),
                        'save_path' => $fileRecord->file_path,
                        'file_type' => $fileType,
                        'mime_type' => $realMimeType,
                        'size' => $fileSize,
                        'url' => $fileRecord->http_url,
                        'is_duplicate' => $isDuplicate
                    ];
                    
                    $result[] = $resultItem;
                    continue; // 跳过后续的文件保存步骤
                }

                // 生成存储路径
                $datePath = date('Ymd');
                $saveDir = $basePath . "{$datePath}/{$fileType}";

                // 创建目录（如果不存在）
                if (!is_dir($saveDir)) {
                    mkdir($saveDir, 0777, true);
                }

                // 生成随机文件名
                $newName = md5(uniqid()) . '.' . $ext;
                $relativePath = "upload/{$datePath}/{$fileType}/{$newName}";
                $httpUrl = $baseUrl . "{$datePath}/{$fileType}/{$newName}";

                // 移动文件到目标位置
                $fileInfo = $file->move($saveDir, $newName);
                if (!$fileInfo) {
                    throw new \Exception('文件保存失败');
                }
                
                // 准备写入file表的数据
                $fileData = [
                    'user_id' => $userId,
                    'original_name' => $file->getOriginalName(),
                    'store_name' => $newName,
                    'file_path' => $relativePath,
                    'file_size' => $fileSize,
                    'file_type' => $fileType,
                    'file_extension' => $ext,
                    'file_hash' => $fileHash,
                    'hash_algorithm' => 'MD5',
                    'device_fingerprint' => $deviceFingerprint,
                    'storage_type' => $storageType,
                    'bucket_name' => $bucketName,
                    'http_url' => $httpUrl,
                    'remark' => $remark
                ];
                
                // 创建新的文件记录
                $fileRecord = File::create($fileData);
                $isDuplicate = false;
                
                // 构建响应数据
                $resultItem = [
                    'file_id' => $fileRecord->file_id,
                    'original_name' => $file->getOriginalName(),
                    'save_path' => $relativePath,
                    'file_type' => $fileType,
                    'mime_type' => $realMimeType,
                    'size' => $fileSize,
                    'url' => $fileRecord->http_url,
                    'is_duplicate' => $isDuplicate
                ];
                
                $result[] = $resultItem;
            }

            if (empty($result)) {
                Db::rollback();
                return json(['code' => 400, 'msg' => '没有有效的文件被上传']);
            }
            
            // 提交事务
            Db::commit();

            return json([
                'code' => 200,
                'msg' => '上传成功',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            
            return json([
                'code' => 500,
                'msg' => '上传失败: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * 获取文件类型
     * @param string $ext
     * @return string
     */
    private function getFileType($ext)
    {
        $ext = strtolower($ext);
        $allowedTypes = FileConfig::getAllowedTypes();
        
        foreach ($allowedTypes as $type => $exts) {
            if (in_array($ext, $exts)) {
                return $type;
            }
        }
        return 'other';
    }

    /**
     * 批量删除文件
     * @return \think\response\Json
     */
    public function deleteFiles(): \think\response\Json
    {
        try {
            // 获取请求参数
            $input = request()->getContent();
            $data = json_decode($input, true);

            // 验证参数
            if (!isset($data['urls']) || !is_array($data['urls'])) {
                return json(['code' => 400, 'msg' => '参数错误：urls必须是数组']);
            }

            $urls = $data['urls'];

            // 验证URL数组不能为空
            if (empty($urls)) {
                return json(['code' => 400, 'msg' => '删除文件列表不能为空']);
            }

            // 记录操作日志
            LogService::log('开始批量删除文件', [
                'urls' => $urls,
                'count' => count($urls),
                'user_id' => request()->JWTUid ?? 0
            ], 'info');

            // 开始事务
            Db::startTrans();

            $successCount = 0;
            $failedCount = 0;
            $results = [];

            foreach ($urls as $url) {
                try {
                    // 根据URL查找数据库记录
                    $fileRecord = File::where('http_url', $url)->find();

                    if (!$fileRecord) {
                        // 数据库中没有记录，但仍尝试删除物理文件
                        $physicalDeleted = FileUtil::deleteFile($url);
                        $results[] = [
                            'url' => $url,
                            'status' => 'warning',
                            'message' => '数据库中未找到记录，物理文件删除' . ($physicalDeleted ? '成功' : '失败')
                        ];
                        if ($physicalDeleted) {
                            $successCount++;
                        } else {
                            $failedCount++;
                        }
                        continue;
                    }

                    // 删除物理文件
                    $physicalDeleted = FileUtil::deleteFile($url);

                    // 删除数据库记录（软删除）
                    $dbDeleted = $fileRecord->delete();

                    if ($physicalDeleted && $dbDeleted) {
                        $successCount++;
                        $results[] = [
                            'url' => $url,
                            'status' => 'success',
                            'message' => '删除成功'
                        ];

                        // 记录成功日志
                        LogService::log('文件删除成功', [
                            'file_id' => $fileRecord->file_id,
                            'url' => $url,
                            'original_name' => $fileRecord->original_name
                        ], 'info');
                    } else {
                        $failedCount++;
                        $results[] = [
                            'url' => $url,
                            'status' => 'error',
                            'message' => '删除失败：' . (!$physicalDeleted ? '物理文件删除失败' : '数据库记录删除失败')
                        ];

                        // 记录失败日志
                        LogService::error('文件删除失败', [
                            'file_id' => $fileRecord->file_id ?? null,
                            'url' => $url,
                            'physical_deleted' => $physicalDeleted,
                            'db_deleted' => $dbDeleted
                        ]);
                    }

                } catch (\Exception $e) {
                    $failedCount++;
                    $results[] = [
                        'url' => $url,
                        'status' => 'error',
                        'message' => '删除异常：' . $e->getMessage()
                    ];

                    // 记录异常日志
                    LogService::error($e, [
                        'msg' => '批量删除文件时发生异常',
                        'url' => $url
                    ]);
                }
            }

            // 提交事务
            Db::commit();

            // 记录操作结果日志
            LogService::log('批量删除文件完成', [
                'total' => count($urls),
                'success' => $successCount,
                'failed' => $failedCount
            ], 'info');

            return json([
                'code' => 200,
                'msg' => "删除完成，成功：{$successCount}个，失败：{$failedCount}个",
                'data' => [
                    'total' => count($urls),
                    'success_count' => $successCount,
                    'failed_count' => $failedCount,
                    'details' => $results
                ]
            ]);

        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();

            // 记录异常日志
            LogService::error($e, ['msg' => '批量删除文件发生异常']);

            return json([
                'code' => 500,
                'msg' => '批量删除失败：' . $e->getMessage()
            ]);
        }
    }
}