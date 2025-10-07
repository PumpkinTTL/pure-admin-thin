<?php
/**
 * 卡密控制器
 * 
 * 处理卡密相关的HTTP请求
 * 
 * @author AI Assistant
 * @date 2025-10-01
 */

namespace app\api\controller\v1;

use app\BaseController;
use app\api\services\CardKeyService;
use think\Request;
use think\response\Json;

class CardKey extends BaseController
{
    /**
     * 卡密服务实例
     * 
     * @var CardKeyService
     */
    protected $service;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->service = new CardKeyService();
    }

    /**
     * 生成单个卡密
     * 
     * POST /api/v1/cardkey/generate
     * 
     * @param Request $request
     * @return Json
     */
    public function generate(Request $request): Json
    {
        // 获取请求参数
        $data = $request->post();
        
        // 参数验证
        if (empty($data['type_id'])) {
            return json([
                'code' => 400,
                'message' => '卡密类型ID不能为空'
            ]);
        }
        
        // 调用服务生成
        $result = $this->service->generate($data);
        
        return json([
            'code' => $result['success'] ? 200 : 400,
            'message' => $result['message'] ?? ($result['success'] ? '生成成功' : '生成失败'),
            'data' => $result['data'] ?? null
        ]);
    }

    /**
     * 批量生成卡密
     * 
     * POST /api/v1/cardkey/batch
     * 
     * @param Request $request
     * @return Json
     */
    public function batchGenerate(Request $request): Json
    {
        // 获取请求参数
        $data = $request->post();
        
        // 参数验证
        if (empty($data['type_id'])) {
            return json([
                'code' => 400,
                'message' => '卡密类型ID不能为空'
            ]);
        }
        
        if (empty($data['count']) || $data['count'] <= 0) {
            return json([
                'code' => 400,
                'message' => '生成数量必须大于0'
            ]);
        }
        
        // 调用服务批量生成
        $result = $this->service->batchGenerate($data);
        
        return json([
            'code' => $result['success'] ? 200 : 400,
            'message' => $result['message'] ?? ($result['success'] ? '批量生成成功' : '批量生成失败'),
            'data' => $result['data'] ?? null,
            'total' => $result['total'] ?? 0,
            'failed' => $result['failed'] ?? 0
        ]);
    }

    /**
     * 获取卡密列表（分页+筛选）
     * 
     * GET /api/v1/cardkey/list
     * 
     * @param Request $request
     * @return Json
     */
    public function index(Request $request): Json
    {
        // 获取查询参数
        $params = $request->get();
        
        // 调用服务获取列表
        $result = $this->service->getList($params);
        
        return json([
            'code' => $result['success'] ? 200 : 400,
            'message' => $result['message'] ?? ($result['success'] ? '获取成功' : '获取失败'),
            'data' => $result['data'] ?? null
        ]);
    }

    /**
     * 获取卡密详情
     * 
     * GET /api/v1/cardkey/detail/:id
     * 
     * @param Request $request
     * @param int $id
     * @return Json
     */
    public function detail(Request $request, int $id): Json
    {
        // 调用服务获取详情
        $result = $this->service->getDetail($id);
        
        return json([
            'code' => $result['success'] ? 200 : 404,
            'message' => $result['message'] ?? ($result['success'] ? '获取成功' : '获取失败'),
            'data' => $result['data'] ?? null
        ]);
    }

    /**
     * 删除卡密
     * 
     * DELETE /api/v1/cardkey/delete/:id
     * 
     * @param Request $request
     * @param int $id
     * @return Json
     */
    public function delete(Request $request, int $id): Json
    {
        // 调用服务删除
        $result = $this->service->delete($id);
        
        return json([
            'code' => $result['success'] ? 200 : 400,
            'message' => $result['message'] ?? ($result['success'] ? '删除成功' : '删除失败')
        ]);
    }

    /**
     * 批量删除卡密
     * 
     * POST /api/v1/cardkey/batchDelete
     * 
     * @param Request $request
     * @return Json
     */
    public function batchDelete(Request $request): Json
    {
        // 获取ID数组
        $ids = $request->post('ids', []);
        
        if (empty($ids) || !is_array($ids)) {
            return json([
                'code' => 400,
                'message' => '请选择要删除的卡密'
            ]);
        }
        
        // 调用服务批量删除
        $result = $this->service->batchDelete($ids);
        
        return json([
            'code' => $result['success'] ? 200 : 400,
            'message' => $result['message'] ?? ($result['success'] ? '批量删除成功' : '批量删除失败')
        ]);
    }

    /**
     * 验证卡密
     * 
     * POST /api/v1/cardkey/verify
     * 
     * @param Request $request
     * @return Json
     */
    public function verify(Request $request): Json
    {
        // 获取卡密码
        $cardKey = $request->post('card_key', '');
        
        if (empty($cardKey)) {
            return json([
                'code' => 400,
                'message' => '卡密码不能为空'
            ]);
        }
        
        // 调用服务验证
        $result = $this->service->verify($cardKey);
        
        return json([
            'code' => $result['success'] ? 200 : 400,
            'message' => $result['message'] ?? '验证完成',
            'data' => $result['data'] ?? null
        ]);
    }

    /**
     * 使用卡密
     * 
     * POST /api/v1/cardkey/use
     * 
     * @param Request $request
     * @return Json
     */
    public function use(Request $request): Json
    {
        // 获取参数
        $cardKey = $request->post('card_key', '');
        $userId = $request->post('user_id', 0);
        
        if (empty($cardKey)) {
            return json([
                'code' => 400,
                'message' => '卡密码不能为空'
            ]);
        }
        
        if (empty($userId)) {
            return json([
                'code' => 400,
                'message' => '用户ID不能为空'
            ]);
        }
        
        // 额外信息
        $extra = [
            'ip' => $request->ip(),
            'user_agent' => $request->header('user-agent'),
            'remark' => $request->post('remark', '')
        ];
        
        // 调用服务使用卡密
        $result = $this->service->use($cardKey, $userId, $extra);
        
        return json([
            'code' => $result['success'] ? 200 : 400,
            'message' => $result['message'] ?? ($result['success'] ? '使用成功' : '使用失败'),
            'data' => $result['data'] ?? null
        ]);
    }

    /**
     * 禁用卡密
     * 
     * POST /api/v1/cardkey/disable/:id
     * 
     * @param Request $request
     * @param int $id
     * @return Json
     */
    public function disable(Request $request, int $id): Json
    {
        // 获取操作者ID和原因
        $userId = $request->post('user_id', 0);
        $reason = $request->post('reason', '');
        
        if (empty($userId)) {
            return json([
                'code' => 400,
                'message' => '操作者ID不能为空'
            ]);
        }
        
        // 调用服务禁用
        $result = $this->service->disable($id, $userId, $reason);
        
        return json([
            'code' => $result['success'] ? 200 : 400,
            'message' => $result['message'] ?? ($result['success'] ? '禁用成功' : '禁用失败')
        ]);
    }

    /**
     * 导出卡密
     * 
     * GET /api/v1/cardkey/export
     * 
     * @param Request $request
     * @return mixed
     */
    public function export(Request $request)
    {
        // 获取筛选参数
        $params = $request->get();
        
        // 调用服务导出
        $result = $this->service->export($params);
        
        if (!$result['success']) {
            return json([
                'code' => 400,
                'message' => $result['message'] ?? '导出失败'
            ]);
        }
        
        // 返回CSV文件
        return response($result['data'], 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $result['filename'] . '"'
        ]);
    }

    /**
     * 获取统计数据
     * 
     * GET /api/v1/cardkey/statistics
     * 
     * @param Request $request
     * @return Json
     */
    public function statistics(Request $request): Json
    {
        // 调用服务获取统计
        $result = $this->service->getStatistics();
        
        return json([
            'code' => $result['success'] ? 200 : 400,
            'message' => $result['message'] ?? ($result['success'] ? '获取成功' : '获取失败'),
            'data' => $result['data'] ?? null
        ]);
    }

    /**
     * 获取类型列表
     * 
     * GET /api/v1/cardkey/types
     * 
     * @param Request $request
     * @return Json
     */
    public function getTypes(Request $request): Json
    {
        // 调用服务获取类型列表
        $result = $this->service->getTypeList();
        
        return json([
            'code' => $result['success'] ? 200 : 400,
            'message' => $result['message'] ?? ($result['success'] ? '获取成功' : '获取失败'),
            'data' => $result['data'] ?? []
        ]);
    }

    /**
     * 获取使用记录
     * 
     * GET /api/v1/cardkey/logs/:id
     * 
     * @param Request $request
     * @param int $id
     * @return Json
     */
    public function logs(Request $request, int $id): Json
    {
        // 获取分页参数
        $params = $request->get();
        
        // 调用服务获取记录
        $result = $this->service->getLogs($id, $params);
        
        return json([
            'code' => $result['success'] ? 200 : 400,
            'message' => $result['message'] ?? ($result['success'] ? '获取成功' : '获取失败'),
            'data' => $result['data'] ?? null
        ]);
    }
}

