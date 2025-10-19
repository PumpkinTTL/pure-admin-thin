<?php

namespace app\api\services;

use app\api\model\PaymentMethod;
use think\facade\Db;

/**
 * 支付方式服务类
 */
class PaymentMethodService
{
    /**
     * 获取支付方式列表
     * @param array $conditions 查询条件
     * @param int $page 页码，默认1
     * @param int $limit 每页条数，默认10
     * @return array
     */
    public static function getPaymentMethodList(array $conditions = [], int $page = 1, int $limit = 10): array
    {
        try {
            // 记录SQL开始时间
            $startTime = microtime(true);
            
            // 构建查询条件
            $query = PaymentMethod::alias('pm');
            
            // ID精确匹配
            if (isset($conditions['id']) && $conditions['id'] !== '') {
                $query->where('pm.id', '=', $conditions['id']);
            }
            
            // 模糊匹配字段
            foreach (['name', 'code'] as $field) {
                if (!empty($conditions[$field])) {
                    $query->where("pm.{$field}", 'like', '%' . $conditions[$field] . '%');
                }
            }

            // 精确匹配字段
            foreach (['type', 'status', 'network', 'is_default'] as $field) {
                if (isset($conditions[$field]) && $conditions[$field] !== '') {
                    $query->where("pm.{$field}", '=', $conditions[$field]);
                }
            }
            
            // 排序
            $query->order('pm.sort_order', 'desc')
                  ->order('pm.create_time', 'desc');
            
            // 分页查询
            $result = $query->paginate([
                'page' => $page,
                'list_rows' => $limit
            ]);
            
            // 添加文本描述字段
            $list = $result->items();
            foreach ($list as &$item) {
                $item->append(['type_text', 'status_text', 'is_default_text']);
            }
            
            // 记录SQL执行时间
            $endTime = microtime(true);
            $executionTime = round(($endTime - $startTime) * 1000, 2);
            LogService::sql(Db::getLastSql(), [], $executionTime);
            
            // 返回标准格式的分页数据
            return [
                'code' => 200,
                'msg' => '获取支付方式列表成功',
                'data' => [
                    'list' => $list,
                    'pagination' => [
                        'total' => $result->total(),
                        'current' => $result->currentPage(),
                        'page_size' => $limit,
                        'pages' => $result->lastPage()
                    ]
                ]
            ];
        } catch (\Exception $e) {
            LogService::error($e);
            return [
                'code' => 500,
                'msg' => '获取支付方式列表失败：' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * 获取支付方式详情
     * @param int $id 支付方式ID
     * @return array
     */
    public static function getPaymentMethodById(int $id): array
    {
        try {
            $startTime = microtime(true);

            $paymentMethod = PaymentMethod::find($id);

            if (!$paymentMethod) {
                LogService::log("获取支付方式详情失败，ID不存在：{$id}", [], 'warning');
                return ['code' => 404, 'msg' => '支付方式不存在', 'data' => null];
            }

            // 添加文本描述字段
            $paymentMethod->append(['type_text', 'status_text', 'is_default_text']);
            
            $endTime = microtime(true);
            $executionTime = round(($endTime - $startTime) * 1000, 2);
            LogService::sql(Db::getLastSql(), [], $executionTime);
            
            return [
                'code' => 200,
                'msg' => '获取支付方式详情成功',
                'data' => $paymentMethod
            ];
        } catch (\Exception $e) {
            LogService::error($e);
            return [
                'code' => 500,
                'msg' => '获取支付方式详情失败：' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * 添加支付方式
     * @param array $data 支付方式数据
     * @return array
     */
    public static function addPaymentMethod(array $data): array
    {
        try {
            $startTime = microtime(true);
            
            // 验证必填字段
            if (empty($data['name'])) {
                return ['code' => 400, 'msg' => '支付方式名称不能为空'];
            }
            
            // 如果设置为默认支付方式，需要取消其他默认设置
            if (!empty($data['is_default']) && $data['is_default'] == 1) {
                PaymentMethod::where('is_default', 1)->update(['is_default' => 0]);
            }
            
            // 创建支付方式
            $paymentMethod = new PaymentMethod();
            $paymentMethod->save($data);
            
            $endTime = microtime(true);
            $executionTime = round(($endTime - $startTime) * 1000, 2);
            LogService::sql(Db::getLastSql(), [], $executionTime);
            LogService::log("添加支付方式成功，ID：{$paymentMethod->id}，名称：{$data['name']}");
            
            return [
                'code' => 200,
                'msg' => '添加成功',
                'data' => $paymentMethod
            ];
        } catch (\Exception $e) {
            LogService::error($e);
            return [
                'code' => 500,
                'msg' => '添加失败：' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * 更新支付方式
     * @param int $id 支付方式ID
     * @param array $data 更新数据
     * @return array
     */
    public static function updatePaymentMethod(int $id, array $data): array
    {
        try {
            $startTime = microtime(true);
            
            // 查询支付方式是否存在
            $paymentMethod = PaymentMethod::find($id);
            if (!$paymentMethod) {
                LogService::log("更新支付方式失败，ID不存在：{$id}", [], 'warning');
                return ['code' => 404, 'msg' => '支付方式不存在'];
            }
            
            // 如果设置为默认支付方式，需要取消其他默认设置
            if (!empty($data['is_default']) && $data['is_default'] == 1) {
                PaymentMethod::where('is_default', 1)->where('id', '<>', $id)->update(['is_default' => 0]);
            }
            
            // 更新支付方式
            $paymentMethod->save($data);
            
            $endTime = microtime(true);
            $executionTime = round(($endTime - $startTime) * 1000, 2);
            LogService::sql(Db::getLastSql(), [], $executionTime);
            LogService::log("更新支付方式成功，ID：{$id}，名称：{$paymentMethod->name}");
            
            // 添加文本描述字段
            $paymentMethod->append(['type_text', 'status_text', 'is_default_text']);

            return [
                'code' => 200,
                'msg' => '更新成功',
                'data' => $paymentMethod
            ];
        } catch (\Exception $e) {
            LogService::error($e);
            return [
                'code' => 500,
                'msg' => '更新失败：' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * 删除支付方式
     * @param int $id 支付方式ID
     * @return array
     */
    public static function deletePaymentMethod(int $id): array
    {
        try {
            $startTime = microtime(true);

            // 查询支付方式是否存在
            $paymentMethod = PaymentMethod::find($id);
            if (!$paymentMethod) {
                LogService::log("删除支付方式失败，ID不存在：{$id}", [], 'warning');
                return ['code' => 404, 'msg' => '支付方式不存在'];
            }

            // 检查是否为默认支付方式
            if ($paymentMethod->getData('is_default') == 1) {
                LogService::log("删除支付方式失败，不能删除默认支付方式：{$id}", [], 'warning');
                return ['code' => 400, 'msg' => '不能删除默认支付方式，请先设置其他支付方式为默认'];
            }

            // 检查是否有相关的支付订单
            // 注：如果项目中有订单表，请解注下面的代码
            /* 
            $orderCount = Db::name('orders')->where('payment_method_id', $id)->count();
            if ($orderCount > 0) {
                LogService::log("删除支付方式失败，已被{$orderCount}个订单使用：{$id}", [], 'warning');
                return ['code' => 400, 'msg' => "该支付方式已被 {$orderCount} 个订单使用，无法删除"];
            }
            */

            $paymentMethodName = $paymentMethod->getData('name');

            // 物理删除
            $paymentMethod->delete();
            LogService::log("删除支付方式成功，ID：{$id}，名称：{$paymentMethodName}");

            $endTime = microtime(true);
            $executionTime = round(($endTime - $startTime) * 1000, 2);
            LogService::sql(Db::getLastSql(), [], $executionTime);

            return [
                'code' => 200,
                'msg' => '支付方式已删除'
            ];
        } catch (\Exception $e) {
            LogService::error($e);
            return [
                'code' => 500,
                'msg' => '删除失败：' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * 更新支付方式状态
     * @param int $id 支付方式ID
     * @param int $status 状态：0=禁用，1=启用
     * @return array
     */
    public static function updatePaymentMethodStatus(int $id, int $status): array
    {
        try {
            $startTime = microtime(true);

            // 查询支付方式是否存在
            $paymentMethod = PaymentMethod::find($id);
            if (!$paymentMethod) {
                LogService::log("更新支付方式状态失败，ID不存在：{$id}", [], 'warning');
                return ['code' => 404, 'msg' => '支付方式不存在'];
            }

            // 如果是禁用默认支付方式，需要检查
            if ($paymentMethod->is_default == 1 && $status == 0) {
                LogService::log("更新支付方式状态失败，不能禁用默认支付方式：{$id}", [], 'warning');
                return ['code' => 400, 'msg' => '不能禁用默认支付方式，请先设置其他支付方式为默认'];
            }

            // 更新状态
            $paymentMethod->status = $status;
            $paymentMethod->save();

            $endTime = microtime(true);
            $executionTime = round(($endTime - $startTime) * 1000, 2);
            LogService::sql(Db::getLastSql(), [], $executionTime);

            $statusText = $status == 1 ? '启用' : '禁用';
            LogService::log("更新支付方式状态成功，ID：{$id}，状态：{$statusText}");

            return [
                'code' => 200,
                'msg' => '状态更新成功',
                'data' => $paymentMethod
            ];
        } catch (\Exception $e) {
            LogService::error($e);
            return [
                'code' => 500,
                'msg' => '状态更新失败：' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * 设置默认支付方式
     * @param int $id 支付方式ID
     * @return array
     */
    public static function setDefaultPaymentMethod(int $id): array
    {
        try {
            $startTime = microtime(true);

            // 查询支付方式是否存在且启用
            $paymentMethod = PaymentMethod::where('id', $id)->where('status', 1)->find();
            if (!$paymentMethod) {
                LogService::log("设置默认支付方式失败，ID不存在或未启用：{$id}", [], 'warning');
                return ['code' => 404, 'msg' => '支付方式不存在或未启用'];
            }

            Db::startTrans();
            try {
                // 取消所有默认设置
                PaymentMethod::where('is_default', 1)->update(['is_default' => 0]);

                // 设置新的默认支付方式
                $paymentMethod->is_default = 1;
                $paymentMethod->save();

                Db::commit();

                $endTime = microtime(true);
                $executionTime = round(($endTime - $startTime) * 1000, 2);
                LogService::sql(Db::getLastSql(), [], $executionTime);
                LogService::log("设置默认支付方式成功，ID：{$id}，名称：{$paymentMethod->name}");

                return [
                    'code' => 200,
                    'msg' => '设置默认支付方式成功',
                    'data' => $paymentMethod
                ];
            } catch (\Exception $e) {
                Db::rollback();
                throw $e;
            }
        } catch (\Exception $e) {
            LogService::error($e);
            return [
                'code' => 500,
                'msg' => '设置默认支付方式失败：' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * 获取启用的支付方式列表（用于前端选择）
     * @param array $conditions 查询条件
     * @return array
     */
    public static function getEnabledPaymentMethods(array $conditions = []): array
    {
        try {
            $startTime = microtime(true);

            $query = PaymentMethod::where('status', 1);

            // 根据类型筛选
            if (!empty($conditions['type'])) {
                $query->where('type', $conditions['type']);
            }

            // 根据网络筛选
            if (!empty($conditions['network'])) {
                $query->where('network', $conditions['network']);
            }

            $paymentMethods = $query->field('id,name,type,icon,network,wallet_address,sort_order,is_default')
                                   ->order('sort_order', 'desc')
                                   ->order('is_default', 'desc')
                                   ->select();

            $endTime = microtime(true);
            $executionTime = round(($endTime - $startTime) * 1000, 2);
            LogService::sql(Db::getLastSql(), [], $executionTime);

            return [
                'code' => 200,
                'msg' => '获取启用的支付方式成功',
                'data' => $paymentMethods
            ];
        } catch (\Exception $e) {
            LogService::error($e);
            return [
                'code' => 500,
                'msg' => '获取启用的支付方式失败：' . $e->getMessage(),
                'data' => null
            ];
        }
    }
}
