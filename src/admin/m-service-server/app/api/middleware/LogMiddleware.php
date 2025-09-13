<?php

namespace app\api\middleware;

use think\facade\Log;
class LogMiddleware
{
    public function handle($request, \Closure $next)
    {
        // 先执行下一个中间件或控制器操作
        $response = $next($request);
        
        // 记录日志
        $logData = [
            'url' => $request->url(),
            'method' => $request->method(),
            'params' => $request->param(),
            'response' => $response->getContent(),
            'ip' => $request->ip(),
            'user_agent' => $request->header('user-agent'),
        ];
        
        // 记录请求日志
        Log::info(json_encode($logData));

        // 直接返回已经处理过的响应
        return $response;
    }
}
