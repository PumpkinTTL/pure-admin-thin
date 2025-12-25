<?php
/**
 * V2 版本路由
 * 客户端接口
 */

use think\facade\Route;

// ============================================
// 用户模块 - 客户端
// ============================================
Route::group('/v2/user', function () {
    
    // 公开接口（无需登录）
    Route::group(function () {
        Route::rule('/checkUsername', 'v2.User/checkUsername');
        Route::rule('/checkEmail', 'v2.User/checkEmail');
    });
    
    // 需要登录的接口
    Route::group(function () {
        Route::rule('/profile', 'v2.User/profile');
        Route::rule('/update', 'v2.User/update');
        Route::rule('/membership', 'v2.User/membership');
        Route::rule('/delete', 'v2.User/delete');
        Route::rule('/logout', 'v2.User/logout');
    })->middleware([app\api\middleware\Auth::class]);
});
