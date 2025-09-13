<?php

namespace app\api\controller\v1;

use app\api\services\MySqlEventService;
use app\api\services\LogService;
use think\facade\Request;
use think\facade\Db;

class MySqlEvent
{
    /**
     * 获取所有MySQL事件列表
     *
     * @return \think\Response
     */
    public function list()
    {
        $schema = Request::param('schema');
        
        // 记录操作日志
        LogService::log('查询MySQL事件列表', ['schema' => $schema]);
        
        // 获取事件列表
        $result = MySqlEventService::getAllEvents($schema);
        
        return json($result);
    }
    
    /**
     * 获取MySQL事件详情
     *
     * @return \think\Response
     */
    public function detail()
    {
        $eventName = Request::param('event_name');
        $schema = Request::param('schema');
        
        // 参数检查
        if (empty($eventName)) {
            return json([
                'code' => 400,
                'msg' => '事件名称不能为空',
                'data' => null
            ]);
        }
        
        // 记录操作日志
        LogService::log('查询MySQL事件详情', ['event_name' => $eventName, 'schema' => $schema]);
        
        // 获取事件详情
        $result = MySqlEventService::getEventDetails($eventName, $schema);
        
        return json($result);
    }
    
    /**
     * 创建MySQL事件
     *
     * @return \think\Response
     */
    public function create()
    {
        // 获取请求参数
        $eventName = Request::param('event_name');
        $schedule = Request::param('schedule');
        $sqlStatement = Request::param('sql_statement');
        $enable = Request::param('enable', true);
        $comment = Request::param('comment');
        $startTime = Request::param('start_time');
        $endTime = Request::param('end_time');
        
        // 参数检查
        if (empty($eventName) || empty($schedule) || empty($sqlStatement)) {
            return json([
                'code' => 400,
                'msg' => '事件名称、调度表达式和SQL语句不能为空',
                'data' => null
            ]);
        }
        
        // 记录操作日志
        LogService::log('创建MySQL事件', [
            'event_name' => $eventName,
            'schedule' => $schedule,
            'enable' => $enable
        ]);
        
        // 创建事件
        $result = MySqlEventService::createEvent(
            $eventName,
            $schedule,
            $sqlStatement,
            $enable,
            $comment,
            $startTime,
            $endTime
        );
        
        return json($result);
    }
    
    /**
     * 修改MySQL事件
     *
     * @return \think\Response
     */
    public function alter()
    {
        // 获取请求参数
        $eventName = Request::param('event_name');
        $options = Request::param();
        
        // 移除event_name，因为它是单独的参数
        unset($options['event_name']);
        
        // 参数检查
        if (empty($eventName)) {
            return json([
                'code' => 400,
                'msg' => '事件名称不能为空',
                'data' => null
            ]);
        }
        
        // 如果没有任何修改选项，返回错误
        if (empty($options)) {
            return json([
                'code' => 400,
                'msg' => '至少需要提供一个修改选项',
                'data' => null
            ]);
        }
        
        // 记录操作日志
        LogService::log('修改MySQL事件', [
            'event_name' => $eventName,
            'options' => $options
        ]);
        
        // 修改事件
        $result = MySqlEventService::alterEvent($eventName, $options);
        
        return json($result);
    }
    
    /**
     * 删除MySQL事件
     *
     * @return \think\Response
     */
    public function drop()
    {
        $eventName = Request::param('event_name');
        
        // 参数检查
        if (empty($eventName)) {
            return json([
                'code' => 400,
                'msg' => '事件名称不能为空',
                'data' => null
            ]);
        }
        
        // 记录操作日志
        LogService::log('删除MySQL事件', ['event_name' => $eventName]);
        
        // 删除事件
        $result = MySqlEventService::dropEvent($eventName);
        
        return json($result);
    }
    
    /**
     * 启用/禁用事件调度器
     *
     * @return \think\Response
     */
    public function toggleScheduler()
    {
        $enable = Request::param('enable', true);
        
        // 记录操作日志
        LogService::log('设置MySQL事件调度器状态', ['enable' => $enable]);
        
        // 设置事件调度器状态
        $result = MySqlEventService::toggleEventScheduler($enable);
        
        return json($result);
    }
    
    /**
     * 获取事件调度器状态
     *
     * @return \think\Response
     */
    public function schedulerStatus()
    {
        // 记录操作日志
        LogService::log('获取MySQL事件调度器状态');
        
        // 获取事件调度器状态
        $result = MySqlEventService::getEventSchedulerStatus();
        
        return json($result);
    }
    
    /**
     * 检查用户是否有MySQL事件权限
     *
     * @return \think\Response
     */
    public function checkPrivileges()
    {
        // 记录操作日志
        LogService::log('检查MySQL事件权限');
        
        // 检查权限
        $result = MySqlEventService::checkEventPrivileges();
        
        return json($result);
    }
    
    /**
     * 获取MySQL事件系统状态
     *
     * @return \think\Response
     */
    public function systemStatus()
    {
        // 记录操作日志
        LogService::log('获取MySQL事件系统状态');
        
        // 获取系统状态
        $result = MySqlEventService::getEventSystemStatus();
        
        return json($result);
    }
    
    /**
     * 启用/禁用单个事件
     *
     * @return \think\Response
     */
    public function toggleEvent()
    {
        $eventName = Request::param('event_name');
        $enable = Request::param('enable', true);
        
        // 参数检查
        if (empty($eventName)) {
            return json([
                'code' => 400,
                'msg' => '事件名称不能为空',
                'data' => null
            ]);
        }
        
        // 记录操作日志
        LogService::log('切换MySQL事件状态', [
            'event_name' => $eventName,
            'enable' => $enable
        ]);
        
        // 修改事件状态
        $result = MySqlEventService::alterEvent($eventName, ['enable' => $enable]);
        
        return json($result);
    }
    
    /**
     * 获取MySQL事件执行日志列表
     *
     * @return \think\Response
     */
    public function eventLogList()
    {
        $eventName = Request::param('event_name');
        $status = Request::param('status');
        $page = Request::param('page', 1, 'intval');
        $limit = Request::param('limit', 20, 'intval');
        
        // 记录操作日志
        LogService::log('查询MySQL事件执行日志列表', [
            'event_name' => $eventName,
            'status' => $status,
            'page' => $page,
            'limit' => $limit
        ]);
        
        try {
            // 构建查询
            $query = Db::table('bl_mysql_event_log')
                ->order('create_time', 'desc');
            
            // 条件筛选
            if (!empty($eventName)) {
                $query = $query->where('message', 'like', "%{$eventName}%");
            }
            
            if (!empty($status)) {
                $query = $query->where('status', $status);
            }
            
            // 分页查询
            $total = $query->count();
            $list = $query->page($page, $limit)->select();
            
            return json([
                'code' => 200,
                'msg' => '获取MySQL事件执行日志列表成功',
                'data' => [
                    'total' => $total,
                    'list' => $list
                ]
            ]);
        } catch (\Exception $e) {
            LogService::error('获取MySQL事件执行日志列表失败', [
                'error' => $e->getMessage()
            ]);
            
            return json([
                'code' => 500,
                'msg' => '获取MySQL事件执行日志列表失败：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    /**
     * 清空MySQL事件执行日志
     *
     * @return \think\Response
     */
    public function clearEventLog()
    {
        $eventName = Request::param('event_name');
        
        // 记录操作日志
        LogService::log('清空MySQL事件执行日志', [
            'event_name' => $eventName
        ]);
        
        try {
            $query = Db::table('bl_mysql_event_log')->where('1=1');
            
            // 如果指定了事件名称，则只清空该事件的日志
            if (!empty($eventName)) {
                $query = $query->where('message', 'like', "%{$eventName}%");
            }
            
            $count = $query->count();
            $query->delete();
            
            return json([
                'code' => 200,
                'msg' => '清空MySQL事件执行日志成功',
                'data' => [
                    'count' => $count
                ]
            ]);
        } catch (\Exception $e) {
            LogService::error('清空MySQL事件执行日志失败', [
                'error' => $e->getMessage()
            ]);
            
            return json([
                'code' => 500,
                'msg' => '清空MySQL事件执行日志失败：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    /**
     * 获取MySQL事件执行统计信息
     *
     * @return \think\Response
     */
    public function eventLogStatistics()
    {
        // 记录操作日志
        LogService::log('获取MySQL事件执行统计信息');
        
        try {
            // 总执行次数
            $totalCount = Db::table('bl_mysql_event_log')->count();
            
            // 成功次数
            $successCount = Db::table('bl_mysql_event_log')->where('status', 1)->count();
            
            // 失败次数
            $failedCount = Db::table('bl_mysql_event_log')->where('status', 0)->count();
            
            // 按事件名称分组统计
            $eventStats = Db::table('bl_mysql_event_log')
                ->field('message as event_name, COUNT(*) as total, 
                    SUM(IF(status=1, 1, 0)) as success_count,
                    SUM(IF(status=0, 1, 0)) as failed_count,
                    AVG(execution_time) as avg_time,
                    MAX(execution_time) as max_time,
                    MIN(execution_time) as min_time,
                    SUM(affected_rows) as total_affected_rows')
                ->group('message')
                ->select();
            
            return json([
                'code' => 200,
                'msg' => '获取MySQL事件执行统计信息成功',
                'data' => [
                    'total_count' => $totalCount,
                    'success_count' => $successCount,
                    'failed_count' => $failedCount,
                    'success_rate' => $totalCount > 0 ? round($successCount / $totalCount * 100, 2) : 0,
                    'event_stats' => $eventStats
                ]
            ]);
        } catch (\Exception $e) {
            LogService::error('获取MySQL事件执行统计信息失败', [
                'error' => $e->getMessage()
            ]);
            
            return json([
                'code' => 500,
                'msg' => '获取MySQL事件执行统计信息失败：' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
} 