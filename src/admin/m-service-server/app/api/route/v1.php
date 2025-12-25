<?php
/**
 * V1 版本路由
 * 管理端接口
 */

use think\facade\Route;

// ============================================
// 用户模块
// ============================================
Route::group('/v1/user', function () {
    
    // 公开接口（无需登录）
    Route::group(function () {
        Route::rule('/login', 'v1.User/login');
        Route::rule('/register', 'v1.User/register');
        Route::rule('/requestPasswordReset', 'v1.User/requestPasswordReset');
        Route::rule('/verifyResetToken', 'v1.User/verifyResetToken');
        Route::rule('/resetPassword', 'v1.User/resetPassword');
        Route::rule('/sendEmailCode', 'v1.User/sendEmailCode');
        Route::rule('/testEmail', 'v1.User/testEmail');
    });
    
    // 需要登录的接口
    Route::group(function () {
        Route::rule('/selectUserInfoById', 'v1.User/selectUserInfoById');
        Route::rule('/selectUserListWithRoles', 'v1.User/selectUserListWithRoles');
        Route::rule('/add', 'v1.User/add');
        Route::rule('/update', 'v1.User/update');
        Route::rule('/delete', 'v1.User/delete');
        Route::rule('/restore', 'v1.User/restore');
        Route::rule('/activatePremium', 'v1.User/activatePremium');
        Route::rule('/cancelPremium', 'v1.User/cancelPremium');
        Route::rule('/getPremiumStatus', 'v1.User/getPremiumStatus');
        Route::rule('/changePassword', 'v1.User/changePassword');
        Route::rule('/logout', 'v1.User/logout');
        Route::rule('/refreshToken', 'v1.User/refreshToken');
    })->middleware([app\api\middleware\Auth::class]);
});

// 认证相关接口
Route::group('/v1/auth', function () {
    Route::rule('/refresh', 'v1.User/refreshToken');
});

// 资源分组
Route::group('/v1/resource', function () {
    Route::rule('selectResourceAll', 'v1.resource/selectResourceAll');
    Route::rule('add', 'v1.resource/add');
    Route::rule('selectResourceById', 'v1.resource/selectResourceById');
    Route::rule('update', 'v1.resource/update');
    Route::rule('delete', 'v1.resource/delete');
    Route::rule('restore', 'v1.resource/restore');
    Route::rule('forceDelete', 'v1.resource/forceDelete');
    Route::rule('getDeletedResources', 'v1.resource/getDeletedResources');
    Route::rule('testSoftDelete', 'v1.resource/testSoftDelete');
});

// 类别分组
Route::group('/v1/type', function () {
    Route::rule('selectTypeAll', 'v1.Type/selectTypeAll');
});

// 文章
Route::group('/v1/article', function () {
    Route::rule('getSummary', 'v1.article/getSummary');
    Route::rule('selectArticleAll', 'v1.article/selectArticleAll');
    Route::rule('add', 'v1.article/add');
    Route::rule('update', 'v1.article/update');
    Route::rule('delete', 'v1.article/delete');
    Route::rule('restore', 'v1.article/restore');
    Route::rule('getDeletedArticles', 'v1.article/getDeletedArticles');
    Route::rule('getArticleList', 'v1.article/getArticleList');
    Route::rule('selectArticleById', 'v1.article/selectArticleById');
})->middleware([app\api\middleware\ArticleAuth::class]);

// 评论组
Route::group('/v1/comments', function () {
    Route::rule('list', 'v1.comments/getList');
    Route::rule('stats', 'v1.comments/getStats');
    Route::rule('detail', 'v1.comments/detail');
    Route::rule('getTargetComments/:target_id/:target_type', 'v1.comments/getTargetComments');
    Route::rule('getCommentsByArticleId', 'v1.comments/getCommentsByArticleId');
    Route::rule('getComments/:article_id', 'v1.comments/getComments');
    Route::rule('getCommentsChildren/:parent_id', 'v1.comments/getCommentsChildren');
    Route::rule('add', 'v1.comments/add');
    Route::rule('update', 'v1.comments/update');
    Route::rule('delete', 'v1.comments/delete');
    Route::rule('batchDelete', 'v1.comments/batchDelete');
    Route::rule('approve', 'v1.comments/approve');
    Route::rule('reject', 'v1.comments/reject');
    Route::rule('batchApprove', 'v1.comments/batchApprove');
});

// 点赞组
Route::group('/v1/likes', function () {
    Route::rule('toggle', 'v1.likes/toggle');
    Route::rule('list', 'v1.likes/list');
});

// 收藏组
Route::group('/v1/favorites', function () {
    Route::get('list', 'v1.favorites/list');
    Route::post('toggle', 'v1.favorites/toggle');
});

// 文件分组
Route::group('/v1/upload', function () {
    Route::rule('uploadFile', 'v1.upload/uploadFile');
    Route::rule('deleteFiles', 'v1.upload/deleteFiles');
});

// 分类分组
Route::group('/v1/category', function () {
    Route::rule('selectCategoryAll', 'v1.category/selectCategoryAll');
    Route::rule('selectCategoryById', 'v1.category/selectCategoryById');
    Route::rule('add', 'v1.category/add');
    Route::rule('update', 'v1.category/update');
    Route::rule('delete', 'v1.category/delete');
    Route::rule('restore', 'v1.category/restore');
    Route::rule('getDeletedCategories', 'v1.category/getDeletedCategories');
});

// 路由分组
Route::group('/v1/route', function () {
    Route::rule('/getRouteByUid', 'v1.route/getRouteByUid');
});

// 角色分组
Route::group('/v1/roles', function () {
    Route::rule('/selectRolesAll', 'v1.roles/selectRolesAll');
    Route::rule('/selectRoleByUid', 'v1.roles/selectRoleByUid');
    Route::rule('/detail', 'v1.roles/detail');
    Route::rule('/add', 'v1.roles/add');
    Route::rule('/update', 'v1.roles/update');
    Route::rule('/delete', 'v1.roles/delete');
    Route::rule('/restore', 'v1.roles/restore');
    Route::rule('/updateStatus', 'v1.roles/updateStatus');
    Route::rule('/getPermissionsTree', 'v1.roles/getPermissionsTree');
    Route::rule('/setPermissions', 'v1.roles/setPermissions');
    Route::rule('/assignPermissions', 'v1.roles/assignPermissions');
});

// 菜单分组
Route::group('/v1/menus', function () {
    Route::rule('/selectMenuAll', 'v1.menu/selectMenuAll');
});

// 公告分组
Route::group('/v1/notice', function () {
    Route::rule('list', 'v1.notice/getNoticeList');
    Route::rule('detail/:notice_id', 'v1.notice/getNoticeById');
    Route::rule('create', 'v1.notice/createNotice');
    Route::rule('update/:notice_id', 'v1.notice/updateNotice');
    Route::rule('delete/:notice_id', 'v1.notice/deleteNotice');
    Route::rule('status/:notice_id', 'v1.notice/updateNoticeStatus');
    Route::rule('top/:notice_id', 'v1.notice/toggleNoticeTop');
    Route::rule('user/:user_id', 'v1.notice/getUserNotices');
    Route::rule('trashed', 'v1.notice/getTrashedNotices');
    Route::rule('restore/:notice_id', 'v1.notice/restoreNotice');
})->middleware([app\api\middleware\NoticeAuth::class]);

// 权限分组
Route::group('/v1/permissions', function () {
    Route::rule('/', 'v1.permissions/index');
    Route::rule('/tree', 'v1.permissions/tree');
    Route::rule('/add', 'v1.permissions/add');
    Route::rule('/update', 'v1.permissions/update');
    Route::rule('/delete', 'v1.permissions/delete');
    Route::rule('/restore', 'v1.permissions/restore');
    Route::rule('/parents', 'v1.permissions/parents');
    Route::rule('/children', 'v1.permissions/children');
    Route::rule('/levels', 'v1.permissions/levels');
    Route::rule('/categories', 'v1.permissions/categories');
    Route::rule('/fullTree', 'v1.permissions/fullTree');
    Route::rule('/assignApis', 'v1.permissions/assignApis');
    Route::rule('/getApis', 'v1.permissions/getApis');
});

// API日志分组
Route::group('/v1/apiLog', function () {
    Route::rule('list', 'v1.apiLog/index');
    Route::rule('detail', 'v1.apiLog/detail');
    Route::rule('stats', 'v1.apiLog/stats');
    Route::rule('clean', 'v1.apiLog/clean');
    Route::rule('user', 'v1.apiLog/userLogs');
    Route::rule('errors', 'v1.apiLog/errors');
    Route::rule('slow', 'v1.apiLog/slow');
});

// MySQL事件调度路由
Route::group('/v1/mysqlEvent', function () {
    Route::rule('list', 'v1.MySqlEvent/list');
    Route::rule('detail', 'v1.MySqlEvent/detail');
    Route::rule('create', 'v1.MySqlEvent/create');
    Route::rule('alter', 'v1.MySqlEvent/alter');
    Route::rule('drop', 'v1.MySqlEvent/drop');
    Route::rule('toggleEvent', 'v1.MySqlEvent/toggleEvent');
    Route::rule('toggleScheduler', 'v1.MySqlEvent/toggleScheduler');
    Route::rule('schedulerStatus', 'v1.MySqlEvent/schedulerStatus');
    Route::rule('checkPrivileges', 'v1.MySqlEvent/checkPrivileges');
    Route::rule('systemStatus', 'v1.MySqlEvent/systemStatus');
});

// MySQL事件日志路由
Route::group('/v1/mysqlEventLog', function () {
    Route::rule('list', 'v1.MySqlEvent/eventLogList');
    Route::rule('clear', 'v1.MySqlEvent/clearEventLog');
    Route::rule('statistics', 'v1.MySqlEvent/eventLogStatistics');
});

// 文件管理路由组
Route::group('/v1/file', function () {
    Route::rule('list', 'v1.File/getFileList');
    Route::rule('detail', 'v1.File/getFileById');
    Route::rule('delete', 'v1.File/deleteFile');
    Route::rule('restore', 'v1.File/restoreFile');
    Route::rule('forceDelete', 'v1.File/forceDeleteFile');
    Route::rule('batchDelete', 'v1.File/batchDeleteFiles');
    Route::rule('stats', 'v1.File/getFileStats');
    Route::rule('update', 'v1.File/updateFile');
    Route::rule('content', 'v1.File/readFileContent');
    Route::rule('proxy', 'v1.FileProxy/proxy');
});

// API管理路由组
Route::group('/v1/api', function () {
    Route::rule('list', 'v1.ApiManager/getApiList');
    Route::rule('detail', 'v1.ApiManager/getApiById');
    Route::rule('update', 'v1.ApiManager/updateApi');
    Route::rule('reset', 'v1.ApiManager/resetApis');
    Route::rule('status', 'v1.ApiManager/updateStatus');
    Route::rule('batchStatus', 'v1.ApiManager/batchUpdateStatus');
    Route::rule('statusOptions', 'v1.ApiManager/getStatusOptions');
    Route::rule('fixPaths', 'v1.ApiManager/fixApiPaths');
});

// 邮件记录路由组
Route::group('/v1/emailRecord', function () {
    Route::rule('list', 'v1.emailRecord/getList');
    Route::rule('detail', 'v1.emailRecord/getDetail');
    Route::rule('receivers', 'v1.emailRecord/getReceivers');
    Route::rule('send', 'v1.emailRecord/send');
    Route::rule('resend', 'v1.emailRecord/resend');
    Route::rule('delete', 'v1.emailRecord/delete');
    Route::rule('batchDelete', 'v1.emailRecord/batchDelete');
    Route::rule('restore', 'v1.emailRecord/restore');
    Route::rule('statistics', 'v1.emailRecord/getStatistics');
});

// 邮件模板路由组
Route::group('/v1/emailTemplate', function () {
    Route::rule('list', 'v1.EmailTemplate/list');
    Route::rule('detail', 'v1.EmailTemplate/detail');
    Route::rule('create', 'v1.EmailTemplate/create');
    Route::rule('update', 'v1.EmailTemplate/update');
    Route::rule('delete', 'v1.EmailTemplate/delete');
    Route::rule('restore', 'v1.EmailTemplate/restore');
    Route::rule('toggleStatus', 'v1.EmailTemplate/toggleStatus');
    Route::rule('active', 'v1.EmailTemplate/getActiveTemplates');
    Route::rule('sendTest', 'v1.EmailTemplate/sendTest');
    Route::rule('preview', 'v1.EmailTemplate/preview');
});

// 支付方式分组
Route::group('/v1/paymentMethod', function () {
    Route::rule('selectPaymentMethodAll', 'v1.paymentMethod/selectPaymentMethodAll');
    Route::rule('selectPaymentMethodById', 'v1.paymentMethod/selectPaymentMethodById');
    Route::rule('add', 'v1.paymentMethod/add');
    Route::rule('update', 'v1.paymentMethod/update');
    Route::rule('delete', 'v1.paymentMethod/delete');
    Route::rule('updateStatus', 'v1.paymentMethod/updateStatus');
    Route::rule('setDefault', 'v1.paymentMethod/setDefault');
    Route::rule('getEnabledList', 'v1.paymentMethod/getEnabledList');
});

// 主题配置路由组
Route::group('/v1/theme', function () {
    Route::rule('current', 'v1.Theme/current');
    Route::rule('list', 'v1.Theme/list');
    Route::rule('detail/:key', 'v1.Theme/detail');
    Route::rule('create', 'v1.Theme/create');
    Route::rule('update/:id', 'v1.Theme/update');
    Route::rule('delete/:id', 'v1.Theme/delete');
    Route::rule('set-current', 'v1.Theme/setCurrent');
    Route::rule('toggle-status/:id', 'v1.Theme/toggleStatus');
});

// 卡密管理路由组
Route::group('/v1/cardkey', function () {
    Route::post('generate', 'v1.CardKey/generate');
    Route::post('batchDelete', 'v1.CardKey/batchDelete');
    Route::post('batch', 'v1.CardKey/batchGenerate');
    Route::get('list', 'v1.CardKey/index');
    Route::get('detail/:id', 'v1.CardKey/detail');
    Route::get('types', 'v1.CardKey/getTypes');
    Route::get('logs/:id', 'v1.CardKey/logs');
    Route::get('allLogs', 'v1.CardKey/allLogs');
    Route::post('verify', 'v1.CardKey/verify');
    Route::post('use', 'v1.CardKey/use');
    Route::post('disable/:id', 'v1.CardKey/disable');
    Route::post('reset/:id', 'v1.CardKey/reset');
    Route::post('batchReset', 'v1.CardKey/batchReset');
    Route::delete('delete/:id', 'v1.CardKey/delete');
    Route::get('export', 'v1.CardKey/export');
    Route::get('statistics', 'v1.CardKey/statistics');
});

// 卡密类型管理路由组
Route::group('/v1/cardtype', function () {
    Route::get('list', 'v1.CardType/index');
    Route::get('enabled', 'v1.CardType/enabled');
    Route::get('detail/:id', 'v1.CardType/detail');
    Route::post('create', 'v1.CardType/create');
    Route::put('update/:id', 'v1.CardType/update');
    Route::delete('delete/:id', 'v1.CardType/delete');
    Route::post('batchDelete', 'v1.CardType/batchDelete');
});

// 捐赠记录管理路由组
Route::group('/v1/donation', function () {
    Route::rule('list', 'v1.Donation/getList');
    Route::rule('detail', 'v1.Donation/getDetail');
    Route::rule('getDeletedList', 'v1.Donation/getDeletedList');
    Route::rule('add', 'v1.Donation/add');
    Route::rule('update', 'v1.Donation/update');
    Route::rule('delete', 'v1.Donation/delete');
    Route::rule('batchDelete', 'v1.Donation/batchDelete');
    Route::rule('updateStatus', 'v1.Donation/updateStatus');
    Route::rule('statistics', 'v1.Donation/getStatistics');
    Route::rule('channelOptions', 'v1.Donation/getChannelOptions');
    Route::rule('statusOptions', 'v1.Donation/getStatusOptions');
    Route::rule('query', 'v1.Donation/queryByContact');
});

// 等级管理接口
Route::group('/v1/level', function () {
    Route::rule('list', 'v1.level/list');
    Route::rule('detail', 'v1.level/detail');
    Route::rule('types', 'v1.level/types');
    Route::rule('getLevelByExperience', 'v1.level/getLevelByExperience');
    Route::rule('add', 'v1.level/add');
    Route::rule('update', 'v1.level/update');
    Route::rule('delete', 'v1.level/delete');
    Route::rule('setStatus', 'v1.level/setStatus');
    Route::rule('batchDelete', 'v1.level/batchDelete');
    Route::rule('batchStatus', 'v1.level/batchStatus');
   
})->middleware([app\api\middleware\Auth::class]);

// 经验值管理接口
Route::group('/v1/experience', function () {
    Route::rule('logs', 'v1.experience/logs');
    Route::rule('levelInfo', 'v1.experience/levelInfo');
    Route::rule('userAllLevels', 'v1.experience/userAllLevels');
    Route::rule('sources', 'v1.experience/sources');
    Route::rule('sourceStats', 'v1.experience/sourceStats');
    Route::rule('records', 'v1.experience/records');
    Route::rule('add', 'v1.experience/add');
    Route::rule('revoke', 'v1.experience/revoke');
})->middleware([app\api\middleware\Auth::class]);
