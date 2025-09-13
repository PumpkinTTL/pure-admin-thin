<?php

namespace app\api\services;

use app\api\model\types;
use think\facade\Db;

class TypeServices
{
    public static function selectTypeAll($map = [])
    {

        return Db::name('types')->where($map)->select()->order('create_time', 'desc');
    }
}