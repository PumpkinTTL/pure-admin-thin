<?php

namespace app\api\controller\v1;

use app\api\model\Api;
use app\BaseController;
use think\facade\Db;
use think\response\Json;
use utils\NumUtil;
use utils\RouteParser;

/**
 * 接口管理控制器
 */
class ApiManager extends BaseController
{
    /**
     * 获取所有接口
     * @return Json
     */
    public function getApiList(): Json
    {
        try {
            $params = request()->param();
            $page = isset($params['page']) ? intval($params['page']) : 1;
            $pageSize = isset($params['page_size']) ? intval($params['page_size']) : 15;
            $keyword = isset($params['keyword']) ? $params['keyword'] : '';
            $module = isset($params['module']) ? $params['module'] : '';
            $version = isset($params['version']) ? $params['version'] : '';
            $status = isset($params['status']) ? intval($params['status']) : null;

            // 构建查询
            $query = Api::order('id', 'desc');

            // 筛选条件
            if (!empty($keyword)) {
                $query = $query->where('full_path|model|method|description', 'like', "%{$keyword}%");
            }

            // 模块筛选
            if (!empty($module)) {
                $query = $query->where('module', 'like', "%{$module}%");
            }

            // 版本筛选
            if (!empty($version)) {
                $query = $query->where('version', $version);
            }

            if ($status !== null) {
                $query = $query->where('status', $status);
            }

            // 获取总记录数
            $total = $query->count();

            // 获取列表数据
            $list = $query->page($page, $pageSize)->select()->toArray();

            // 获取所有模块列表（用于筛选下拉框）
            $moduleList = Api::where('module', '<>', '')
                ->whereNotNull('module')
                ->group('module')
                ->order('module', 'asc')
                ->column('module');

            // 获取所有版本列表（用于筛选下拉框）
            $versionList = Api::where('version', '<>', '')
                ->whereNotNull('version')
                ->group('version')
                ->order('version', 'asc')
                ->column('version');

            return json([
                'code' => 200,
                'msg' => '获取成功',
                'data' => [
                    'list' => $list,
                    'total' => $total,
                    'page' => $page,
                    'page_size' => $pageSize,
                    'module_list' => array_values($moduleList), // 模块列表
                    'version_list' => array_values($versionList), // 版本列表
                    'status_options' => Api::getStatusOptions()
                ]
            ]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => '获取接口列表失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 获取接口详情
     * @return Json
     */
    public function getApiById(): Json
    {
        try {
            $id = request()->param('id');

            if (!$id || !is_numeric($id)) {
                return json(['code' => 400, 'msg' => '无效的接口ID']);
            }

            $apiInfo = Api::find($id);
            if (!$apiInfo) {
                return json(['code' => 404, 'msg' => '接口不存在']);
            }

            return json([
                'code' => 200,
                'msg' => '获取成功',
                'data' => $apiInfo,
                'status_options' => Api::getStatusOptions()
            ]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => '获取接口详情失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 更新接口信息
     * @return Json
     */
    public function updateApi(): Json
    {
        try {
            $params = request()->param();

            if (!isset($params['id']) || !is_numeric($params['id'])) {
                return json(['code' => 400, 'msg' => '无效的接口ID']);
            }

            $id = $params['id'];
            $apiInfo = Api::find($id);

            if (!$apiInfo) {
                return json(['code' => 404, 'msg' => '接口不存在']);
            }

            // 可更新字段
            $allowFields = ['description', 'status', 'check_mode', 'module', 'required_permission'];
            $updateData = [];

            foreach ($allowFields as $field) {
                if (isset($params[$field])) {
                    $updateData[$field] = $params[$field];
                }
            }

            // 验证状态值
            if (isset($updateData['status']) && !in_array($updateData['status'], [
                Api::STATUS_OPEN,
                Api::STATUS_MAINTENANCE,
                Api::STATUS_CLOSED
            ])) {
                return json(['code' => 400, 'msg' => '无效的状态值']);
            }

            // 验证 check_mode 值
            if (isset($updateData['check_mode']) && !in_array($updateData['check_mode'], ['none', 'manual', 'auto'])) {
                return json(['code' => 400, 'msg' => '无效的权限检查模式，必须是 none、manual 或 auto']);
            }

            // 如果是 manual 模式，检查是否设置了 required_permission
            if (isset($updateData['check_mode']) && $updateData['check_mode'] === 'manual') {
                if (!isset($updateData['required_permission']) || empty(trim($updateData['required_permission']))) {
                    // 检查数据库中是否已有 required_permission
                    if (empty($apiInfo->required_permission)) {
                        return json(['code' => 400, 'msg' => 'manual 模式必须设置 required_permission']);
                    }
                }
            }

            if (empty($updateData)) {
                return json(['code' => 400, 'msg' => '没有需要更新的数据']);
            }

            // 更新数据
            $apiInfo->save($updateData);

            return json([
                'code' => 200,
                'msg' => '更新成功'
            ]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => '更新接口失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 重置接口数据（从路由文件重新导入）
     * 使用新的 RouteParser 解析器，支持多版本路由
     * @return Json
     */
    public function resetApis(): Json
    {
        try {
            // 获取参数
            $clearExisting = (bool)request()->param('clear_existing', false);
            $syncMode = request()->param('sync_mode', 'smart'); // smart: 智能同步, full: 全量同步

            // 开始事务
            Db::startTrans();

            // 使用新的路由解析器
            $parser = new RouteParser();
            $routes = $parser->parseAll();
            $stats = $parser->getStats();

            // 如果需要清除现有接口
            if ($clearExisting) {
                Api::destroy(function ($query) {
                    $query->where('id', '>', 0);
                });
                error_log("[ApiManager] 已清除所有现有接口");
            }

            // 导入到数据库
            $importResult = $this->importRoutesV2($routes, $syncMode);

            // 提交事务
            Db::commit();

            return json([
                'code' => 200,
                'msg' => '接口同步成功',
                'data' => [
                    'total_parsed' => count($routes),
                    'imported_count' => $importResult['imported'],
                    'updated_count' => $importResult['updated'],
                    'skipped_count' => $importResult['skipped'],
                    'deleted_count' => $importResult['deleted'],
                    'clear_existing' => $clearExisting,
                    'sync_mode' => $syncMode,
                    'version_stats' => $stats['by_version'],
                    'module_stats' => $stats['by_module'],
                    'errors' => $stats['errors']
                ]
            ]);
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            error_log("[ApiManager] 同步失败: " . $e->getMessage());
            error_log("[ApiManager] 堆栈: " . $e->getTraceAsString());
            return json(['code' => 500, 'msg' => '重置接口失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 导入路由到数据库（V2版本 - 智能同步）
     * @param array $routes 解析后的路由列表
     * @param string $mode 同步模式: smart(智能) / full(全量)
     * @return array 导入结果统计
     */
    private function importRoutesV2(array $routes, string $mode = 'smart'): array
    { 
        $result = [
            'imported' => 0,
            'updated' => 0,
            'skipped' => 0,
            'deleted' => 0
        ];

        // 获取当前数据库中的所有路由（用于检测需要删除的）
        $existingRoutes = [];
        if ($mode === 'smart') {
            $existingRoutes = Api::column('id', 'full_path');
        }

        $processedPaths = [];

        foreach ($routes as $route) {
            $fullPath = $route['full_path'];
            $method = $route['method'];

            // 记录已处理的路径
            $processedPaths[$fullPath] = true;

            // 检查是否已存在
            $existing = Api::where('full_path', $fullPath)->find();

            if ($existing) {
                // 已存在，检查是否需要更新
                $needUpdate = false;
                $updateData = [];

                // 检查版本是否变化
                if ($existing->version !== $route['version']) {
                    $updateData['version'] = $route['version'];
                    $needUpdate = true;
                }

                // 检查模块是否变化
                if ($existing->module !== $route['module']) {
                    $updateData['module'] = $route['module'];
                    $needUpdate = true;
                }

                // 检查HTTP方法是否变化
                if ($existing->method !== $method) {
                    $updateData['method'] = $method;
                    $needUpdate = true;
                }

                // 检查控制器是否变化
                if ($existing->model !== $route['model']) {
                    $updateData['model'] = $route['model'];
                    $needUpdate = true;
                }

                if ($needUpdate) {
                    $updateData['update_time'] = date('Y-m-d H:i:s');
                    $existing->save($updateData);
                    $result['updated']++;
                    error_log("[ApiManager] 更新路由: {$fullPath}");
                } else {
                    $result['skipped']++;
                }
            } else {
                // 不存在，新增
                $newId = NumUtil::generateNumberCode(1, 5);

                // 确保ID唯一
                while (Api::where('id', $newId)->find()) {
                    $newId = NumUtil::generateNumberCode(1, 5);
                }

                $insertData = RouteParser::formatForDatabase($route);
                $insertData['id'] = $newId;

                // 强制设置为 none 模式，同步时不进行鉴权
                $insertData['check_mode'] = 'none';
                $insertData['required_permission'] = '';

                Api::create($insertData);
                $result['imported']++;
                error_log("[ApiManager] 新增路由: {$fullPath} (check_mode: none)");
            }
        }

        // 智能模式下，删除数据库中存在但路由文件中不存在的记录
        if ($mode === 'smart') {
            foreach ($existingRoutes as $path => $id) {
                if (!isset($processedPaths[$path])) {
                    // 这个路由在文件中不存在了，删除它
                    Api::destroy($id);
                    $result['deleted']++;
                    error_log("[ApiManager] 删除过期路由: {$path}");
                }
            }
        }

        error_log("========================================");
        error_log("[ApiManager] 导入完成");
        error_log("  新增: {$result['imported']}");
        error_log("  更新: {$result['updated']}");
        error_log("  跳过: {$result['skipped']}");
        error_log("  删除: {$result['deleted']}");
        error_log("========================================");

        return $result;
    }

    /**
     * 更新接口状态
     * @return Json
     */
    public function updateStatus(): Json
    {
        try {
            $id = request()->param('id');
            $status = request()->param('status');

            if (!$id || !is_numeric($id)) {
                return json(['code' => 400, 'msg' => '无效的接口ID']);
            }

            if (
                $status === null || !in_array($status, [
                    Api::STATUS_OPEN,
                    Api::STATUS_MAINTENANCE,
                    Api::STATUS_CLOSED
                ])
            ) {
                return json(['code' => 400, 'msg' => '无效的状态值']);
            }

            $apiInfo = Api::find($id);

            if (!$apiInfo) {
                return json(['code' => 404, 'msg' => '接口不存在']);
            }

            // 更新状态
            $apiInfo->save([
                'status' => $status
            ]);

            return json([
                'code' => 200,
                'msg' => '状态更新成功'
            ]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => '更新接口状态失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 批量更新接口状态
     * @return Json
     */
    public function batchUpdateStatus(): Json
    {
        try {
            $ids = request()->param('ids/a');  // /a 表示接收数组参数
            $status = request()->param('status');

            if (!$ids || !is_array($ids)) {
                return json(['code' => 400, 'msg' => '请提供接口ID列表']);
            }

            if ($status === null || !in_array($status, [
                Api::STATUS_OPEN,
                Api::STATUS_MAINTENANCE,
                Api::STATUS_CLOSED
            ])) {
                return json(['code' => 400, 'msg' => '无效的状态值']);
            }

            // 批量更新状态
            Api::update(['status' => $status], [['id', 'in', $ids]]);

            return json([
                'code' => 200,
                'msg' => '批量状态更新成功'
            ]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => '批量更新接口状态失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 获取接口状态选项
     * @return Json
     */
    public function getStatusOptions(): Json
    {
        try {
            return json([
                'code' => 200,
                'msg' => '获取成功',
                'data' => Api::getStatusOptions()
            ]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => '获取状态选项失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 修复数据库中已有的错误路径格式
     * @return Json
     */
    public function fixApiPaths(): Json
    {
        try {
            // 开始事务
            Db::startTrans();

            // 获取所有接口
            $apis = Api::select();
            $fixedCount = 0;

            foreach ($apis as $api) {
                $fullPath = $api->full_path;

                // 检查并修复路径格式
                if (strpos($fullPath, '/api/v1') === 0) {
                    // 提取模块和方法
                    $parts = explode('/', substr($fullPath, 7)); // 去掉 /api/v1 前缀

                    if (count($parts) >= 1) {
                        $model = $parts[0];

                        // 检查是否缺少斜杠
                        if (preg_match('/^([a-zA-Z0-9]+)([a-zA-Z0-9].*)$/', $model, $matches)) {
                            // 修复格式: /api/v1modulemethod -> /api/v1/module/method
                            $correctModel = $matches[1];
                            $method = $matches[2];

                            // 构建正确的路径
                            $correctedPath = "/api/v1/{$correctModel}/{$method}";

                            // 更新数据库
                            $api->full_path = $correctedPath;
                            $api->save();
                            $fixedCount++;
                        }
                    }
                }
            }

            // 提交事务
            Db::commit();

            return json([
                'code' => 200,
                'msg' => '路径格式修复成功',
                'data' => [
                    'fixed_count' => $fixedCount
                ]
            ]);
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return json(['code' => 500, 'msg' => '修复路径格式失败: ' . $e->getMessage()]);
        }
    }
}
