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
            $allowFields = ['description', 'status'];
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
            
            // 导入到数据库
            $importCount = $this->importRoutes($routes);
            
            // 提交事务
            Db::commit();
            
            return json([
                'code' => 200,
                'msg' => '接口重置成功',
                'data' => [
                    'imported_count' => $importCount,
                    'clear_existing' => $clearExisting
                ]
            ]);
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return json(['code' => 500, 'msg' => '重置接口失败: ' . $e->getMessage(), 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * 解析route.php文件获取所有接口
     * @return array
     */
    private function parseRouteFile(): array
    {
        // 使用正确的路径格式，避免路径错误
        $routePath = root_path() . 'app' . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'route' . DIRECTORY_SEPARATOR . 'route.php';
        $routeFile = file_get_contents($routePath);
        
        $routes = [];
        
        // 先匹配所有路由组
        preg_match_all('/Route::group\([\'"]([^\'"]+)[\'"],\s*function\s*\(\)\s*\{((?:[^{}]|(?R))*)\}\);/s', $routeFile, $groupMatches, PREG_SET_ORDER);
        
        foreach ($groupMatches as $groupMatch) {
            $groupPath = $groupMatch[1];
            $groupContent = $groupMatch[2];
            
            // 替换版本占位符
            $groupPath = str_replace('/:version', '/api/v1', $groupPath);
            
            // 匹配组内的路由规则
            preg_match_all('/Route::rule\([\'"]([^\'"]+)[\'"],\s*[\'"]([^\'"]+)[\'"](?:,\s*[\'"]([^\'"]*)[\'"]\s*)?(?:,|(?:\)))/', $groupContent, $ruleMatches, PREG_SET_ORDER);
            
            foreach ($ruleMatches as $match) {
                $path = $match[1];
                $action = $match[2];
                
                // 获取HTTP方法（如果指定）
                $httpMethod = isset($match[3]) && !empty($match[3]) ? strtoupper($match[3]) : 'ANY';
                
                // 解析版本和控制器
                preg_match('/^:version\.([^\/]+)\/(.+)$/', $action, $actionMatches);
                
                if (isset($actionMatches[1]) && isset($actionMatches[2])) {
                    $model = $actionMatches[1];
                    $method = $actionMatches[2];
                    
                    // 构建完整路径
                    $fullPath = $groupPath;
                    
                    // 确保路径以/开头
                    if ($path[0] !== '/') {
                        $fullPath .= '/';
                    }
                    
                    $fullPath .= $path;
                    
                    // 规范化路径，确保格式正确
                    $fullPath = preg_replace('#/+#', '/', $fullPath);
                    
                    // 添加到接口列表
                    $routes[] = [
                        'version' => 'v1',
                        'method' => $httpMethod,
                        'model' => $model,
                        'path' => $path,
                        'full_path' => $fullPath,
                        'create_time' => date('Y-m-d H:i:s'),
                        'update_time' => date('Y-m-d H:i:s'),
                        'description' => '',
                        'status' => Api::STATUS_OPEN
                    ];
                }
            }
        }
        
        return $routes;
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