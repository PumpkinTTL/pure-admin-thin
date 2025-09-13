<?php

namespace app\api\services;

use think\facade\Db;
use think\Exception;

/**
 * MySQL事件调度服务
 * 用于管理和执行MySQL事件调度任务
 */
class MySqlEventService
{
    /**
     * 获取所有MySQL事件列表
     * 
     * @param string|null $schema 数据库名称，默认当前数据库
     * @return array
     */
    public static function getAllEvents(?string $schema = null): array
    {
        try {
            if ($schema === null) {
                // 获取当前数据库名
                $currentDb = Db::query('SELECT DATABASE() as db')[0]['db'];
                $schema = $currentDb;
            }
            
            // 查询所有事件，包含注释信息
            $events = Db::query("
                SELECT 
                    e.EVENT_SCHEMA AS 'Db', 
                    e.EVENT_NAME AS 'Name',
                    e.DEFINER AS 'Definer',
                    e.TIME_ZONE AS 'Time zone',
                    e.EVENT_TYPE AS 'Type',
                    e.EXECUTE_AT AS 'Execute at',
                    e.INTERVAL_VALUE AS 'Interval value',
                    e.INTERVAL_FIELD AS 'Interval field',
                    e.STARTS AS 'Starts',
                    e.ENDS AS 'Ends',
                    e.STATUS AS 'Status',
                    e.ORIGINATOR AS 'Originator',
                    e.CHARACTER_SET_CLIENT AS 'character_set_client',
                    e.COLLATION_CONNECTION AS 'collation_connection',
                    e.DATABASE_COLLATION AS 'Database Collation',
                    e.EVENT_COMMENT AS 'Comment'
                FROM 
                    information_schema.EVENTS e
                WHERE 
                    e.EVENT_SCHEMA = '{$schema}'
            ");
            
            // 记录日志
            LogService::log("获取MySQL事件列表成功", ['schema' => $schema, 'count' => count($events)]);
            
            return [
                'code' => 200,
                'msg' => '获取MySQL事件列表成功',
                'data' => $events
            ];
        } catch (\Exception $e) {
            LogService::error("获取MySQL事件列表失败", ['error' => $e->getMessage()]);
            return [
                'code' => 500,
                'msg' => '获取MySQL事件列表失败：' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * 获取事件详情
     * 
     * @param string $eventName 事件名称
     * @param string|null $schema 数据库名称，默认当前数据库
     * @return array
     */
    public static function getEventDetails(string $eventName, ?string $schema = null): array
    {
        try {
            if ($schema === null) {
                // 获取当前数据库名
                $currentDb = Db::query('SELECT DATABASE() as db')[0]['db'];
                $schema = $currentDb;
            }
            
            // 查询事件详情，包含注释信息
            $eventDetails = Db::query("
                SELECT 
                    e.EVENT_SCHEMA AS 'Db', 
                    e.EVENT_NAME AS 'Name',
                    e.DEFINER AS 'Definer',
                    e.TIME_ZONE AS 'Time zone',
                    e.EVENT_TYPE AS 'Type',
                    e.EXECUTE_AT AS 'Execute at',
                    e.INTERVAL_VALUE AS 'Interval value',
                    e.INTERVAL_FIELD AS 'Interval field',
                    e.STARTS AS 'Starts',
                    e.ENDS AS 'Ends',
                    e.STATUS AS 'Status',
                    e.ORIGINATOR AS 'Originator',
                    e.CHARACTER_SET_CLIENT AS 'character_set_client',
                    e.COLLATION_CONNECTION AS 'collation_connection',
                    e.DATABASE_COLLATION AS 'Database Collation',
                    e.EVENT_COMMENT AS 'Comment',
                    e.EVENT_DEFINITION AS 'SQL'
                FROM 
                    information_schema.EVENTS e
                WHERE 
                    e.EVENT_SCHEMA = '{$schema}' 
                    AND e.EVENT_NAME = '{$eventName}'
            ");
            
            if (empty($eventDetails)) {
                return [
                    'code' => 404,
                    'msg' => "事件 '{$eventName}' 不存在",
                    'data' => null
                ];
            }
            
            // 记录日志
            LogService::log("获取MySQL事件详情成功", ['schema' => $schema, 'event' => $eventName]);
            
            return [
                'code' => 200,
                'msg' => '获取MySQL事件详情成功',
                'data' => $eventDetails[0]
            ];
        } catch (\Exception $e) {
            LogService::error("获取MySQL事件详情失败", ['error' => $e->getMessage()]);
            return [
                'code' => 500,
                'msg' => '获取MySQL事件详情失败：' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * 创建一个新的MySQL事件
     * 
     * @param string $eventName 事件名称
     * @param string $schedule 调度表达式，如'EVERY 1 DAY' 或 'AT TIMESTAMP "2023-10-01 00:00:00"'
     * @param string $sqlStatement 要执行的SQL语句
     * @param bool $enable 是否启用事件
     * @param string|null $comment 事件注释
     * @param string|null $startTime 事件开始时间，格式：Y-m-d H:i:s
     * @param string|null $endTime 事件结束时间，格式：Y-m-d H:i:s
     * @return array
     */
    public static function createEvent(
        string $eventName,
        string $schedule,
        string $sqlStatement,
        bool $enable = true,
        ?string $comment = null,
        ?string $startTime = null,
        ?string $endTime = null
    ): array {
        try {
            // 防止SQL注入，对参数进行安全处理
            $eventName = addslashes($eventName);
            $schedule = addslashes($schedule);
            
            if (!empty($comment)) {
                $comment = addslashes($comment);
            }
            
            if (!empty($startTime)) {
                $startTime = addslashes($startTime);
            }
            
            if (!empty($endTime)) {
                $endTime = addslashes($endTime);
            }
            
            // 构建事件创建SQL
            $sql = "CREATE EVENT `{$eventName}` ON SCHEDULE {$schedule}";
            
            // 添加开始时间
            if (!empty($startTime)) {
                $sql .= " STARTS '{$startTime}'";
            }
            
            // 添加结束时间
            if (!empty($endTime)) {
                $sql .= " ENDS '{$endTime}'";
            }
            
            // 添加启用/禁用状态
            $sql .= $enable ? " ENABLE" : " DISABLE";
            
            // 添加注释
            if (!empty($comment)) {
                $sql .= " COMMENT '{$comment}'";
            }
            
            // 检查SQL语句是否已经包含BEGIN...END块
            if (stripos($sqlStatement, 'BEGIN') === 0) {
                // 如果已经是BEGIN...END格式，直接使用
                $eventSql = "DO " . $sqlStatement;
            } else {
                // 否则添加BEGIN...END和日志记录
                $eventSql = "DO BEGIN {$sqlStatement}; INSERT INTO bl_mysql_event_log (message, status, execution_time, error_message, affected_rows, execution_info, create_time) VALUES ('{$eventName}', 1, 0, '', ROW_COUNT(), '执行成功', NOW()); END";
            }
            
            // 添加执行SQL
            $sql .= " " . $eventSql;
            
            // 执行创建事件
            Db::execute($sql);
            
            // 记录日志
            LogService::log("创建MySQL事件成功", ['event' => $eventName, 'schedule' => $schedule]);
            
            return [
                'code' => 200,
                'msg' => "创建MySQL事件 '{$eventName}' 成功",
                'data' => null
            ];
        } catch (\Exception $e) {
            LogService::error("创建MySQL事件失败", ['error' => $e->getMessage()]);
            return [
                'code' => 500,
                'msg' => '创建MySQL事件失败：' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * 修改现有的MySQL事件
     * 
     * @param string $eventName 事件名称
     * @param array $options 修改选项，可包含：schedule, sql_statement, enable, comment, start_time, end_time
     * @return array
     */
    public static function alterEvent(string $eventName, array $options): array
    {
        try {
            // 防止SQL注入，对参数进行安全处理
            $eventName = addslashes($eventName);
            
            if (isset($options['schedule'])) {
                $options['schedule'] = addslashes($options['schedule']);
            }
            
            if (!empty($options['start_time'])) {
                $options['start_time'] = addslashes($options['start_time']);
            }
            
            if (!empty($options['end_time'])) {
                $options['end_time'] = addslashes($options['end_time']);
            }
            
            if (isset($options['comment'])) {
                $options['comment'] = addslashes($options['comment']);
            }
            
            // 如果只是修改启用/禁用状态，使用专门的SQL语法
            if (isset($options['enable']) && count($options) === 1) {
                $status = $options['enable'] ? "ENABLE" : "DISABLE";
                $sql = "ALTER EVENT `{$eventName}` {$status}";
                
                // 执行修改事件状态
                Db::execute($sql);
                
                // 记录日志
                LogService::log("修改MySQL事件状态成功", [
                    'event' => $eventName, 
                    'status' => $status
                ]);
                
                return [
                    'code' => 200,
                    'msg' => "修改MySQL事件 '{$eventName}' 状态为 {$status} 成功",
                    'data' => null
                ];
            }
            
            // 构建事件修改SQL
            $sql = "ALTER EVENT `{$eventName}`";
            
            // 修改调度
            if (isset($options['schedule'])) {
                $sql .= " ON SCHEDULE {$options['schedule']}";
                
                // 添加开始时间
                if (!empty($options['start_time'])) {
                    $sql .= " STARTS '{$options['start_time']}'";
                }
                
                // 添加结束时间
                if (!empty($options['end_time'])) {
                    $sql .= " ENDS '{$options['end_time']}'";
                }
            }
            
            // 修改启用/禁用状态
            if (isset($options['enable'])) {
                $sql .= $options['enable'] ? " ENABLE" : " DISABLE";
            }
            
            // 修改注释
            if (isset($options['comment'])) {
                $sql .= " COMMENT '{$options['comment']}'";
            }
            
            // 修改执行SQL
            if (isset($options['sql_statement'])) {
                $sqlStatement = $options['sql_statement'];
                
                // 检查SQL语句是否已经包含BEGIN...END块
                if (stripos($sqlStatement, 'BEGIN') === 0) {
                    // 如果已经是BEGIN...END格式，直接使用
                    $eventSql = "DO " . $sqlStatement;
                } else {
                    // 否则添加BEGIN...END和日志记录
                    $eventSql = "DO BEGIN {$sqlStatement}; INSERT INTO bl_mysql_event_log (message, status, execution_time, error_message, affected_rows, execution_info, create_time) VALUES ('{$eventName}', 1, 0, '', ROW_COUNT(), '执行成功', NOW()); END";
                }
                
                $sql .= " " . $eventSql;
            }
            
            // 执行修改事件
            Db::execute($sql);
            
            // 记录日志
            LogService::log("修改MySQL事件成功", ['event' => $eventName, 'options' => $options]);
            
            return [
                'code' => 200,
                'msg' => "修改MySQL事件 '{$eventName}' 成功",
                'data' => null
            ];
        } catch (\Exception $e) {
            LogService::error("修改MySQL事件失败", ['error' => $e->getMessage()]);
            return [
                'code' => 500,
                'msg' => '修改MySQL事件失败：' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * 删除MySQL事件
     * 
     * @param string $eventName 事件名称
     * @return array
     */
    public static function dropEvent(string $eventName): array
    {
        try {
            // 删除事件
            Db::execute("DROP EVENT IF EXISTS `{$eventName}`");
            
            // 记录日志
            LogService::log("删除MySQL事件成功", ['event' => $eventName]);
            
            return [
                'code' => 200,
                'msg' => "删除MySQL事件 '{$eventName}' 成功",
                'data' => null
            ];
        } catch (\Exception $e) {
            LogService::error("删除MySQL事件失败", ['error' => $e->getMessage()]);
            return [
                'code' => 500,
                'msg' => '删除MySQL事件失败：' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * 启用/禁用事件调度器
     * 
     * @param bool $enable 是否启用事件调度器
     * @return array
     */
    public static function toggleEventScheduler(bool $enable): array
    {
        try {
            // 设置事件调度器状态
            $status = $enable ? "ON" : "OFF";
            Db::execute("SET GLOBAL event_scheduler = {$status}");
            
            // 记录日志
            LogService::log("设置MySQL事件调度器状态", ['status' => $status]);
            
            return [
                'code' => 200,
                'msg' => "设置MySQL事件调度器为 '{$status}' 成功",
                'data' => null
            ];
        } catch (\Exception $e) {
            LogService::error("设置MySQL事件调度器状态失败", ['error' => $e->getMessage()]);
            return [
                'code' => 500,
                'msg' => '设置MySQL事件调度器状态失败：' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * 获取事件调度器状态
     * 
     * @return array
     */
    public static function getEventSchedulerStatus(): array
    {
        try {
            // 查询事件调度器状态
            $result = Db::query("SHOW VARIABLES LIKE 'event_scheduler'");
            $status = $result[0]['Value'];
            
            // 记录日志
            LogService::log("获取MySQL事件调度器状态成功", ['status' => $status]);
            
            return [
                'code' => 200,
                'msg' => '获取MySQL事件调度器状态成功',
                'data' => [
                    'status' => $status,
                    'is_enabled' => strtoupper($status) === 'ON'
                ]
            ];
        } catch (\Exception $e) {
            LogService::error("获取MySQL事件调度器状态失败", ['error' => $e->getMessage()]);
            return [
                'code' => 500,
                'msg' => '获取MySQL事件调度器状态失败：' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * 检查当前用户是否有操作MySQL事件的权限
     * 
     * @return array
     */
    public static function checkEventPrivileges(): array
    {
        try {
            // 检查当前用户是否有创建事件的权限
            $result = Db::query("SHOW GRANTS FOR CURRENT_USER()");
            
            $hasEventPrivilege = false;
            $privileges = [];
            
            // 分析权限
            foreach ($result as $grant) {
                $grantStr = array_values($grant)[0];
                $privileges[] = $grantStr;
                
                // 检查是否有EVENT权限或SUPER权限或ALL PRIVILEGES
                if (
                    stripos($grantStr, 'EVENT') !== false || 
                    stripos($grantStr, 'SUPER') !== false || 
                    stripos($grantStr, 'ALL PRIVILEGES') !== false
                ) {
                    $hasEventPrivilege = true;
                }
            }
            
            // 记录日志
            LogService::log("检查MySQL事件权限", ['has_privilege' => $hasEventPrivilege]);
            
            return [
                'code' => 200,
                'msg' => $hasEventPrivilege ? '当前用户拥有事件调度权限' : '当前用户没有事件调度权限',
                'data' => [
                    'has_privilege' => $hasEventPrivilege,
                    'privileges' => $privileges
                ]
            ];
        } catch (\Exception $e) {
            LogService::error("检查MySQL事件权限失败", ['error' => $e->getMessage()]);
            return [
                'code' => 500,
                'msg' => '检查MySQL事件权限失败：' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * 获取MySQL事件调度系统状态
     * 
     * @return array
     */
    public static function getEventSystemStatus(): array
    {
        try {
            // 获取事件调度器状态
            $schedulerStatus = Db::query("SHOW VARIABLES LIKE 'event_scheduler'")[0]['Value'];
            
            // 检查权限
            $privilegeResult = self::checkEventPrivileges();
            $hasPrivilege = $privilegeResult['data']['has_privilege'] ?? false;
            
            // 检查事件功能是否可用
            $eventsEnabled = Db::query("SELECT @@event_scheduler AS enabled")[0]['enabled'];
            $eventsEnabled = (strtolower($eventsEnabled) === 'on' || $eventsEnabled === '1');
            
            // 获取数据库版本
            $version = Db::query("SELECT VERSION() AS version")[0]['version'];
            
            // 记录日志
            LogService::log("获取MySQL事件系统状态", [
                'scheduler_status' => $schedulerStatus,
                'has_privilege' => $hasPrivilege,
                'events_enabled' => $eventsEnabled
            ]);
            
            return [
                'code' => 200,
                'msg' => '获取MySQL事件系统状态成功',
                'data' => [
                    'scheduler_status' => $schedulerStatus,
                    'is_enabled' => (strtoupper($schedulerStatus) === 'ON'),
                    'has_privilege' => $hasPrivilege,
                    'events_supported' => true, // MySQL 5.1+支持事件
                    'events_enabled' => $eventsEnabled,
                    'db_version' => $version
                ]
            ];
        } catch (\Exception $e) {
            LogService::error("获取MySQL事件系统状态失败", ['error' => $e->getMessage()]);
            return [
                'code' => 500,
                'msg' => '获取MySQL事件系统状态失败：' . $e->getMessage(),
                'data' => null
            ];
        }
    }
} 