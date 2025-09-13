<?php

namespace app\api\controller\v1;

use app\api\services\ApiLogService;
use app\api\services\LogService;
use think\facade\Request;

class apiLog
{
    /**
     * 获取API日志列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $params = Request::param();
        
        // 记录操作日志
        LogService::log('查询API日志列表', $params);
        
        // 获取API日志列表
        $result = ApiLogService::getApiLogs($params);
        
        return json($result);
    }
    
    /**
     * 获取API日志统计信息
     *
     * @return \think\Response
     */
    public function stats()
    {
        $timeRange = Request::param('time_range', 'today');
        
        // 记录操作日志
        LogService::log('查询API日志统计信息', ['time_range' => $timeRange]);
        
        // 获取API日志统计信息
        $result = ApiLogService::getApiLogStats($timeRange);
        
        return json($result);
    }
    
    /**
     * 获取API日志详情
     *
     * @param int $id 日志ID
     * @return \think\Response
     */
    public function detail()
    {
        $id = Request::param('id');
        
        // 记录操作日志
        LogService::log('查询API日志详情', ['id' => $id]);
        
        // 获取日志详情
        $log = \app\api\model\ApiLog::with(['user' => function($query) {
            $query->field(['id', 'username', 'nickname']);
        }])->find($id);
        
        if (!$log) {
            return json([
                'code' => 404,
                'msg' => '日志不存在',
                'data' => null
            ]);
        }
        
        return json([
            'code' => 200,
            'msg' => '获取日志详情成功',
            'data' => $log
        ]);
    }
    
    /**
     * 清理过期日志
     *
     * @return \think\Response
     */
    public function clean()
    {
        $days = Request::param('days', 30);
        
        // 记录操作日志
        LogService::log('清理API日志', ['days' => $days]);
        
        // 清理日志
        $result = ApiLogService::cleanLogs($days);
        
        return json($result);
    }
    
    /**
     * 获取特定用户的API日志
     *
     * @return \think\Response
     */
    public function userLogs()
    {
        $params = Request::param();
        $userId = Request::param('user_id');
        $params['user_id'] = $userId;
        
        // 记录操作日志
        LogService::log('查询用户API日志', ['user_id' => $userId]);
        
        // 获取用户日志
        $result = ApiLogService::getApiLogs($params);
        
        return json($result);
    }
    
    /**
     * 获取错误日志
     *
     * @return \think\Response
     */
    public function errors()
    {
        $params = Request::param();
        $params['is_error'] = true;
        
        // 记录操作日志
        LogService::log('查询API错误日志', $params);
        
        // 获取错误日志
        $result = ApiLogService::getApiLogs($params);
        
        return json($result);
    }
    
    /**
     * 获取性能较慢的API日志
     *
     * @return \think\Response
     */
    public function slow()
    {
        $params = Request::param();
        $params['min_execution_time'] = Request::param('threshold', 1000); // 默认1000ms
        $params['order_field'] = 'execution_time';
        $params['order_direction'] = 'desc';
        
        // 记录操作日志
        LogService::log('查询慢API日志', $params);
        
        // 获取慢API日志
        $result = ApiLogService::getApiLogs($params);
        
        return json($result);
    }
} 