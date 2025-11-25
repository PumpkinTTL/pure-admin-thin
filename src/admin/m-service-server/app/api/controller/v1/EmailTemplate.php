<?php

namespace app\api\controller\v1;

use app\BaseController;
use app\api\services\EmailTemplateService;
use think\response\Json;

class EmailTemplate extends BaseController
{
    /**
     * 获取模板列表
     */
    public function list(): Json
    {
        $params = request()->param();
        $result = EmailTemplateService::getList($params);
        
        if ($result['success']) {
            return json([
                'code' => 200,
                'msg' => '获取成功',
                'data' => $result['data']
            ]);
        }
        
        return json([
            'code' => 500,
            'msg' => $result['message']
        ]);
    }
    
    /**
     * 获取模板详情
     */
    public function detail(): Json
    {
        $id = request()->param('id');
        
        if (empty($id)) {
            return json(['code' => 501, 'msg' => '参数错误：缺少模板ID']);
        }
        
        $result = EmailTemplateService::getDetail((int)$id);
        
        if ($result['success']) {
            return json([
                'code' => 200,
                'msg' => '获取成功',
                'data' => $result['data']
            ]);
        }
        
        return json([
            'code' => 404,
            'msg' => $result['message']
        ]);
    }
    
    /**
     * 创建模板
     */
    public function create(): Json
    {
        $data = request()->param();
        
        // 简单验证 - 修正为 content 字段
        if (empty($data['name']) || empty($data['code']) || empty($data['subject']) || empty($data['content'])) {
            return json(['code' => 501, 'msg' => '参数错误：缺少必填字段']);
        }
        
        // 设置默认 type
        if (!isset($data['type'])) {
            $data['type'] = 1; // 默认类型
        }
        
        $result = EmailTemplateService::create($data);
        
        if ($result['success']) {
            return json([
                'code' => 200,
                'msg' => $result['message'],
                'data' => $result['data']
            ]);
        }
        
        return json([
            'code' => 500,
            'msg' => $result['message']
        ]);
    }
    
    /**
     * 更新模板
     */
    public function update(): Json
    {
        $data = request()->param();
        
        if (empty($data['id'])) {
            return json(['code' => 501, 'msg' => '参数错误：缺少模板ID']);
        }
        
        $id = $data['id'];
        unset($data['id']);
        
        $result = EmailTemplateService::update((int)$id, $data);
        
        if ($result['success']) {
            return json([
                'code' => 200,
                'msg' => $result['message'],
                'data' => $result['data']
            ]);
        }
        
        return json([
            'code' => 500,
            'msg' => $result['message']
        ]);
    }
    
    /**
     * 删除模板
     */
    public function delete(): Json
    {
        $data = request()->param();
        
        if (empty($data['id'])) {
            return json(['code' => 501, 'msg' => '参数错误：缺少模板ID']);
        }
        
        // 检查是否物理删除
        $realDel = isset($data['real_del']) && $data['real_del'] == 1;
        
        $result = EmailTemplateService::delete((int)$data['id'], $realDel);
        
        if ($result['success']) {
            return json([
                'code' => 200,
                'msg' => $result['message']
            ]);
        }
        
        return json([
            'code' => 500,
            'msg' => $result['message']
        ]);
    }
    
    /**
     * 恢复模板
     */
    public function restore(): Json
    {
        $data = request()->param();
        
        if (empty($data['id'])) {
            return json(['code' => 501, 'msg' => '参数错误：缺少模板ID']);
        }
        
        $result = EmailTemplateService::restore((int)$data['id']);
        
        if ($result['success']) {
            return json([
                'code' => 200,
                'msg' => $result['message']
            ]);
        }
        
        return json([
            'code' => 500,
            'msg' => $result['message']
        ]);
    }
    
    /**
     * 切换模板状态
     */
    public function toggleStatus(): Json
    {
        $data = request()->param();
        
        if (empty($data['id'])) {
            return json(['code' => 501, 'msg' => '参数错误：缺少模板ID']);
        }
        
        $result = EmailTemplateService::toggleStatus((int)$data['id']);
        
        if ($result['success']) {
            return json([
                'code' => 200,
                'msg' => $result['message'],
                'data' => $result['data']
            ]);
        }
        
        return json([
            'code' => 500,
            'msg' => $result['message']
        ]);
    }
    
    /**
     * 发送测试邮件
     */
    public function sendTest(): Json
    {
        $data = request()->param();
        
        if (empty($data['id']) || empty($data['email'])) {
            return json(['code' => 501, 'msg' => '参数错误：缺少模板ID或邮箱']);
        }
        
        $variables = $data['variables'] ?? [];
        if (is_string($variables)) {
            $variables = json_decode($variables, true) ?: [];
        }
        
        $result = EmailTemplateService::sendTestEmail(
            (int)$data['id'],
            $data['email'],
            $variables
        );
        
        if ($result['success']) {
            return json([
                'code' => 200,
                'msg' => $result['message']
            ]);
        }
        
        return json([
            'code' => 500,
            'msg' => $result['message']
        ]);
    }
    
    /**
     * 获取可用模板列表（下拉选择用）
     */
    public function active(): Json
    {
        $templates = EmailTemplateService::getActiveTemplates();

        return json([
            'code' => 200,
            'msg' => '获取成功',
            'data' => $templates
        ]);
    }

    /**
     * 预览模板渲染效果
     */
    public function preview(): Json
    {
        $data = request()->param();

        if (empty($data['id'])) {
            return json(['code' => 501, 'msg' => '参数错误：缺少模板ID']);
        }

        $variables = $data['variables'] ?? [];
        if (is_string($variables)) {
            $variables = json_decode($variables, true) ?: [];
        }

        $result = EmailTemplateService::previewTemplate(
            (int)$data['id'],
            $variables
        );

        if ($result['success']) {
            return json([
                'code' => 200,
                'msg' => '预览成功',
                'data' => $result['data']
            ]);
        }

        return json([
            'code' => 500,
            'msg' => $result['message']
        ]);
    }
}
