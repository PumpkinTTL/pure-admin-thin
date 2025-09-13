<?php

namespace app\api\controller\v1;

use app\api\model\users;
use app\BaseController;
use think\response\Json;

class route extends BaseController
{
//    根据用户角色id查询角色获取全权限菜单
    function getRouteByUid(): Json
    {
        $uid = request()->param('uid');
//        查询当前用户的角色信息
        $roles = users::with(['roles.permissions' => function ($query) {

        }])->find($uid);
        $data = ['code' => 200, 'data' => $roles];
        return \json($data);
    }
}