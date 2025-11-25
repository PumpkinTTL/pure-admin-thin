<?php

namespace app\api\services;

use app\api\model\emailRecord;
use app\api\model\emailReceiver;
use app\api\model\users;
use think\facade\Db;
use think\facade\Log;
use think\facade\Request;

class EmailRecordService
{
    /**
     * 获取邮件记录列表
     * @param array $params 查询参数
     * @return \think\Paginator
     */
    public static function getList(array $params = [])
    {
        $pageSize = $params['page_size'] ?? 10;
        $page = $params['page'] ?? 1;

        $query = emailRecord::with(['sender', 'notice']);

        // 应用搜索条件
        if (!empty($params['title'])) {
            $query->where('title', 'like', '%' . $params['title'] . '%');
        }

        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('status', $params['status']);
        }

        if (isset($params['receiver_type']) && $params['receiver_type'] !== '') {
            $query->where('receiver_type', $params['receiver_type']);
        }

        if (!empty($params['sender_id'])) {
            $query->where('sender_id', $params['sender_id']);
        }

        if (!empty($params['notice_id'])) {
            $query->where('notice_id', $params['notice_id']);
        }

        // 时间范围查询
        if (!empty($params['start_time']) && !empty($params['end_time'])) {
            $query->whereBetweenTime('create_time', $params['start_time'], $params['end_time']);
        }

        // 软删除数据查询控制
        // include_deleted: 0=不包含(默认), 1=仅软删除, 2=包含所有
        $includeDeleted = $params['include_deleted'] ?? 0;
        if ($includeDeleted == 1) {
            // 仅查询软删除的数据
            $query->onlyTrashed();
        } elseif ($includeDeleted == 2) {
            // 包含软删除数据
            $query->withTrashed();
        }
        // 默认情况(0)不需要额外处理，会自动过滤软删除数据

        // 排序
        $query->order('create_time', 'desc');

        return $query->paginate([
            'list_rows' => $pageSize,
            'page' => $page,
            'query' => Request::get()
        ]);
    }

    /**
     * 获取邮件记录详情
     * @param int $id 记录ID
     * @return array
     */
    public static function getDetail(int $id): array
    {
        try {
            $record = emailRecord::with(['sender', 'notice'])
                ->withTrashed()
                ->find($id);

            if (!$record) {
                return [
                    'success' => false,
                    'message' => '记录不存在'
                ];
            }

            return [
                'success' => true,
                'data' => $record
            ];
        } catch (\Exception $e) {
            Log::error('获取邮件记录详情失败: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * 获取接收者列表
     * @param int $recordId 记录ID
     * @param array $params 查询参数
     * @return \think\Paginator
     */
    public static function getReceivers(int $recordId, array $params = [])
    {
        $pageSize = $params['page_size'] ?? 20;
        $page = $params['page'] ?? 1;

        $query = emailReceiver::with(['user'])
            ->where('record_id', $recordId);

        // 应用搜索条件
        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('status', $params['status']);
        }

        if (!empty($params['email'])) {
            $query->where('email', 'like', '%' . $params['email'] . '%');
        }

        // 排序
        $query->order('create_time', 'asc');

        return $query->paginate([
            'list_rows' => $pageSize,
            'page' => $page
        ]);
    }

    /**
     * 创建邮件记录并发送
     * @param array $data 邮件数据
     * @return array
     */
    public static function createAndSend(array $data): array
    {
        Db::startTrans();
        try {
            // 判断是否使用模板
            $useTemplate = !empty($data['template_id']);
            $template = null;
            $title = $data['title'] ?? '';
            $content = $data['content'] ?? '';

            if ($useTemplate) {
                // 获取模板信息
                $template = \app\api\model\EmailTemplate::find($data['template_id']);
                if (!$template) {
                    return ['success' => false, 'message' => '邮件模板不存在'];
                }
                if (!$template->is_active) {
                    return ['success' => false, 'message' => '邮件模板未启用'];
                }
                // 使用模板的标题
                $title = $template->subject;
                $content = $template->content;
            }

            // 创建邮件记录
            $record = emailRecord::create([
                'notice_id' => $data['notice_id'] ?? null,
                'sender_id' => $data['sender_id'],
                'title' => $title,
                'content' => $content,
                'receiver_type' => $data['receiver_type'],
                'status' => 1, // 发送中
                'send_time' => date('Y-m-d H:i:s')
            ]);

            // 准备发送目标
            $targets = self::prepareTargets($data);

            // 更新总数量
            $record->total_count = count($targets);
            $record->save();

            // 批量发送邮件
            if ($useTemplate) {
                // 使用模板发送（支持变量替换）
                $sendResult = self::sendBatchWithTemplate(
                    $targets,
                    $template,
                    $record->id
                );
            } else {
                // 普通文字发送
                $sendResult = EmailSendService::sendBatch(
                    $targets,
                    $title,
                    $content,
                    $record->id
                );
            }

            // 更新发送结果
            $record->success_count = $sendResult['success'];
            $record->failed_count = $sendResult['failed'];

            // 更新状态
            if ($sendResult['failed'] == 0) {
                $record->status = 2; // 发送完成
            } elseif ($sendResult['success'] == 0) {
                $record->status = 4; // 全部失败
            } else {
                $record->status = 3; // 部分失败
            }

            $record->save();

            Db::commit();

            return [
                'success' => true,
                'message' => '邮件发送完成',
                'data' => [
                    'record_id' => $record->id,
                    'total_count' => $record->total_count,
                    'success_count' => $record->success_count,
                    'failed_count' => $record->failed_count,
                    'status' => $record->status
                ]
            ];
        } catch (\Exception $e) {
            Db::rollback();
            Log::error('创建邮件记录失败: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * 准备发送目标
     * @param array $data 邮件数据
     * @return array
     */
    protected static function prepareTargets(array $data): array
    {
        $targets = [];

        switch ($data['receiver_type']) {
            case 1: // 全部用户
                $userEmails = EmailSendService::getAllUserEmails();
                foreach ($userEmails as $userId => $email) {
                    $targets[] = ['type' => 'user', 'id' => $userId];
                }
                break;

            case 2: // 指定多个用户
                if (!empty($data['receiver_ids'])) {
                    foreach ($data['receiver_ids'] as $userId) {
                        $targets[] = ['type' => 'user', 'id' => $userId];
                    }
                }
                break;

            case 3: // 用户组(角色)
                if (!empty($data['role_id'])) {
                    // 获取该角色下的所有用户
                    $roleUsers = Db::name('user_role')
                        ->where('role_id', $data['role_id'])
                        ->column('user_id');
                    foreach ($roleUsers as $userId) {
                        $targets[] = ['type' => 'user', 'id' => $userId];
                    }
                }
                break;

            case 4: // 指定邮箱
                if (!empty($data['receiver_emails'])) {
                    foreach ($data['receiver_emails'] as $email) {
                        $targets[] = ['type' => 'email', 'email' => $email];
                    }
                }
                break;
        }

        return $targets;
    }

    /**
     * 使用模板批量发送邮件（支持变量替换）
     * @param array $targets 发送目标
     * @param object $template 模板对象
     * @param int $recordId 记录ID
     * @return array
     */
    protected static function sendBatchWithTemplate(array $targets, $template, int $recordId): array
    {
        $successCount = 0;
        $failedCount = 0;

        foreach ($targets as $target) {
            try {
                // 获取接收者信息
                if ($target['type'] === 'user') {
                    $user = users::find($target['id']);
                    if (!$user || !$user->email) {
                        $failedCount++;
                        continue;
                    }
                    $email = $user->email;
                    // 准备变量数据
                    $variables = [
                        'username' => $user->username ?? '',
                        'email' => $user->email ?? '',
                        'nickname' => $user->nickname ?? $user->username ?? '',
                        'date' => date('Y-m-d'),
                        'year' => date('Y')
                    ];
                } else {
                    $email = $target['email'];
                    // 邮箱地址模式，使用默认变量
                    $variables = [
                        'username' => explode('@', $email)[0],
                        'email' => $email,
                        'nickname' => explode('@', $email)[0],
                        'date' => date('Y-m-d'),
                        'year' => date('Y')
                    ];
                }

                // 渲染模板
                $rendered = self::renderTemplate($template, $variables);

                // 发送邮件
                $result = EmailSendService::sendToEmail(
                    $email,
                    $rendered['subject'],
                    $rendered['content'],
                    $recordId
                );

                if ($result['success']) {
                    $successCount++;
                } else {
                    $failedCount++;
                }
            } catch (\Exception $e) {
                Log::error('发送模板邮件失败: ' . $e->getMessage());
                $failedCount++;
            }
        }

        return [
            'success' => $successCount,
            'failed' => $failedCount
        ];
    }

    /**
     * 渲染模板（替换变量）
     * @param object $template 模板对象
     * @param array $variables 变量数据
     * @return array
     */
    protected static function renderTemplate($template, array $variables): array
    {
        $subject = $template->subject;
        $content = $template->content;

        // 替换所有变量 {variable_name}
        foreach ($variables as $key => $value) {
            $subject = str_replace('{' . $key . '}', $value, $subject);
            $content = str_replace('{' . $key . '}', $value, $content);
        }

        return [
            'subject' => $subject,
            'content' => $content
        ];
    }

    /**
     * 重新发送失败的邮件
     * @param int $recordId 记录ID
     * @param array $receiverIds 指定接收者ID(可选)
     * @return array
     */
    public static function resendFailed(int $recordId, array $receiverIds = []): array
    {
        try {
            $record = emailRecord::find($recordId);
            if (!$record) {
                return ['success' => false, 'message' => '记录不存在'];
            }

            // 查询失败的接收者
            $query = emailReceiver::where('record_id', $recordId)
                ->where('status', 2);

            if (!empty($receiverIds)) {
                $query->whereIn('id', $receiverIds);
            }

            $failedReceivers = $query->select();

            if ($failedReceivers->isEmpty()) {
                return ['success' => false, 'message' => '没有失败的邮件'];
            }

            $successCount = 0;
            $failedCount = 0;

            foreach ($failedReceivers as $receiver) {
                $result = $receiver->user_id
                    ? EmailSendService::sendToUser($receiver->user_id, $record->title, $record->content, $recordId)
                    : EmailSendService::sendToEmail($receiver->email, $record->title, $record->content, $recordId);

                if ($result['success']) {
                    $successCount++;
                } else {
                    $failedCount++;
                }
            }

            // 更新记录统计
            $record->success_count += $successCount;
            $record->failed_count = emailReceiver::where('record_id', $recordId)->where('status', 2)->count();

            if ($record->failed_count == 0) {
                $record->status = 2; // 发送完成
            } else {
                $record->status = 3; // 部分失败
            }

            $record->save();

            return [
                'success' => true,
                'message' => '重新发送完成',
                'data' => [
                    'total_count' => count($failedReceivers),
                    'success_count' => $successCount,
                    'failed_count' => $failedCount
                ]
            ];
        } catch (\Exception $e) {
            Log::error('重新发送邮件失败: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * 删除邮件记录(软删除)
     * @param int $id 记录ID
     * @return array
     */
    public static function delete(int $id): array
    {
        try {
            $record = emailRecord::find($id);
            if (!$record) {
                return ['success' => false, 'message' => '记录不存在'];
            }

            $record->delete();

            return ['success' => true, 'message' => '删除成功'];
        } catch (\Exception $e) {
            Log::error('删除邮件记录失败: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * 批量删除
     * @param array $ids 记录ID数组
     * @return array
     */
    public static function batchDelete(array $ids): array
    {
        try {
            $count = emailRecord::whereIn('id', $ids)->delete();
            return [
                'success' => true,
                'message' => '批量删除成功',
                'data' => ['deleted_count' => $count]
            ];
        } catch (\Exception $e) {
            Log::error('批量删除邮件记录失败: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * 恢复邮件记录
     * @param int $id 记录ID
     * @return array
     */
    public static function restore(int $id): array
    {
        try {
            // 查找软删除的记录
            $record = emailRecord::onlyTrashed()->find($id);

            if (!$record) {
                return ['success' => false, 'message' => '记录不存在或未被删除'];
            }

            // 恢复记录
            $record->restore();

            return ['success' => true, 'message' => '恢复成功'];
        } catch (\Exception $e) {
            Log::error('恢复邮件记录失败: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * 获取统计数据
     * @param array $params 查询参数
     * @return array
     */
    public static function getStatistics(array $params = []): array
    {
        try {
            $query = emailRecord::withTrashed();

            // 时间范围
            if (!empty($params['start_time']) && !empty($params['end_time'])) {
                $query->whereBetweenTime('create_time', $params['start_time'], $params['end_time']);
            }

            $totalRecords = $query->count();
            $totalEmails = $query->sum('total_count');
            $successEmails = $query->sum('success_count');
            $failedEmails = $query->sum('failed_count');

            return [
                'success' => true,
                'data' => [
                    'total_records' => $totalRecords,
                    'total_emails' => $totalEmails,
                    'success_emails' => $successEmails,
                    'failed_emails' => $failedEmails,
                    'success_rate' => $totalEmails > 0 ? round(($successEmails / $totalEmails) * 100, 2) : 0
                ]
            ];
        } catch (\Exception $e) {
            Log::error('获取统计数据失败: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
