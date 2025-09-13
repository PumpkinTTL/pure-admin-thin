<?php

namespace app\api\validate;

use think\Validate;

class UserValidate extends Validate
{
    protected $rule = [
        'action' => 'require',
        'account' => 'require|min:3',
        'password' => 'require',
    ];

    protected $message = [
        'action.require' => '登录方式必须传递',
        'account.require' => '登录账号不能为空',
        'code' => 500
    ];
}