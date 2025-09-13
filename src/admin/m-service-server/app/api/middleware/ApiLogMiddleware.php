<?php

namespace app\api\middleware;

use app\api\services\ApiLogService;
use think\facade\Request;
use think\facade\Session;

class ApiLogMiddleware
{
    /**
     * 处理请求
     *
     * @param \think\Request $request
     * @param \Closure $next
     * @return \think\Response
     */
    public function handle($request, \Closure $next)
    {
        // 记录开始时间
        $startTime = microtime(true);
        
        // 先执行下一个中间件或控制器操作
        $response = $next($request);
        
        // 计算执行时间（毫秒）
        $executionTime = (microtime(true) - $startTime) * 1000;
        
        // 获取用户ID（如果已登录）
        $userId = null;
        if (Session::has('user_id')) {
            $userId = Session::get('user_id');
        }
        
        // 获取响应状态码
        $statusCode = $response->getCode();
        
        // 获取业务错误码（如果响应中包含）
        $errorCode = null;
        $responseData = json_decode($response->getContent(), true);
        if (is_array($responseData) && isset($responseData['code']) && $responseData['code'] != 200) {
            $errorCode = (string)$responseData['code'];
        }
        
        // 获取请求参数（移除敏感信息）
        $requestParams = $request->param();
        if (isset($requestParams['password'])) {
            $requestParams['password'] = '******';
        }
        if (isset($requestParams['token'])) {
            $requestParams['token'] = '******';
        }
        
        // 获取API密钥（如果在请求头中）
        $apiKey = $request->header('x-api-key');
        
        // 记录API日志
        ApiLogService::record(
            $userId,
            $apiKey,
            $statusCode,
            $errorCode,
            $executionTime,
            $requestParams
        );
        
        // 返回响应
        return $response;
    }
} 