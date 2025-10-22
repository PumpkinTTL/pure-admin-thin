<?php

namespace app\api\middleware;

use app\api\model\File;
use think\facade\Log;

/**
 * 文件访问权限中间件
 */
class FileAccessMiddleware
{
    /**
     * 需要权限验证的方法
     */
    private $protectedActions = [
        'detail',       // 查看详情
        'update',       // 更新文件
        'delete',       // 软删除
        'forceDelete',  // 永久删除
        'restore',      // 恢复文件
        'content',      // 读取内容
    ];

    public function handle($request, \Closure $next)
    {
        // 获取当前操作方法
        $action = $request->action();
        
        // 如果不是需要验证的操作，直接放行
        if (!in_array($action, $this->protectedActions)) {
            return $next($request);
        }

        // 获取文件ID
        $fileId = $request->param('file_id') ?? $request->post('file_id');
        
        if (!$fileId) {
            return json([
                'code' => 400,
                'message' => '缺少文件ID参数'
            ]);
        }

        // 查询文件记录
        $file = File::withTrashed()->find($fileId);
        
        if (!$file) {
            return json([
                'code' => 404,
                'message' => '文件不存在'
            ]);
        }

        // 获取当前登录用户ID (从JWT或session)
        $currentUserId = $request->JWTUid ?? 0;

        // 管理员可以访问所有文件 (假设user_id为1是管理员)
        if ($currentUserId === 1) {
            return $next($request);
        }

        // 检查文件所有权
        // 只有文件所有者才能操作(删除、修改等敏感操作)
        $sensitiveActions = ['update', 'delete', 'forceDelete'];
        
        if (in_array($action, $sensitiveActions)) {
            if ($file->user_id != $currentUserId && $currentUserId !== 0) {
                Log::warning('文件访问权限被拒绝', [
                    'file_id' => $fileId,
                    'user_id' => $currentUserId,
                    'file_owner' => $file->user_id,
                    'action' => $action,
                    'ip' => $request->ip()
                ]);
                
                return json([
                    'code' => 403,
                    'message' => '无权限操作此文件'
                ]);
            }
        }

        // 检查文件是否已被删除 (除了restore和forceDelete操作)
        if ($file->delete_time && !in_array($action, ['restore', 'forceDelete'])) {
            return json([
                'code' => 410,
                'message' => '文件已被删除，无法访问'
            ]);
        }

        // 记录访问日志
        Log::info('文件访问', [
            'file_id' => $fileId,
            'user_id' => $currentUserId,
            'action' => $action,
            'file_name' => $file->original_name
        ]);

        // 通过验证，继续执行
        return $next($request);
    }
}
