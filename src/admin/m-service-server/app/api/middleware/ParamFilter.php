<?php

namespace app\api\middleware;

class ParamFilter
{
    public function handle($request, \Closure $next)
    {
        unset($request['version']);
        return $next($request);
    }
}