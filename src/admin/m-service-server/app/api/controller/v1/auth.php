<?php

namespace app\api\controller\v1;

use app\BaseController;
use think\response\Json;

class auth extends BaseController
{
    function test(): Json
    {
        return json(['code' => 200, 'msg' => 'ok']);
    }
}