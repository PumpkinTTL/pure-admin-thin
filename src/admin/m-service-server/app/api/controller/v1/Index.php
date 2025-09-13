<?php

namespace app\api\controller\v1;

use app\api\middleware\Auth;
use app\BaseController;

class Index extends BaseController
{
    protected $middleware = [Auth::class];

    public function index(): string
    {

        return 'v1版本控制';
    }
}