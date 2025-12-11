<?php

namespace app\api\controller\v1;

use app\api\model\Api;
use app\BaseController;
use think\facade\Db;
use think\response\Json;
use utils\NumUtil;

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
            $status = isset($params['status']) ? intval($params['status']) : null;
            
            // 构建查询
            $query = Api::order('id', 'desc');
            
            // 筛选条件
            if (!empty($keyword)) {
                $query = $query->where('full_path|model|method', 'like', "%{$keyword}%");
            }
            
            if ($status !== null) {
                $query = $query->where('status', $status);
            }
            
            // 获取总记录数
            $total = $query->count();
            
            // 获取列表数据
            $list = $query->page($page, $pageSize)->select()->toArray();
            
            return json([
                'code' => 200,
                'msg' => '获取成功',
                'data' => [
                    'list' => $list,
                    'total' => $total,
                    'page' => $page,
                    'page_size' => $pageSize,
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
     * 重置接口数据（从route.php文件重新导入）
     * @return Json
     */
    public function resetApis(): Json
    {
        try {
            // 获取参数
            $clearExisting = (bool)request()->param('clear_existing', false);
            
            // 开始事务
            Db::startTrans();
            
            // 如果需要清除现有接口
            if ($clearExisting) {
                Api::destroy(function($query) {
                    $query->where('id', '>', 0);
                });
            }
            
            // 解析路由文件
            $routes = $this->parseRouteFile();
            
            // 按模块统计
            $moduleStats = [];
            foreach ($routes as $route) {
                $module = $route['module'] ?? 'unknown';
                if (!isset($moduleStats[$module])) {
                    $moduleStats[$module] = 0;
                }
                $moduleStats[$module]++;
            }
            arsort($moduleStats);
            
            error_log("========================================");
            error_log("[ApiManager] 按模块分组统计:");
            foreach ($moduleStats as $module => $count) {
                error_log("  {$module}: {$count} 条");
            }
            error_log("========================================");
            
            // 导入到数据库
            $importCount = $this->importRoutes($routes);
            
            // 提交事务
            Db::commit();
            
            return json([
                'code' => 200,
                'msg' => '接口重置成功',
                'data' => [
                    'total_parsed' => count($routes),
                    'imported_count' => $importCount,
                    'clear_existing' => $clearExisting,
                    'module_stats' => $moduleStats
                ]
            ]);
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return json(['code' => 500, 'msg' => '重置接口失败: ' . $e->getMessage(), 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 解析route.php文件获取所有接口（智能过滤版）
     * @return array
     */
    private function parseRouteFile(): array
    {
        $routes = [];
        
        // 统计信息
        $stats = [
            'total' => 0,
            'skipped_empty' => 0,
            'skipped_closure' => 0,
            'skipped_invalid_type' => 0,
            'skipped_parse_failed' => 0,
            'skipped_framework' => 0,
            'skipped_duplicate' => 0,
            'final' => 0,
        ];
        
        $seenRoutes = []; // 用于检测重复
        
        try {
            // 使用ThinkPHP的路由信息
            $routeList = \think\facade\Route::getRuleList();
            $stats['total'] = count($routeList);
            
            error_log("========================================");
            error_log("[ApiSync] 开始解析路由，ThinkPHP注册总数: {$stats['total']}");
            error_log("========================================");
            
            foreach ($routeList as $rule) {
                // 获取路由规则
                $route = $rule['route'] ?? '';
                $method = $rule['method'] ?? 'ANY';
                $rule_str = $rule['rule'] ?? '';
                
                // 1. 跳过空路由
                if (empty($rule_str)) {
                    $stats['skipped_empty']++;
                    continue;
                }
                
                // 2. 跳过闭包路由
                if ($route instanceof \Closure || is_object($route)) {
                    $stats['skipped_closure']++;
                    continue;
                }
                
                // 3. 确保route是字符串
                if (!is_string($route)) {
                    $stats['skipped_invalid_type']++;
                    continue;
                }
                
                // 4. 解析控制器和方法
                $version = 'v1';
                $controller = '';
                $action = '';
                
                if (preg_match('/(?:app\\\\api\\\\controller\\\\)?(\w+)\\\\(\w+)@(\w+)/', $route, $matches)) {
                    $version = strtolower($matches[1]);
                    $controller = $matches[2];
                    $action = $matches[3];
                } elseif (preg_match('/(:?version|\w+)\.(\w+)\/(\w+)/', $route, $matches)) {
                    $versionPart = $matches[1];
                    if ($versionPart === ':version' || $versionPart === 'version') {
                        $version = 'v1';
                    } else {
                        $version = strtolower($versionPart);
                    }
                    $controller = $matches[2];
                    $action = $matches[3];
                } else {
                    $stats['skipped_parse_failed']++;
                    error_log("[ApiSync] 无法解析路由: {$rule_str} -> {$route}");
                    continue;
                }
                
                // 确保version格式正确
                if (!preg_match('/^v\d+$/', $version)) {
                    $version = 'v1';
                }
                
                // 5. 构建完整路径
                $fullPath = '/' . $rule_str;
                $fullPath = preg_replace('/:version|<version>/', 'v1', $fullPath);
                $fullPath = preg_replace('/:(\w+)/', '{$1}', $fullPath);
                $fullPath = preg_replace('/<(\w+)>/', '{$1}', $fullPath);
                $fullPath = preg_replace('#/+#', '/', $fullPath);
                
                // 6. 过滤框架路由
                if ($this->isFrameworkRoute($fullPath)) {
                    $stats['skipped_framework']++;
                    error_log("[ApiSync] 跳过框架路由: {$fullPath}");
                    continue;
                }
                
                // 7. 处理HTTP方法
                if (is_array($method)) {
                    $httpMethod = implode(',', $method);
                } else {
                    $httpMethod = strtoupper($method);
                }
                
                if ($httpMethod === 'GET,POST,PUT,DELETE,PATCH,HEAD,OPTIONS' || 
                    $httpMethod === '*' || 
                    empty($httpMethod)) {
                    $httpMethod = 'ANY';
                }
                
                // 8. 检查重复（只保留唯一的完整路径）
                // 使用 full_path + method 作为唯一键
                $routeKey = $fullPath . '|' . $httpMethod;
                if (isset($seenRoutes[$routeKey])) {
                    $stats['skipped_duplicate']++;
                    error_log("[ApiSync] 跳过重复路由: {$fullPath} ({$httpMethod})");
                    continue;
                }
                $seenRoutes[$routeKey] = true;
                
                // 9. 额外检查：如果路径完全相同但版本不同，只保留v1版本
                // 例如：/v1/user/login 和 /v2/user/login，只保留 /v1/user/login
                $pathWithoutVersion = preg_replace('#^/v\d+/#', '/vX/', $fullPath);
                $versionlessKey = $pathWithoutVersion . '|' . $httpMethod;
                
                if (isset($seenRoutes[$versionlessKey])) {
                    // 如果已经有相同路径的其他版本，跳过
                    $stats['skipped_duplicate']++;
                    error_log("[ApiSync] 跳过版本重复: {$fullPath} (已有其他版本)");
                    continue;
                }
                $seenRoutes[$versionlessKey] = true;
                
                // 10. 提取模块名
                $moduleName = $this->extractModuleName($fullPath);
                
                // 11. 添加到结果
                $routes[] = [
                    'version' => $version,
                    'method' => $httpMethod,
                    'model' => $controller,
                    'path' => $rule_str,
                    'full_path' => $fullPath,
                    'create_time' => date('Y-m-d H:i:s'),
                    'update_time' => date('Y-m-d H:i:s'),
                    'description' => '',
                    'status' => Api::STATUS_OPEN,
                    'check_mode' => 'manual',
                    'module' => $moduleName,
                    'required_permission' => ''
                ];
            }
            
            $stats['final'] = count($routes);
            
            // 输出统计报告
            error_log("========================================");
            error_log("[ApiSync] 路由解析完成");
            error_log("  ThinkPHP注册总数: {$stats['total']}");
            error_log("  跳过空路由: {$stats['skipped_empty']}");
            error_log("  跳过Closure: {$stats['skipped_closure']}");
            error_log("  跳过无效类型: {$stats['skipped_invalid_type']}");
            error_log("  跳过解析失败: {$stats['skipped_parse_failed']}");
            error_log("  跳过框架路由: {$stats['skipped_framework']}");
            error_log("  跳过重复路由: {$stats['skipped_duplicate']}");
            error_log("  ✅ 最终同步: {$stats['final']} 条");
            error_log("========================================");
            
        } catch (\Exception $e) {
            error_log("[ApiSync] 解析失败: " . $e->getMessage());
            error_log("[ApiSync] 堆栈: " . $e->getTraceAsString());
        }
        
        return $routes;
    }
    
    /**
     * 判断是否为框架路由
     * @param string $path 路径
     * @return bool
     */
    private function isFrameworkRoute(string $path): bool
    {
        $frameworkPatterns = [
            '#^/$#',                    // 根路径
            '#^/index$#',               // 默认首页
            '#^/miss$#',                // 404路由
            '#^/__#',                   // 双下划线开头（调试工具）
            '#^/think/#',               // think路由
            '#^/debug/#',               // debug路由
            '#^/error/#',               // error路由
        ];
        
        foreach ($frameworkPatterns as $pattern) {
            if (preg_match($pattern, $path)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 从完整路径中提取模块名
     * @param string $fullPath 完整路径，如 /v1/user/login
     * @return string 模块名，如 user
     */
    private function extractModuleName(string $fullPath): string
    {
        // 移除开头的斜杠
        $path = ltrim($fullPath, '/');
        
        // 分割路径
        $parts = explode('/', $path);
        
        // 跳过版本号（v1, v2等）
        if (count($parts) >= 2 && preg_match('/^v\d+$/', $parts[0])) {
            return $parts[1];
        }
        
        // 如果没有版本号，返回第一个部分
        return $parts[0] ?? 'unknown';
    }
    
    /**
     * 将接口导入到数据库
     * @param array $routes
     * @return int 导入的接口数量
     */
    private function importRoutes(array $routes): int
    {
        $count = 0;
        
        foreach ($routes as $route) {
            // 检查是否已存在
            $exists = Api::where('full_path', $route['full_path'])
                ->where('method', $route['method'])
                ->find();
            
            if (!$exists) {
                // 生成5位数ID（不以0开头）
                $newId = NumUtil::generateNumberCode(1, 5);
                
                // 检查ID是否已存在
                while (Api::where('id', $newId)->find()) {
                    $newId = NumUtil::generateNumberCode(1, 5);
                }
                
                // 设置ID
                $route['id'] = $newId;
                
                // 插入新接口
                Api::create($route);
                $count++;
            }
        }
        
        return $count;
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
            
            if ($status === null || !in_array($status, [
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