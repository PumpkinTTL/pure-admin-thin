<?php

namespace app\api\controller\v1;

use app\api\middleware\Auth;
use app\api\middleware\Sign;
use app\api\middleware\UserPermissions;
use app\api\services\TypeServices;
use app\BaseController;

class Type extends BaseController
{
    protected $middleware = [Auth::class => ['except' => 'selectTypeAll'],
        Sign::class => [
            'except' => ['selectTypeAll'],
        ],
        UserPermissions::class => ['except' => 'selectTypeAll']];
    function selectTypeAll(): \think\response\Json
    {
        $res = TypeServices::selectTypeAll();
        return json(['code' => 200, 'msg' => 'ok', 'data' => $res]);
    }
}