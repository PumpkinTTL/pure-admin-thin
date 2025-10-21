<?php

namespace app\api\controller\v1;

use app\api\services\FileService;
use app\BaseController;
use think\response\Json;

class File extends BaseController
{
    /**
     * 获取文件列表
     * @return Json
     */
    public function getFileList(): Json
    {
        $params = request()->param();
        $data = FileService::getFileList($params);
        return json(['code' => 200, 'data' => $data]);
    }

    /**
     * 获取文件详情
     * @return Json
     */
    public function getFileById(): Json
    {
        $fileId = request()->param('file_id');
        
        if (!$fileId || !is_numeric($fileId)) {
            return json(['code' => 400, 'message' => '无效的文件ID']);
        }

        $result = FileService::getFileById((int)$fileId);
        
        return json([
            'code' => $result['success'] ? 200 : 500,
            'data' => $result['data'] ?? null,
            'message' => $result['message']
        ]);
    }

    /**
     * 删除文件（软删除）
     * @return Json
     */
    public function deleteFile(): Json
    {
        $fileId = request()->param('file_id');
        
        if (!$fileId || !is_numeric($fileId)) {
            return json(['code' => 400, 'message' => '无效的文件ID']);
        }

        $result = FileService::deleteFile((int)$fileId);
        
        return json([
            'code' => $result['success'] ? 200 : 500,
            'message' => $result['message']
        ]);
    }

    /**
     * 恢复被删除的文件
     * @return Json
     */
    public function restoreFile(): Json
    {
        $fileId = request()->param('file_id');
        
        if (!$fileId || !is_numeric($fileId)) {
            return json(['code' => 400, 'message' => '无效的文件ID']);
        }

        $result = FileService::restoreFile((int)$fileId);
        
        return json([
            'code' => $result['success'] ? 200 : 500,
            'message' => $result['message']
        ]);
    }

    /**
     * 永久删除文件
     * @return Json
     */
    public function forceDeleteFile(): Json
    {
        $fileId = request()->param('file_id');

        if (!$fileId || !is_numeric($fileId)) {
            return json(['code' => 400, 'message' => '无效的文件ID']);
        }

        // 永久删除默认删除物理文件
        $result = FileService::forceDeleteFile((int)$fileId, true);

        return json([
            'code' => $result['success'] ? 200 : 500,
            'message' => $result['message']
        ]);
    }

    /**
     * 批量删除文件
     * @return Json
     */
    public function batchDeleteFiles(): Json
    {
        try {
            // 获取JSON请求体
            $input = request()->getContent();
            $data = json_decode($input, true);

            // 验证参数
            if (!isset($data['file_ids']) || !is_array($data['file_ids'])) {
                return json(['code' => 400, 'message' => '请提供要删除的文件ID列表']);
            }

            $fileIds = $data['file_ids'];
            $isForce = (bool)($data['is_force'] ?? false);

            // 验证文件ID数组不能为空
            if (empty($fileIds)) {
                return json(['code' => 400, 'message' => '文件ID列表不能为空']);
            }

            $result = FileService::batchDeleteFiles($fileIds, $isForce);

            return json([
                'code' => $result['success'] ? 200 : 500,
                'message' => $result['message'],
                'data' => [
                    'fail_ids' => $result['fail_ids'] ?? []
                ]
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => '批量删除失败：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 获取文件统计信息
     * @return Json
     */
    public function getFileStats(): Json
    {
        $result = FileService::getFileStats();
        
        return json([
            'code' => $result['success'] ? 200 : 500,
            'data' => $result['data'] ?? null,
            'message' => $result['message']
        ]);
    }

    /**
     * 更新文件信息
     * @return Json
     */
    public function updateFile(): Json
    {
        try {
            // 获取JSON请求体
            $input = request()->getContent();
            $data = json_decode($input, true);

            // 验证参数
            if (!isset($data['file_id']) || !is_numeric($data['file_id'])) {
                return json(['code' => 400, 'message' => '无效的文件ID']);
            }

            $fileId = (int)$data['file_id'];
            $updateData = [];

            // 获取可更新的字段
            if (isset($data['original_name'])) {
                $updateData['original_name'] = $data['original_name'];
            }
            if (isset($data['remark'])) {
                $updateData['remark'] = $data['remark'];
            }

            if (empty($updateData)) {
                return json(['code' => 400, 'message' => '没有提供要更新的字段']);
            }

            $result = FileService::updateFile($fileId, $updateData);

            return json([
                'code' => $result['success'] ? 200 : 500,
                'message' => $result['message']
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => '更新失败：' . $e->getMessage()
            ]);
        }
    }
}
