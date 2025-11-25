<?php

namespace app\api\services;

use app\api\model\EmailTemplate;
use think\facade\Log;

class EmailTemplateService
{
    /**
     * 获取模板列表
     * @param array $params 查询参数
     * @return array
     */
    public static function getList(array $params = []): array
    {
        try {
            $pageSize = $params['page_size'] ?? 10;
            $page = $params['page'] ?? 1;

            $query = EmailTemplate::withTrashed();

            // 搜索条件
            if (!empty($params['name'])) {
                $query->where('name', 'like', '%' . $params['name'] . '%');
            }

            if (!empty($params['code'])) {
                $query->where('code', 'like', '%' . $params['code'] . '%');
            }

            if (isset($params['type']) && $params['type'] !== '') {
                $query->where('type', $params['type']);
            }

            if (isset($params['is_active']) && $params['is_active'] !== '') {
                $query->where('is_active', $params['is_active']);
            }

            if (isset($params['is_system']) && $params['is_system'] !== '') {
                $query->where('is_system', $params['is_system']);
            }

            // 软删除控制
            $includeDeleted = $params['include_deleted'] ?? 0;
            if ($includeDeleted == 1) {
                $query->onlyTrashed();
            } elseif ($includeDeleted == 2) {
                $query->withTrashed();
            }

            // 排序
            $query->order('type', 'asc')->order('create_time', 'desc');

            $result = $query->paginate([
                'list_rows' => $pageSize,
                'page' => $page
            ]);

            return [
                'success' => true,
                'data' => [
                    'list' => $result->items(),
                    'pagination' => [
                        'total' => $result->total(),
                        'current' => $result->currentPage(),
                        'page_size' => $pageSize,
                        'pages' => ceil($result->total() / $pageSize)
                    ]
                ]
            ];
        } catch (\Exception $e) {
            Log::error('获取模板列表失败: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * 获取模板详情
     * @param int $id 模板ID
     * @return array
     */
    public static function getDetail(int $id): array
    {
        try {
            $template = EmailTemplate::withTrashed()->find($id);
            if (!$template) {
                return ['success' => false, 'message' => '模板不存在'];
            }

            return ['success' => true, 'data' => $template];
        } catch (\Exception $e) {
            Log::error('获取模板详情失败: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * 创建模板
     * @param array $data 模板数据
     * @return array
     */
    public static function create(array $data): array
    {
        try {
            // 检查code是否重复
            $exists = EmailTemplate::where('code', $data['code'])->find();
            if ($exists) {
                return ['success' => false, 'message' => '模板标识已存在'];
            }

            $template = EmailTemplate::create([
                'name' => $data['name'],
                'code' => $data['code'],
                'type' => $data['type'],
                'subject' => $data['subject'],
                'content' => $data['content'],
                'variables' => $data['variables'] ?? [],
                'description' => $data['description'] ?? '',
                'is_active' => $data['is_active'] ?? true,
                'is_system' => $data['is_system'] ?? false,
                'created_by' => $data['created_by'] ?? null,
            ]);

            return ['success' => true, 'message' => '创建成功', 'data' => $template];
        } catch (\Exception $e) {
            Log::error('创建模板失败: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * 更新模板
     * @param int $id 模板ID
     * @param array $data 更新数据
     * @return array
     */
    public static function update(int $id, array $data): array
    {
        try {
            $template = EmailTemplate::find($id);
            if (!$template) {
                return ['success' => false, 'message' => '模板不存在'];
            }

            // 不允许修改模板标识（无论是否系统模板）
            if (isset($data['code']) && $data['code'] !== $template->code) {
                return ['success' => false, 'message' => '不允许修改模板标识'];
            }

            $template->save($data);

            return ['success' => true, 'message' => '更新成功', 'data' => $template];
        } catch (\Exception $e) {
            Log::error('更新模板失败: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * 删除模板
     * @param int $id 模板ID
     * @param bool $realDel 是否物理删除
     * @return array
     */
    public static function delete(int $id, bool $realDel = false): array
    {
        try {
            $template = EmailTemplate::find($id);
            if (!$template) {
                return ['success' => false, 'message' => '模板不存在'];
            }

            // 系统模板不允许删除
            if ($template->is_system) {
                return ['success' => false, 'message' => '系统模板不允许删除'];
            }

            if ($realDel) {
                // 物理删除
                $template->force()->delete();
                return ['success' => true, 'message' => '物理删除成功'];
            } else {
                // 软删除
                $template->delete();
                return ['success' => true, 'message' => '删除成功'];
            }
        } catch (\Exception $e) {
            Log::error('删除模板失败: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * 恢复模板
     * @param int $id 模板ID
     * @return array
     */
    public static function restore(int $id): array
    {
        try {
            $template = EmailTemplate::onlyTrashed()->find($id);
            if (!$template) {
                return ['success' => false, 'message' => '模板不存在或未被删除'];
            }

            $template->restore();

            return ['success' => true, 'message' => '恢复成功'];
        } catch (\Exception $e) {
            Log::error('恢复模板失败: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * 切换模板状态
     * @param int $id 模板ID
     * @return array
     */
    public static function toggleStatus(int $id): array
    {
        try {
            $template = EmailTemplate::find($id);
            if (!$template) {
                return ['success' => false, 'message' => '模板不存在'];
            }

            $template->is_active = !$template->is_active;
            $template->save();

            return [
                'success' => true,
                'message' => '状态更新成功',
                'data' => ['is_active' => $template->is_active]
            ];
        } catch (\Exception $e) {
            Log::error('切换模板状态失败: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * 根据模板标识发送邮件
     * @param string $code 模板标识
     * @param string $email 收件人邮箱
     * @param array $data 模板变量数据
     * @param int|null $recordId 邮件记录ID（可选）
     * @return array
     */
    public static function sendByTemplate(string $code, string $email, array $data, ?int $recordId = null, bool $isHtmlContent = true): array
    {
        try {
            Log::info("开始发送模板邮件: code={$code}, email={$email}");
            
            // 获取模板
            $template = EmailTemplate::getByCode($code);
            if (!$template) {
                Log::error("模板不存在: code={$code}");
                return ['success' => false, 'message' => "模板不存在或未启用: {$code}"];
            }
            
            Log::info("模板找到: id={$template->id}, name={$template->name}");

            // 渲染模板
            $rendered = $template->render($data);
            Log::info("模板渲染完成: subject={$rendered['subject']}, content_length=" . strlen($rendered['content']));

            // 如果有记录ID，使用EmailSendService
            if ($recordId) {
                return EmailSendService::sendToEmail($email, $rendered['subject'], $rendered['content'], $recordId, $isHtmlContent);
            }

            // 否则直接发送
            Log::info("准备调用 EmailUtil::sendMail");
            $result = \utils\EmailUtil::sendMail($email, $rendered['subject'], $rendered['content']);
            Log::info("邮件发送结果: " . ($result ? '成功' : '失败'));

            return [
                'success' => $result,
                'message' => $result ? '发送成功' : '发送失败',
                'email' => $email
            ];
        } catch (\Exception $e) {
            Log::error("使用模板发送邮件异常: " . $e->getMessage());
            Log::error("异常堆栈: " . $e->getTraceAsString());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * 发送测试邮件
     * @param int $id 模板ID
     * @param string $email 测试邮箱
     * @param array $variables 测试变量
     * @return array
     */
    public static function sendTestEmail(int $id, string $email, array $variables): array
    {
        try {
            $template = EmailTemplate::find($id);
            if (!$template) {
                return ['success' => false, 'message' => '模板不存在'];
            }

            // 渲染模板
            $rendered = $template->render($variables);

            // 发送邮件
            $result = \utils\EmailUtil::sendMail($email, $rendered['subject'], $rendered['content']);

            return [
                'success' => $result,
                'message' => $result ? '测试邮件发送成功' : '发送失败'
            ];
        } catch (\Exception $e) {
            Log::error('发送测试邮件失败: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * 获取所有可用模板（用于下拉选择）
     * @return array
     */
    public static function getActiveTemplates(): array
    {
        return EmailTemplate::where('is_active', 1)
            ->field('id,name,code,type,subject,variables')
            ->order('type', 'asc')
            ->order('name', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * 预览模板渲染效果
     * @param int $id 模板ID
     * @param array $variables 变量数据
     * @return array
     */
    public static function previewTemplate(int $id, array $variables = []): array
    {
        try {
            $template = EmailTemplate::find($id);
            if (!$template) {
                return ['success' => false, 'message' => '模板不存在'];
            }

            // 生成默认变量
            $defaultVars = [
                'date' => date('Y-m-d'),
                'datetime' => date('Y-m-d H:i:s'),
                'time' => date('H:i:s'),
                'year' => date('Y'),
                'site_name' => '苍穹云网络',
                'company_name' => '苍穹云网络'
            ];

            // 合并变量
            $allVariables = array_merge($defaultVars, $variables);

            // 简单的字符串替换
            $renderedSubject = $template->subject;
            $renderedContent = $template->content;
            $replacedVars = [];

            // 替换所有变量 {variable_name}
            foreach ($allVariables as $key => $value) {
                $placeholder = '{' . $key . '}';
                $oldSubject = $renderedSubject;
                $oldContent = $renderedContent;

                $renderedSubject = str_replace($placeholder, $value, $renderedSubject);
                $renderedContent = str_replace($placeholder, $value, $renderedContent);

                if ($oldSubject !== $renderedSubject || $oldContent !== $renderedContent) {
                    $replacedVars[] = [
                        'placeholder' => $placeholder,
                        'variable' => $key,
                        'value' => $value
                    ];
                }
            }

            // 检查还有没有未替换的占位符
            $missingVars = [];
            if (preg_match_all('/\{[a-zA-Z0-9_]+\}/', $renderedSubject . $renderedContent, $matches)) {
                foreach ($matches[0] as $match) {
                    $missingVars[] = [
                        'placeholder' => $match,
                        'variable' => substr($match, 1, -1)
                    ];
                }
            }

            return [
                'success' => true,
                'data' => [
                    'original_subject' => $template->subject,
                    'original_content' => $template->content,
                    'rendered_subject' => $renderedSubject,
                    'rendered_content' => $renderedContent,
                    'replaced_vars' => $replacedVars,
                    'missing_vars' => $missingVars,
                    'has_missing' => !empty($missingVars),
                    'variables' => $variables
                ]
            ];
        } catch (\Exception $e) {
            Log::error('预览模板失败: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
