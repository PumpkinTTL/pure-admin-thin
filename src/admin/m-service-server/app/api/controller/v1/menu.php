<?php

namespace app\api\controller\v1;

use app\api\services\MenuServices;
use app\BaseController;

class menu extends BaseController
{
    function selectMenuAll()
    {


        $userMenu = MenuServices::getMenuTree(); // 传入用户角色ID
        return json(['code' => 200, 'data' => $userMenu]);
    }
}