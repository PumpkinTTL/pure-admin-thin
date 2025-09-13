<?php
namespace app\api\services;

use think\facade\Log;

/**
 * 日志服务类
 */
class LogService
{
    /**
     * 记录普通日志
     * @param mixed $message 日志信息
     * @param array $context 上下文信息
     * @param string $level 日志级别
     * @return void
     */
    public static function log($message, array $context = [], string $level = 'info'): void
    {
        Log::log($level, $message, $context);
    }

    /**
     * 记录SQL日志
     * @param string $sql SQL语句
     * @param array $params SQL参数
     * @param float $time 执行时间(毫秒)
     * @return void
     */
    public static function sql(string $sql, array $params = [], float $time = 0.0): void
    {
        $data = [
            'sql' => $sql,
            'params' => $params,
            'time' => $time . 'ms',
        ];
        
        Log::channel('sql')->info(json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    /**
     * 记录错误日志
     * @param mixed $message 错误信息
     * @param array $context 上下文信息
     * @return void
     */
    public static function error($message, array $context = []): void
    {
        if ($message instanceof \Throwable) {
            $context['file'] = $message->getFile();
            $context['line'] = $message->getLine();
            $context['code'] = $message->getCode();
            $context['trace'] = $message->getTraceAsString();
            $message = $message->getMessage();
        }
        
        Log::channel('error')->error($message, $context);
    }

    /**
     * 记录警告日志
     * @param mixed $message 警告信息
     * @param array $context 上下文信息
     * @return void
     */
    public static function warning($message, array $context = []): void
    {
        Log::warning($message, $context);
    }

    /**
     * 记录调试日志
     * @param mixed $message 调试信息
     * @param array $context 上下文信息
     * @return void
     */
    public static function debug($message, array $context = []): void
    {
        Log::debug($message, $context);
    }

    /**
     * 记录请求日志
     * @param array $data 请求数据
     * @return void
     */
    public static function request(array $data): void
    {
        Log::info(json_encode(['请求日志' => $data], JSON_UNESCAPED_UNICODE));
    }
} 