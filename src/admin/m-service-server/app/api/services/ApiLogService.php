<?php

namespace app\api\services;

use app\api\model\ApiLog as ApiLogModel;
use think\facade\Request;
use think\facade\Db;

class ApiLogService
{
    /**
     * 记录API请求日志
     * 
     * @param int|null $userId 用户ID
     * @param string|null $apiKey API密钥
     * @param int $statusCode HTTP状态码
     * @param string|null $errorCode 业务错误码
     * @param float $executionTime 执行时间(毫秒)
     * @param array $requestParams 请求参数(可选，默认自动获取)
     * @return int 日志ID
     */
    public static function record(
        ?int $userId = null,
        ?string $apiKey = null,
        int $statusCode = 200,
        ?string $errorCode = null,
        float $executionTime = 0,
        ?array $requestParams = null
    ): int {
        // 获取请求对象
        $request = Request::instance();
        
        // 如果未提供请求参数，则自动获取
        if ($requestParams === null) {
            $requestParams = $request->param();
            
            // 移除敏感字段（密码等）
            if (isset($requestParams['password'])) {
                $requestParams['password'] = '******';
            }
            if (isset($requestParams['token'])) {
                $requestParams['token'] = '******';
            }
        }
        
        // 准备日志数据
        $logData = [
            'user_id' => $userId,
            'api_key' => $apiKey,
            'device_fingerprint' => $request->header('X-Fingerprint'),
            'http_method' => $request->method(),
            'url_path' => $request->pathinfo(),
            'request_params' => $requestParams,
            'ip' => $request->ip(),
            'user_agent' => $request->header('user-agent'),
            'status_code' => $statusCode,
            'error_code' => $errorCode,
            'execution_time' => round($executionTime),
        ];
        
        // 创建日志记录
        try {
            $apiLog = ApiLogModel::create($logData);
            LogService::log("API日志记录成功: {$request->method()} {$request->pathinfo()}");
            return $apiLog->id;
        } catch (\Exception $e) {
            LogService::error("API日志记录失败", ['error' => $e->getMessage()]);
            return 0;
        }
    }
    
    /**
     * 获取API日志列表
     * 
     * @param array $params 查询参数
     * @return array
     */
    public static function getApiLogs(array $params = []): array
    {
        // 基础查询构建
        $query = ApiLogModel::with(['user' => function($query) {
            $query->field(['id', 'username', 'nickname']);
        }]);
        
        // 用户ID查询
        if (!empty($params['user_id'])) {
            $query->where('user_id', $params['user_id']);
        }
        
        // API路径模糊查询
        if (!empty($params['url_path'])) {
            $query->whereLike('url_path', '%' . $params['url_path'] . '%');
        }
        
        // HTTP方法查询
        if (!empty($params['http_method'])) {
            $query->where('http_method', $params['http_method']);
        }
        
        // 状态码查询
        if (!empty($params['status_code'])) {
            $query->where('status_code', $params['status_code']);
        }
        
        // IP地址查询
        if (!empty($params['ip'])) {
            $query->where('ip', $params['ip']);
        }
        
        // 时间范围查询
        if (!empty($params['start_time'])) {
            $query->where('create_time', '>=', $params['start_time']);
        }
        if (!empty($params['end_time'])) {
            $query->where('create_time', '<=', $params['end_time']);
        }
        
        // 错误日志查询（状态码非200）
        if (isset($params['is_error']) && $params['is_error']) {
            $query->where('status_code', '<>', 200);
        }
        
        // 执行时间查询（超过指定毫秒）
        if (!empty($params['min_execution_time'])) {
            $query->where('execution_time', '>=', $params['min_execution_time']);
        }
        
        // 排序
        $orderField = !empty($params['order_field']) ? $params['order_field'] : 'create_time';
        $orderDirection = !empty($params['order_direction']) ? $params['order_direction'] : 'desc';
        $query->order($orderField, $orderDirection);
        
        // 分页参数
        $page = !empty($params['page']) ? intval($params['page']) : 1;
        $pageSize = !empty($params['page_size']) ? intval($params['page_size']) : 20;
        
        // 执行分页查询
        try {
            $result = $query->paginate([
                'page' => $page,
                'list_rows' => $pageSize
            ]);
            
            // 记录SQL
            LogService::sql(Db::getLastSql(), [], 0);
            
            // 返回标准格式的分页数据
            return [
                'code' => 200,
                'msg' => '获取API日志列表成功',
                'data' => [
                    'list' => $result->items(),
                    'pagination' => [
                        'total' => $result->total(),
                        'current' => $result->currentPage(),
                        'page_size' => $pageSize,
                        'pages' => $result->lastPage()
                    ]
                ]
            ];
        } catch (\Exception $e) {
            LogService::error($e);
            return [
                'code' => 500,
                'msg' => '获取API日志列表失败：' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * 获取API日志统计信息
     * 
     * @param string $timeRange 时间范围，例如：today, yesterday, week, month
     * @return array
     */
    public static function getApiLogStats(string $timeRange = 'today'): array
    {
        try {
            // 根据时间范围确定查询日期
            $startDate = '';
            $endDate = date('Y-m-d H:i:s');
            
            switch ($timeRange) {
                case 'today':
                    $startDate = date('Y-m-d 00:00:00');
                    break;
                case 'yesterday':
                    $startDate = date('Y-m-d 00:00:00', strtotime('-1 day'));
                    $endDate = date('Y-m-d 23:59:59', strtotime('-1 day'));
                    break;
                case 'week':
                    $startDate = date('Y-m-d 00:00:00', strtotime('-7 days'));
                    break;
                case 'month':
                    $startDate = date('Y-m-d 00:00:00', strtotime('-30 days'));
                    break;
                default:
                    $startDate = date('Y-m-d 00:00:00');
            }
            
            // 基础查询条件
            $where = [
                ['create_time', 'between', [$startDate, $endDate]]
            ];
            
            // 统计数据
            $stats = [
                'total_requests' => ApiLogModel::where($where)->count(),
                'success_requests' => ApiLogModel::where($where)->where('status_code', 200)->count(),
                'error_requests' => ApiLogModel::where($where)->where('status_code', '<>', 200)->count(),
                'avg_execution_time' => ApiLogModel::where($where)->avg('execution_time'),
                'max_execution_time' => ApiLogModel::where($where)->max('execution_time'),
                'unique_ips' => ApiLogModel::where($where)->distinct(true)->field('ip')->count(),
                'unique_users' => ApiLogModel::where($where)->where('user_id', '<>', null)->distinct(true)->field('user_id')->count(),
                'top_apis' => self::getTopApis($startDate, $endDate, 5),
                'top_errors' => self::getTopErrors($startDate, $endDate, 5),
            ];
            
            // 记录SQL
            LogService::sql(Db::getLastSql(), [], 0);
            
            return [
                'code' => 200,
                'msg' => '获取API统计信息成功',
                'data' => $stats
            ];
        } catch (\Exception $e) {
            LogService::error($e);
            return [
                'code' => 500,
                'msg' => '获取API统计信息失败：' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * 获取访问量最高的API
     * 
     * @param string $startDate 开始日期
     * @param string $endDate 结束日期
     * @param int $limit 返回数量
     * @return array
     */
    private static function getTopApis(string $startDate, string $endDate, int $limit = 5): array
    {
        return Db::name('api_log')
            ->field('url_path, http_method, COUNT(*) as count, AVG(execution_time) as avg_time')
            ->where('create_time', 'between', [$startDate, $endDate])
            ->group('url_path, http_method')
            ->order('count', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }
    
    /**
     * 获取出错最多的API
     * 
     * @param string $startDate 开始日期
     * @param string $endDate 结束日期
     * @param int $limit 返回数量
     * @return array
     */
    private static function getTopErrors(string $startDate, string $endDate, int $limit = 5): array
    {
        return Db::name('api_log')
            ->field('url_path, http_method, status_code, error_code, COUNT(*) as count')
            ->where('create_time', 'between', [$startDate, $endDate])
            ->where('status_code', '<>', 200)
            ->group('url_path, http_method, status_code, error_code')
            ->order('count', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }
    
    /**
     * 清理过期日志（保留最近N天的日志）
     * 
     * @param int $days 保留天数
     * @return array
     */
    public static function cleanLogs(int $days = 30): array
    {
        try {
            $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
            
            // 删除旧日志
            $affectedRows = Db::name('api_log')
                ->where('create_time', '<', $cutoffDate)
                ->delete();
            
            LogService::log("清理API日志成功，删除了 {$affectedRows} 条 {$days} 天前的日志记录");
            
            return [
                'code' => 200,
                'msg' => "清理API日志成功，删除了 {$affectedRows} 条记录",
                'data' => [
                    'affected_rows' => $affectedRows,
                    'cutoff_date' => $cutoffDate
                ]
            ];
        } catch (\Exception $e) {
            LogService::error($e);
            return [
                'code' => 500,
                'msg' => '清理API日志失败：' . $e->getMessage(),
                'data' => null
            ];
        }
    }
} 