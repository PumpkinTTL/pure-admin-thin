<?php

namespace app\api\middleware;

use app\api\model\users;

class UserPermissions
{
    public function handle($request, \Closure $next)
    {
//        当前用户id
        $currentId = request()->param('currentId');
//        查询权限
        $currentUser = users::with('roles')->find($currentId);
//        return json(['data' => $currentUser['roles']]);
        return $next($request);
    }
}