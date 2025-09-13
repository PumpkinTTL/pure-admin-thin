<?php

use think\facade\Route;


Route::group('api/v1', function () {
    Route::post('user/login', 'v1/User/@login');
});
