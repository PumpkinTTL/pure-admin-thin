<?php

namespace app\api\services;

use app\api\model\users;
use app\api\model\emailReceiver;
use think\facade\Log;
use utils\EmailUtil;

class EmailSendService
{
    /**
     * 发送邮件给单个用户
     * @param int $userId 用户ID
     * @param string $title 邮件标题
     * @param string $content 邮件内容
     * @param int $recordId 邮件记录ID
     * @return array
     */
    public static function sendToUser(int $userId, string $title, string $content, int $recordId): array
    {
        try {
            // 获取用户信息
            $user = users::where('id', $userId)->find();
            if (!$user) {
                return [
                    'success' => false,
                    'message' => '用户不存在',
                    'user_id' => $userId
                ];
            }

            if (empty($user->email)) {
                return [
                    'success' => false,
                    'message' => '用户邮箱为空',
                    'user_id' => $userId
                ];
            }

            // 创建接收者记录
            $receiver = emailReceiver::create([
                'record_id' => $recordId,
                'user_id' => $userId,
                'email' => $user->email,
                'status' => 0 // 待发送
            ]);

            // 生成HTML邮件内容
            $htmlContent = self::generateEmailHtml($content, ['title' => $title]);

            // 发送邮件
            $result = EmailUtil::sendMail($user->email, $title, $htmlContent);

            // 更新接收者状态
            $receiver->status = $result ? 1 : 2;
            $receiver->send_time = date('Y-m-d H:i:s');
            if (!$result) {
                $receiver->error_msg = '邮件发送失败';
            }
            $receiver->save();

            return [
                'success' => $result,
                'message' => $result ? '发送成功' : '发送失败',
                'user_id' => $userId,
                'email' => $user->email,
                'receiver_id' => $receiver->id
            ];
        } catch (\Exception $e) {
            Log::error('发送邮件失败: ' . $e->getMessage());

            // 如果接收者记录已创建，更新为失败状态
            if (isset($receiver) && $receiver) {
                $receiver->status = 2;
                $receiver->error_msg = $e->getMessage();
                $receiver->send_time = date('Y-m-d H:i:s');
                $receiver->save();
            }

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'user_id' => $userId
            ];
        }
    }

    /**
     * 发送邮件到指定邮箱
     * @param string $email 邮箱地址
     * @param string $title 邮件标题
     * @param string $content 邮件内容
     * @param int $recordId 邮件记录ID
     * @return array
     */
    public static function sendToEmail(string $email, string $title, string $content, int $recordId): array
    {
        try {
            // 创建接收者记录
            $receiver = emailReceiver::create([
                'record_id' => $recordId,
                'user_id' => null, // 外部邮箱
                'email' => $email,
                'status' => 0 // 待发送
            ]);

            // 生成HTML邮件内容
            $htmlContent = self::generateEmailHtml($content, ['title' => $title]);

            // 发送邮件
            $result = EmailUtil::sendMail($email, $title, $htmlContent);

            // 更新接收者状态
            $receiver->status = $result ? 1 : 2;
            $receiver->send_time = date('Y-m-d H:i:s');
            if (!$result) {
                $receiver->error_msg = '邮件发送失败';
            }
            $receiver->save();

            return [
                'success' => $result,
                'message' => $result ? '发送成功' : '发送失败',
                'email' => $email,
                'receiver_id' => $receiver->id
            ];
        } catch (\Exception $e) {
            Log::error('发送邮件失败: ' . $e->getMessage());

            // 如果接收者记录已创建，更新为失败状态
            if (isset($receiver) && $receiver) {
                $receiver->status = 2;
                $receiver->error_msg = $e->getMessage();
                $receiver->send_time = date('Y-m-d H:i:s');
                $receiver->save();
            }

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'email' => $email
            ];
        }
    }

    /**
     * 批量发送邮件
     * @param array $targets 目标列表 [['type' => 'user', 'id' => 1], ['type' => 'email', 'email' => 'xxx@xx.com']]
     * @param string $title 邮件标题
     * @param string $content 邮件内容
     * @param int $recordId 邮件记录ID
     * @return array
     */
    public static function sendBatch(array $targets, string $title, string $content, int $recordId): array
    {
        $results = [
            'total' => count($targets),
            'success' => 0,
            'failed' => 0,
            'details' => []
        ];

        foreach ($targets as $target) {
            if ($target['type'] === 'user') {
                $result = self::sendToUser($target['id'], $title, $content, $recordId);
            } else {
                $result = self::sendToEmail($target['email'], $title, $content, $recordId);
            }

            if ($result['success']) {
                $results['success']++;
            } else {
                $results['failed']++;
            }

            $results['details'][] = $result;
        }

        return $results;
    }

    /**
     * 获取所有用户邮箱列表
     * @return array
     */
    public static function getAllUserEmails(): array
    {
        return users::where('email', '<>', '')
            ->whereNotNull('email')
            ->column('email', 'id');
    }

    /**
     * 根据用户ID列表获取邮箱
     * @param array $userIds 用户ID数组
     * @return array
     */
    public static function getUserEmailsByIds(array $userIds): array
    {
        return users::whereIn('id', $userIds)
            ->where('email', '<>', '')
            ->whereNotNull('email')
            ->column('email', 'id');
    }

    /**
     * 生成邮件HTML内容
     * @param string $content 邮件内容
     * @param array $data 额外数据
     * @return string
     */
    public static function generateEmailHtml(string $content, array $data = []): string
    {
        // 简单的HTML模板
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
                .footer { background: #333; color: #fff; padding: 15px; text-align: center; font-size: 12px; border-radius: 0 0 5px 5px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2>' . ($data['title'] ?? '系统通知') . '</h2>
                </div>
                <div class="content">
                    ' . nl2br(htmlspecialchars($content)) . '
                </div>
                <div class="footer">
                    <p>此邮件由系统自动发送，请勿回复</p>
                    <p>&copy; ' . date('Y') . ' 苍穹云网络. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ';

        return $html;
    }
}
