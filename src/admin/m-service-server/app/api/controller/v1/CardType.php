<?php
/**
 * 卡密类型控制器
 * 
 * 处理卡密类型相关的HTTP请求
 * 
 * @author AI Assistant
 * @date 2025-10-04
 */

namespace app\api\controller\v1;

use app\BaseController;
use app\api\services\CardTypeService;
use think\Request;
use think\response\Json;

class CardType extends BaseController
{
    /**
     * 卡密类型服务实例
     * 
     * @var CardTypeService
     */
    protected $service;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->service = new CardTypeService();
    }

    /**
     * 获取类型列表（分页+筛选）
     * 
     * GET /api/v1/cardtype/list
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
     * 获取所有启用的类型（用于下拉选择）
     * 
     * GET /api/v1/cardtype/enabled
     * 
     * @param Request $request
     * @return Json
     */
    public function enabled(Request $request): Json
    {
        // 调用服务获取启用类型
        $result = $this->service->getEnabledTypes();
        
        return json([
            'code' => $result['success'] ? 200 : 400,
            'message' => $result['message'] ?? ($result['success'] ? '获取成功' : '获取失败'),
            'data' => $result['data'] ?? null
        ]);
    }

    /**
     * 获取类型详情
     * 
     * GET /api/v1/cardtype/detail/:id
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
     * 创建类型
     * 
     * POST /api/v1/cardtype/create
     * 
     * @param Request $request
     * @return Json
     */
    public function create(Request $request): Json
    {
        // 获取请求参数
        $data = $request->post();
        
        // 参数验证
        if (empty($data['type_name'])) {
            return json([
                'code' => 400,
                'message' => '类型名称不能为空'
            ]);
        }
        
        if (empty($data['type_code'])) {
            return json([
                'code' => 400,
                'message' => '类型编码不能为空'
            ]);
        }
        
        // 调用服务创建
        $result = $this->service->create($data);
        
        return json([
            'code' => $result['success'] ? 200 : 400,
            'message' => $result['message'] ?? ($result['success'] ? '创建成功' : '创建失败'),
            'data' => $result['data'] ?? null
        ]);
    }

    /**
     * 更新类型
     * 
     * PUT /api/v1/cardtype/update/:id
     * 
     * @param Request $request
     * @param int $id
     * @return Json
     */
    public function update(Request $request, int $id): Json
    {
        // 获取请求参数
        $data = $request->put();
        
        // 调用服务更新
        $result = $this->service->update($id, $data);
        
        return json([
            'code' => $result['success'] ? 200 : 400,
            'message' => $result['message'] ?? ($result['success'] ? '更新成功' : '更新失败'),
            'data' => $result['data'] ?? null
        ]);
    }

    /**
     * 删除类型
     * 
     * DELETE /api/v1/cardtype/delete/:id
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
     * 批量删除类型
     * 
     * POST /api/v1/cardtype/batchDelete
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
                'message' => '请选择要删除的类型'
            ]);
        }
        
        // 调用服务批量删除
        $result = $this->service->batchDelete($ids);
        
        return json([
            'code' => $result['success'] ? 200 : 400,
            'message' => $result['message'] ?? ($result['success'] ? '批量删除成功' : '批量删除失败')
        ]);
    }
}

