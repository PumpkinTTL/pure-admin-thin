<?php

namespace app\api\controller\v1;

use app\BaseController;
use app\api\services\PaymentMethodService;
use think\facade\Validate;
use think\response\Json;

class paymentMethod extends BaseController
{
    /**
     * 获取支付方式列表
     * @return Json
     */
    public function selectPaymentMethodAll(): Json
    {
        $params = request()->param();

        // 分页参数
        $page = (int)($params['page'] ?? 1);
        $limit = (int)($params['limit'] ?? 10);

        // 查询条件
        $conditions = [];
        $searchFields = ['id', 'name', 'code', 'type', 'status', 'is_crypto', 'currency_code', 'network', 'is_default'];
        foreach ($searchFields as $field) {
            if (isset($params[$field]) && $params[$field] !== '') {
                $conditions[$field] = $params[$field];
            }
        }

        $result = PaymentMethodService::getPaymentMethodList($conditions, $page, $limit);

        return json($result);
    }

    /**
     * 获取支付方式详情
     * @return Json
     */
    public function selectPaymentMethodById(): Json
    {
        $id = (int)request()->param('id');

        if (!$id) {
            return json([
                'code' => 400,
                'msg' => '支付方式ID不能为空'
            ]);
        }

        $result = PaymentMethodService::getPaymentMethodById($id);

        return json($result);
    }

    /**
     * 添加支付方式
     * @return Json
     */
    public function add(): Json
    {
        $params = request()->param();

        // 验证规则
        $validate = Validate::rule([
            'name' => 'require|max:100',
            'code' => 'require|max:50|alphaNum',
            'type' => 'require|in:1,2,3',
            'currency_code' => 'max:10',
            'currency_symbol' => 'max:10',
            'is_crypto' => 'in:0,1',
            'network' => 'max:50',
            'contract_address' => 'max:100',
            'status' => 'in:0,1',
            'sort_order' => 'integer',
            'is_default' => 'in:0,1',
            'icon' => 'max:100'
        ])->message([
            'name.require' => '支付方式名称不能为空',
            'name.max' => '支付方式名称不能超过100个字符',
            'code.require' => '支付方式代码不能为空',
            'code.max' => '支付方式代码不能超过50个字符',
            'code.alphaNum' => '支付方式代码只能包含字母和数字',
            'type.require' => '支付类型不能为空',
            'type.in' => '支付类型必须为1(传统支付)、2(加密货币)或3(数字钱包)'
        ]);

        if (!$validate->check($params)) {
            return json([
                'code' => 400,
                'msg' => $validate->getError()
            ]);
        }

        $result = PaymentMethodService::addPaymentMethod($params);

        return json($result);
    }

    /**
     * 更新支付方式
     * @return Json
     */
    public function update(): Json
    {
        $id = (int)request()->param('id');
        $params = request()->param();

        if (!$id) {
            return json([
                'code' => 400,
                'msg' => '支付方式ID不能为空'
            ]);
        }

        // 验证规则（更新时字段可选）
        $validate = Validate::rule([
            'name' => 'max:100',
            'code' => 'max:50|alphaNum',
            'type' => 'in:1,2,3',
            'currency_code' => 'max:10',
            'currency_symbol' => 'max:10',
            'is_crypto' => 'in:0,1',
            'network' => 'max:50',
            'contract_address' => 'max:100',
            'status' => 'in:0,1',
            'sort_order' => 'integer',
            'is_default' => 'in:0,1',
            'icon' => 'max:100'
        ])->message([
            'name.max' => '支付方式名称不能超过100个字符',
            'code.max' => '支付方式代码不能超过50个字符',
            'code.alphaNum' => '支付方式代码只能包含字母和数字',
            'type.in' => '支付类型必须为1(传统支付)、2(加密货币)或3(数字钱包)'
        ]);

        if (!$validate->check($params)) {
            return json([
                'code' => 400,
                'msg' => $validate->getError()
            ]);
        }

        // 移除ID字段，避免更新主键
        unset($params['id']);

        $result = PaymentMethodService::updatePaymentMethod($id, $params);

        return json($result);
    }

    /**
     * 删除支付方式
     * @return Json
     */
    public function delete(): Json
    {
        $id = (int)request()->param('id');

        if (!$id) {
            return json([
                'code' => 400,
                'msg' => '支付方式ID不能为空'
            ]);
        }

        $result = PaymentMethodService::deletePaymentMethod($id);

        return json($result);
    }

    /**
     * 更新支付方式状态
     * @return Json
     */
    public function updateStatus(): Json
    {
        $id = (int)request()->param('id');
        $status = (int)request()->param('status');

        if (!$id) {
            return json([
                'code' => 400,
                'msg' => '支付方式ID不能为空'
            ]);
        }

        if (!in_array($status, [0, 1])) {
            return json([
                'code' => 400,
                'msg' => '状态值必须为0(禁用)或1(启用)'
            ]);
        }

        $result = PaymentMethodService::updatePaymentMethodStatus($id, $status);

        return json($result);
    }

    /**
     * 设置默认支付方式
     * @return Json
     */
    public function setDefault(): Json
    {
        $id = (int)request()->param('id');

        if (!$id) {
            return json([
                'code' => 400,
                'msg' => '支付方式ID不能为空'
            ]);
        }

        $result = PaymentMethodService::setDefaultPaymentMethod($id);

        return json($result);
    }

    /**
     * 获取启用的支付方式列表（用于前端选择）
     * @return Json
     */
    public function getEnabledList(): Json
    {
        $params = request()->param();

        // 查询条件
        $conditions = [];
        $filterFields = ['type', 'is_crypto', 'currency_code'];
        foreach ($filterFields as $field) {
            if (isset($params[$field]) && $params[$field] !== '') {
                $conditions[$field] = $params[$field];
            }
        }

        $result = PaymentMethodService::getEnabledPaymentMethods($conditions);

        return json($result);
    }
}