<?php

namespace app\api\controller\v1;

use app\api\model\File;
use think\response\File as FileResponse;

class FileProxy
{
    /**
     * 代理访问文件（解决CORS问题）
     * @return mixed
     */
    public function proxy()
    {
        try {
            $fileId = request()->param('file_id');
            
            if (!$fileId) {
                return json(['code' => 400, 'message' => '文件ID不能为空']);
            }
            
            // 查询文件记录
            $fileRecord = File::find($fileId);
            if (!$fileRecord) {
                return json(['code' => 404, 'message' => '文件不存在']);
            }
            
            // 获取环境配置
            $env = $this->getEnvironment();
            $config = [
                'development' => [
                    'base_path' => 'D:/upload/'
                ],
                'production' => [
                    'base_path' => '/home/upload/'
                ]
            ];
            $basePath = $config[$env]['base_path'];
            
            // 构建完整文件路径
            $filePath = $fileRecord->file_path;
            if (strpos($filePath, 'upload/') === 0) {
                $filePath = substr($filePath, 7);
            }
            $fullPath = $basePath . $filePath;
            
            // 检查文件是否存在
            if (!file_exists($fullPath)) {
                return json(['code' => 404, 'message' => '文件不存在']);
            }
            
            // 设置CORS头
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, OPTIONS');
            header('Access-Control-Allow-Headers: *');
            
            // 返回文件
            return download($fullPath, $fileRecord->original_name);
            
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => '文件访问失败：' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * 获取当前运行环境
     * @return string
     */
    private function getEnvironment()
    {
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        return $isWindows ? 'development' : 'production';
    }
}
