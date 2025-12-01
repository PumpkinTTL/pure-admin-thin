<?php

namespace app\api\controller\v2;

use app\BaseController;
use app\api\model\users as userModel;
use app\api\services\v2\UserService;
use app\api\services\LogService;
use think\response\Json;
use think\validate\ValidateRule;
use think\facade\Validate;

/**
 * 客户端用户控制器
 * 专注于客户端用户的简单CRUD操作
 * 登录、注册等基础功能复用v1接口
 */
class User extends BaseController
{
    /**
     * 获取当前用户信息
     * 客户端专用，返回经过过滤的用户数据
     * @return Json
     */
    public function profile(): Json
    {
        try {
            // 从中间件获取用户ID
            $userId = request()->JWTUid;

            if (!$userId) {
                return json(['code' => 401, 'msg' => '未授权，请重新登录']);
            }

            // 调用v2 UserService获取用户信息
            $result = UserService::getUserInfo($userId);

            if ($result['code'] !== 200) {
                return json($result);
            }

            // 过滤敏感信息，只返回客户端需要的数据
            $userData = $result['data'];
            $clientData = [
                'id' => $userData['id'],
                'username' => $userData['username'] ?? '',
                'nickname' => $userData['nickname'] ?? '',
                'email' => $userData['email'] ?? '',
                'avatar' => $userData['avatar'] ?? '',
                'gender' => $userData['gender'] ?? 0,
                'signature' => $userData['signature'] ?? '',
                'phone' => $userData['phone'] ?? '', // 已脱敏处理
                'status' => $userData['status'] ?? 1,
                'last_login' => $userData['last_login'] ?? '',
                'register_source' => $userData['register_source'] ?? '',
                'roles' => $userData['roles'] ?? [],
                'premium' => $userData['premium'] ?? null
            ];

            return json([
                'code' => 200,
                'msg' => '获取用户信息成功',
                'data' => $clientData
            ]);

        } catch (\Exception $e) {
            LogService::error($e);
            return json(['code' => 500, 'msg' => '获取用户信息失败：' . $e->getMessage()]);
        }
    }

    /**
     * 更新用户资料
     * 客户端专用，只允许更新安全字段
     * @return Json
     */
    public function update(): Json
    {
        try {
            // 从中间件获取用户ID
            $currentUserId = request()->JWTUid;

            if (!$currentUserId) {
                return json(['code' => 401, 'msg' => '未授权，请重新登录']);
            }

            // 获取请求参数
            $params = request()->param();

            // 参数验证 - 只允许更新安全字段
            $validate = Validate::rule([
                'nickname' => 'max:255|chsDash', // 昵称：最大255字符，允许中英文、数字、下划线、减号
                'gender' => 'in:0,1,2', // 性别：0=未知，1=男，2=女
                'signature' => 'max:500', // 签名：最大500字符
                'avatar' => 'url', // 头像：必须是有效URL
            ]);

            $validateResult = $validate->batch()->check($params);
            if (!$validateResult) {
                return json(['code' => 501, 'msg' => '参数错误', 'info' => $validate->getError()]);
            }

            // 调用v2 UserService更新用户资料（只更新安全字段，不影响角色等信息）
            $result = UserService::updateUserProfile($currentUserId, $params);

            // 记录操作日志
            LogService::log("用户更新个人资料：用户ID {$currentUserId}");

            return json($result);

        } catch (\Exception $e) {
            LogService::error($e);
            return json(['code' => 500, 'msg' => '更新资料失败：' . $e->getMessage()]);
        }
    }

    /**
     * 获取会员状态
     * 客户端专用
     * @return Json
     */
    public function membership(): Json
    {
        try {
            // 从中间件获取用户ID
            $currentUserId = request()->JWTUid;

            if (!$currentUserId) {
                return json(['code' => 401, 'msg' => '未授权，请重新登录']);
            }

            // 调用v2 UserService检查会员状态
            $result = UserService::checkPremiumStatus($currentUserId);

            return json($result);

        } catch (\Exception $e) {
            LogService::error($e);
            return json(['code' => 500, 'msg' => '获取会员状态失败：' . $e->getMessage()]);
        }
    }

    /**
     * 注销账号
     * 客户端专用，软删除用户账号
     * @return Json
     */
    public function delete(): Json
    {
        try {
            // 从中间件获取用户ID
            $currentUserId = request()->JWTUid;

            if (!$currentUserId) {
                return json(['code' => 401, 'msg' => '未授权，请重新登录']);
            }

            // 获取请求参数（密码确认）
            $params = request()->param();

            // 参数验证 - 需要密码确认
            $validate = Validate::rule([
                'password' => ValidateRule::isRequire(null, '密码不能为空'),
                'confirmation' => ValidateRule::isRequire(null, '确认文本不能为空')
                    ->in(['我确认删除账号'], '确认文本不正确'),
            ]);

            $validateResult = $validate->batch()->check($params);
            if (!$validateResult) {
                return json(['code' => 501, 'msg' => '参数错误', 'info' => $validate->getError()]);
            }

            // 调用v2 UserService软删除用户
            $result = UserService::deleteAccount($currentUserId, $params['password']);

            if ($result['code'] === 200) {
                // 记录操作日志
                LogService::log("用户主动注销账号：用户ID {$currentUserId}");

                return json([
                    'code' => 200,
                    'msg' => '账号已注销，所有数据将被保留30天后永久删除'
                ]);
            }

            return json($result);

        } catch (\Exception $e) {
            LogService::error($e);
            return json(['code' => 500, 'msg' => '注销账号失败：' . $e->getMessage()]);
        }
    }

    /**
     * 检查用户名是否可用
     * 用于注册时实时验证
     * @return Json
     */
    public function checkUsername(): Json
    {
        try {
            $params = request()->param();

            // 参数验证
            $validate = Validate::rule([
                'username' => ValidateRule::isRequire(null, '用户名不能为空')
                    ->min(3, '用户名长度不能少于3位')
                    ->max(50, '用户名长度不能超过50位')
                    ->regex('/^[a-zA-Z0-9_\-]+$/', '用户名只能包含字母、数字、下划线和减号'),
            ]);

            $validateResult = $validate->batch()->check($params);
            if (!$validateResult) {
                return json(['code' => 501, 'msg' => '参数错误', 'info' => $validate->getError()]);
            }

            $username = $params['username'];

            // 调用v2 UserService检查用户名
            $result = UserService::checkUsernameAvailable($username);

            return json($result);

        } catch (\Exception $e) {
            LogService::error($e);
            return json(['code' => 500, 'msg' => '检查用户名失败：' . $e->getMessage()]);
        }
    }

    /**
     * 检查邮箱是否可用
     * 用于注册时实时验证
     * @return Json
     */
    public function checkEmail(): Json
    {
        try {
            $params = request()->param();

            // 参数验证
            $validate = Validate::rule([
                'email' => ValidateRule::isRequire(null, '邮箱不能为空')
                    ->email('邮箱格式不正确'),
            ]);

            $validateResult = $validate->batch()->check($params);
            if (!$validateResult) {
                return json(['code' => 501, 'msg' => '参数错误', 'info' => $validate->getError()]);
            }

            $email = $params['email'];

            // 调用v2 UserService检查邮箱
            $result = UserService::checkEmailAvailable($email);

            return json($result);

        } catch (\Exception $e) {
            LogService::error($e);
            return json(['code' => 500, 'msg' => '检查邮箱失败：' . $e->getMessage()]);
        }
    }

    /**
     * 用户登出
     * 客户端专用
     * @return Json
     */
    public function logout(): Json
    {
        try {
            // 从中间件获取用户ID
            $userId = request()->JWTUid;

            if (!$userId) {
                return json(['code' => 401, 'msg' => '未授权，请重新登录']);
            }

            // 调用v2 UserService退出登录
            $result = UserService::logout($userId);

            return json($result);

        } catch (\Exception $e) {
            LogService::error($e);
            return json(['code' => 500, 'msg' => '退出登录失败：' . $e->getMessage()]);
        }
    }
}