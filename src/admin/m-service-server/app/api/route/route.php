<?php

use think\facade\Route;

//用户分组
Route::group('/:version/user', function () {
    Route::rule('/login', ':version.User/login');
    Route::rule('/V2Login', ':version.User/V2Login');
    Route::rule('/dualTokenLogin', ':version.User/login'); // 双token登录
    Route::rule('/refreshToken', ':version.User/refreshToken'); // 刷新token
    Route::rule('/logout', ':version.User/logout'); // 登出
    Route::rule('/forceLogoutUser', ':version.User/forceLogoutUser'); // 强制失效用户token
    Route::rule('/selectUserInfoById', ':version.User/selectUserInfoById');
    Route::rule('/add', ':version.User/add');
    Route::rule('/delete', ':version.User/delete');
    Route::rule('/restore', ':version.User/restore');
    Route::rule('/update', ':version.User/update');
    Route::rule('/outLogin', ':version.User/outLogin');
    Route::rule('/selectUserListWithRoles', ':version.User/selectUserListWithRoles');
});

// 认证相关接口
Route::group('/:version/auth', function () {
    Route::rule('/refresh', ':version.User/refreshToken'); // 刷新token - 指向User控制器
});
// 需要双token认证的用户接口（直接在控制器中验证）
Route::group('/:version/user', function () {
    Route::rule('/info', ':version.User/info'); // 获取用户信息
    Route::rule('/testToken', ':version.User/testToken'); // 测试token读取
});
//资源分组
Route::group('/:version/resource', function () {
    Route::rule('selectResourceAll', ':version.resource/selectResourceAll');
    Route::rule('add', ':version.resource/add');
    Route::rule('selectResourceById', ':version.resource/selectResourceById');
    Route::rule('update', ':version.resource/update');
    Route::rule('delete', ':version.resource/deleteResource');
    Route::rule('restore', ':version.resource/restore');
    Route::rule('forceDelete', ':version.resource/forceDeleteResource');
    Route::rule('getDeletedResources', ':version.resource/getDeletedResources');
    Route::rule('testSoftDelete', ':version.resource/testSoftDelete');
});
//类别分组
Route::group('/:version/type', function () {
    Route::rule('selectTypeAll', ':version.Type/selectTypeAll');
    Route::rule('/selectTypeInfoById', ':version.Type/selectTypeInfoById');
});
//文章
Route::group('/:version/article', function () {
    Route::rule('getSummary', ':version.article/getSummary');
    Route::rule('selectArticleAll', ':version.article/selectArticleAll');
    Route::rule('add', ':version.article/add');
    Route::rule('update', ':version.article/update');
    Route::rule('delete', ':version.article/delete');
    Route::rule('restore', ':version.article/restore');
    Route::rule('getDeletedArticles', ':version.article/getDeletedArticles');
    //    客户端
    Route::rule('getArticleList', ':version.article/getArticleList');
    Route::rule('selectArticleById', ':version.article/selectArticleById');
});
// 评论组
Route::group('/:version/comments', function () {
    Route::rule('getCommentsByArticleId', ':version.comments/getCommentsByArticleId');
    Route::rule('getComments/:article_id', ':version.comments/getComments');
    Route::rule('getCommentsChildren/:parent_id', ':version.comments/getCommentsChildren');
});
//文件分组
Route::group('/:version/upload', function () {
    Route::rule('uploadFile', ':version.upload/uploadFile');
    Route::rule('deleteFiles', ':version.upload/deleteFiles');
});
//分类分组
Route::group('/:version/category', function () {
    Route::rule('selectCategoryAll', ':version.category/selectCategoryAll');
    Route::rule('selectCategoryById', ':version.category/selectCategoryById');
    Route::rule('add', ':version.category/add');
    Route::rule('update', ':version.category/update');
    Route::rule('delete', ':version.category/delete');
    Route::rule('restore', ':version.category/restore');
    Route::rule('getDeletedCategories', ':version.category/getDeletedCategories');
    Route::rule('testSoftDelete', ':version.category/testSoftDelete');
    Route::rule('testDelete', ':version.category/testDelete');
});
//路由分组
Route::group('/:version/route', function () {
    Route::rule('/getRouteByUid', ':version.route/getRouteByUid');
});

//角色分组
Route::group('/:version/roles', function () {
    Route::rule('/selectRolesAll', ':version.roles/selectRolesAll');
    Route::rule('/selectRoleByUid', ':version.roles/selectRoleByUid');
    Route::rule('/detail', ':version.roles/detail');
    Route::rule('/add', ':version.roles/add');
    Route::rule('/update', ':version.roles/update');
    Route::rule('/delete', ':version.roles/delete');
    Route::rule('/restore', ':version.roles/restore');
    Route::rule('/updateStatus', ':version.roles/updateStatus');
    Route::rule('/getPermissionsTree', ':version.roles/getPermissionsTree');
    Route::rule('/setPermissions', ':version.roles/setPermissions');
    Route::rule('/assignPermissions', ':version.roles/assignPermissions');
});

//菜单分组
Route::group('/:version/menus', function () {
    Route::rule('/selectMenuAll', ':version.menu/selectMenuAll');
});

//公告分组
Route::group('/:version/notice', function () {
    Route::rule('list', ':version.notice/getNoticeList');
    Route::rule('detail/:notice_id', ':version.notice/getNoticeById');
    Route::rule('create', ':version.notice/createNotice');
    Route::rule('update/:notice_id', ':version.notice/updateNotice');
    Route::rule('delete/:notice_id', ':version.notice/deleteNotice');
    Route::rule('status/:notice_id', ':version.notice/updateNoticeStatus');
    Route::rule('top/:notice_id', ':version.notice/toggleNoticeTop');
    Route::rule('user/:user_id', ':version.notice/getUserNotices');
    Route::rule('trashed', ':version.notice/getTrashedNotices');
    Route::rule('restore/:notice_id', ':version.notice/restoreNotice');
})->middleware([\app\api\middleware\PermissionCheck::class]);

//权限分组
Route::group('/:version/permissions', function () {
    Route::rule('/', ':version.permissions/index');
    Route::rule('/tree', ':version.permissions/tree');
    Route::rule('/add', ':version.permissions/add');
    Route::rule('/update', ':version.permissions/update');
    Route::rule('/delete', ':version.permissions/delete');
    Route::rule('/restore', ':version.permissions/restore');
    Route::rule('/parents', ':version.permissions/parents');
    Route::rule('/children', ':version.permissions/children');
    Route::rule('/levels', ':version.permissions/levels');
    Route::rule('/categories', ':version.permissions/categories');
    Route::rule('/fullTree', ':version.permissions/fullTree');
    Route::rule('/assignApis', ':version.permissions/assignApis');
    Route::rule('/getApis', ':version.permissions/getApis');
});

//API日志分组
Route::group('/:version/apiLog', function () {
    Route::rule('list', ':version.apiLog/index');
    Route::rule('detail', ':version.apiLog/detail');
    Route::rule('stats', ':version.apiLog/stats');
    Route::rule('clean', ':version.apiLog/clean');
    Route::rule('user', ':version.apiLog/userLogs');
    Route::rule('errors', ':version.apiLog/errors');
    Route::rule('slow', ':version.apiLog/slow');
});

// MySQL事件调度路由 (开发阶段暂不启用鉴权)
Route::group('/:version/mysqlEvent', function () {
    Route::rule('list', ':version.MySqlEvent/list');
    Route::rule('detail', ':version.MySqlEvent/detail');
    Route::rule('create', ':version.MySqlEvent/create');
    Route::rule('alter', ':version.MySqlEvent/alter');
    Route::rule('drop', ':version.MySqlEvent/drop');
    Route::rule('toggleEvent', ':version.MySqlEvent/toggleEvent');
    Route::rule('toggleScheduler', ':version.MySqlEvent/toggleScheduler');
    Route::rule('schedulerStatus', ':version.MySqlEvent/schedulerStatus');
    Route::rule('checkPrivileges', ':version.MySqlEvent/checkPrivileges');
    Route::rule('systemStatus', ':version.MySqlEvent/systemStatus');
});

// MySQL事件日志路由
Route::group('/:version/mysqlEventLog', function () {
    Route::rule('list', ':version.MySqlEvent/eventLogList');
    Route::rule('clear', ':version.MySqlEvent/clearEventLog');
    Route::rule('statistics', ':version.MySqlEvent/eventLogStatistics');
});

// 文件管理路由组
Route::group('/:version/file', function () {
    // 获取文件列表
    Route::rule('list', ':version.File/getFileList');
    // 获取文件详情
    Route::rule('detail', ':version.File/getFileById');
    // 软删除文件
    Route::rule('delete', ':version.File/deleteFile');
    // 恢复被删除的文件
    Route::rule('restore', ':version.File/restoreFile');
    // 永久删除文件
    Route::rule('forceDelete', ':version.File/forceDeleteFile');
    // 批量删除文件
    Route::rule('batchDelete', ':version.File/batchDeleteFiles');
    // 获取文件统计信息
    Route::rule('stats', ':version.File/getFileStats');
});

// 接口管理路由组
Route::group('/:version/api', function () {
    // 获取接口列表
    Route::rule('list', ':version.ApiManager/getApiList');
    // 获取接口详情
    Route::rule('detail', ':version.ApiManager/getApiById');
    // 更新接口信息
    Route::rule('update', ':version.ApiManager/updateApi');
    // 重置接口数据
    Route::rule('reset', ':version.ApiManager/resetApis');
    // 更新接口状态
    Route::rule('status', ':version.ApiManager/updateStatus');
    // 批量更新接口状态
    Route::rule('batchStatus', ':version.ApiManager/batchUpdateStatus');
    // 获取状态选项
    Route::rule('statusOptions', ':version.ApiManager/getStatusOptions');
    // 修复API路径格式
    Route::rule('fixPaths', ':version.ApiManager/fixApiPaths');
});

//支付方式分组
Route::group('/:version/paymentMethod', function () {
    Route::rule('selectPaymentMethodAll', ':version.paymentMethod/selectPaymentMethodAll');
    Route::rule('selectPaymentMethodById', ':version.paymentMethod/selectPaymentMethodById');
    Route::rule('add', ':version.paymentMethod/add');
    Route::rule('update', ':version.paymentMethod/update');
    Route::rule('delete', ':version.paymentMethod/delete');
    Route::rule('updateStatus', ':version.paymentMethod/updateStatus');
    Route::rule('setDefault', ':version.paymentMethod/setDefault');
    Route::rule('getEnabledList', ':version.paymentMethod/getEnabledList');
});

// 主题配置路由组
Route::group('/:version/theme', function () {
    // ========== 客户端API ==========
    Route::rule('current', ':version.Theme/current');                    // 获取当前主题
    Route::rule('list', ':version.Theme/list');                         // 获取主题列表
    Route::rule('detail/:key', ':version.Theme/detail');                // 获取指定主题

    // ========== 管理端API ==========
    Route::rule('create', ':version.Theme/create');                     // 创建主题
    Route::rule('update/:id', ':version.Theme/update');                 // 更新主题
    Route::rule('delete/:id', ':version.Theme/delete');                 // 删除主题
    Route::rule('set-current', ':version.Theme/setCurrent');            // 设置当前主题
    Route::rule('toggle-status/:id', ':version.Theme/toggleStatus');    // 切换状态
});
