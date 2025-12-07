<?php

namespace app\api\controller\v1;

use app\api\middleware\Auth;
use app\api\middleware\ParamFilter;
use app\api\model\userRoles;
use app\api\model\users as userModel;
use app\api\services\RoleServices;
use app\api\services\UserService;
use app\api\services\LogService;
use app\BaseController;

use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Db;
use think\facade\Validate;
use think\response\Json;
use think\validate\ValidateRule;
use utils\JWTUtil;
use utils\RedisUtil;
use utils\SecretUtil;
use utils\Auth as AuthUtil;

class User extends BaseController
{
    private static string $PREFIX_ACCESS_TOKEN = "ACCESS_TOKEN_";
    //    protected $middleware = [Auth::class => ['except' => 'login']];

    /**
     * 用户登录
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     * @throws DbException
     */
    function login(): Json
    {
        $params = request()->param();

        // 基础参数验证
        $validate = Validate::rule([
            'action' => ValidateRule::isRequire(null, '登录方式必须传递'),
            'account' => ValidateRule::isRequire(null, '登录账号必须传递'),
            'password' => ValidateRule::isRequire(null, '密码必须传递,如是验证码请传递验证码'),
        ]);

        $validateResult = $validate->batch()->check($params);
        if (!$validateResult) {
            return json(['code' => 501, 'msg' => '参数错误', 'info' => $validate->getError()]);
        }

        $account = $params['account'];
        $password = $params['password'];
        $action = $params['action'];
        $inviteCode = $params['invite_code'] ?? null; // 可选的邀请码参数

        // 调用服务进行登录（统一处理所有登录方式）
        $result = UserService::login($account, $password, $action, 'Web', $inviteCode);
        return json($result);
    }

    /**
     * 发送邮箱验证码
     */
    function sendEmailCode(): Json
    {
        $params = request()->param();

        $validate = Validate::rule([
            'email' => ValidateRule::isRequire(null, '邮箱必须传递')->regex('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', '邮箱格式不正确'),
        ]);

        $validateResult = $validate->batch()->check($params);
        if (!$validateResult) {
            return json(['code' => 501, 'msg' => '参数错误', 'info' => $validate->getError()]);
        }

        // 调用服务发送验证码
        $result = UserService::sendEmailCode($params['email']);
        return json($result);
    }


    /**
     * 测试邮件发送
     */
    function testEmail(): Json
    {
        try {
            // 使用配置中的发件人邮箱进行测试
            $testEmail = config('email.from_address');
            $result = \utils\EmailUtil::sendMail($testEmail, "测试邮件", "<h1>这是一封测试邮件</h1>");

            if ($result) {
                return json(['code' => 200, 'msg' => "测试邮件发送成功到 {$testEmail}"]);
            } else {
                return json(['code' => 500, 'msg' => '测试邮件发送失败']);
            }
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => '测试邮件发送异常：' . $e->getMessage()]);
        }
    }

    /**
     * 查询用户信息
     * 支持管理员查询任意用户，普通用户只能查询自己
     */
    function selectUserInfoById(): Json
    {
        // 参数验证
        $validate = Validate::rule([
            'targetUid' => ValidateRule::isRequire(null, '目标用户用户id必须传递')
        ]);
        $validateResult = $validate->batch()->check(request()->param());
        if (!$validateResult) {
            return json(['code' => 501, 'msg' => '参数错误', 'info' => $validate->getError()]);
        }

        $targetUid = request()->param('targetUid');
        $currentUid = request()->JWTUid ?? 0;

        // ✅ 数据权限检查
        $permissionCheck = $this->checkUserDataPermission($currentUid, $targetUid);
        if (!$permissionCheck['allowed']) {
            return json(['code' => 403, 'msg' => $permissionCheck['message']]);
        }

        // 调用服务获取用户信息
        $result = UserService::getUserInfo($targetUid);

        return json($result);
    }

    /**
     * 检查用户数据权限
     * @param int $currentUid 当前用户ID
     * @param int $targetUid 目标用户ID
     * @return array ['allowed' => bool, 'message' => string]
     */
    private function checkUserDataPermission(int $currentUid, int $targetUid): array
    {
        try {
            // 获取当前用户的权限信息
            $authInfo = \utils\AuthUtil::parseTokenAndGetAuthInfo(null, true);

            if (!$authInfo['success']) {
                return ['allowed' => false, 'message' => '权限验证失败'];
            }

            // 检查是否为管理员
            if ($authInfo['is_admin']) {
                LogService::log("管理员查询用户信息: 管理员ID {$currentUid}, 目标用户ID {$targetUid}");
                return ['allowed' => true, 'message' => '管理员有权限查看所有用户信息'];
            }

            // 普通用户只能查看自己的信息
            if ($currentUid !== $targetUid) {
                LogService::warning("普通用户尝试越权查看他人信息: 用户ID {$currentUid}, 目标用户ID {$targetUid}");
                return ['allowed' => false, 'message' => '只能查看自己的用户信息'];
            }

            return ['allowed' => true, 'message' => '权限验证通过'];

        } catch (\Exception $e) {
            LogService::error($e);
            LogService::log("用户数据权限检查异常: 当前用户 {$currentUid}, 目标用户 {$targetUid}");
            return ['allowed' => false, 'message' => '权限验证失败'];
        }
    }

    /**
     * 添加用户
     */
    function add(): Json
    {
        $params = request()->param();

        // 参数验证
        $validate = Validate::rule([
            'id' => ValidateRule::isRequire(null, '用户唯一标识符必须传递'),
            'username' => ValidateRule::isRequire(null, '用户名必须传递')->max(255, '用户名长度不能超过255个字符')->unique('users', '用户名已存在'),

            'email' => ValidateRule::isRequire(null, '电子邮箱必须传递'),
            'status' => ValidateRule::isRequire(null, '用户状态必须传递'),
            'avatar' => ValidateRule::max(500, '头像URL长度不能超过500个字符'),
            'phone_number' => ValidateRule::max(20, '手机号码长度不能超过20个字符'),
            'gender' => ValidateRule::isRequire(null, '用户性别必须传递'),
            'nickname' => ValidateRule::max(255, '用户昵称长度不能超过255个字符'),
        ]);

        $validateResult = $validate->batch()->check($params);
        if (!$validateResult) {
            return json(['code' => 501, 'msg' => '参数错误', 'info' => $validate->getError()]);
        }

        $roles = $params['roles'] ?? [];
        unset($params['version']);
        unset($params['roles']);
        // 设置默认密码
        $params['password'] = hash('sha256', 'user' . $params['id']);
        // 获取会员数据
        $premiumData = [];
        if (isset($params['premium']) && is_array($params['premium']) && !empty($params['premium'])) {
            $premiumData = $params['premium'];
        }
        unset($params['premium']);

        // 调用服务添加用户
        $result = UserService::addUser($params, $roles, $premiumData);

        return json($result);
    }

    /**
     * 更新用户信息
     */
    function update(): Json
    {
        $params = request()->param();
        unset($params['version']);

        // 参数验证
        $validate = Validate::rule([
            'id' => ValidateRule::isRequire(null, '用户ID必须传递')
        ]);

        if (!$validate->check($params)) {
            return json([
                'code' => 501,
                'msg' => '参数错误',
                'info' => $validate->getError()
            ]);
        }

        $roles = $params['roles'] ?? [];

        // 获取用户ID
        $userId = $params['id'];

        // 处理会员数据
        $premiumData = [];
        $cancelPremium = false;

        // 检查是否要取消会员
        // 这里进行更全面的检查，确保能正确识别各种形式的取消会员请求
        if (array_key_exists('premium', $params)) {
            if (
                $params['premium'] === null ||
                $params['premium'] === 'null' ||
                $params['premium'] === '' ||
                (is_array($params['premium']) && empty($params['premium'])) ||
                (is_array($params['premium']) && isset($params['premium']['cancel']) && $params['premium']['cancel']) ||
                (is_string($params['premium']) && strtolower($params['premium']) === 'cancel')
            ) {

                $cancelPremium = true;
                LogService::log("用户请求取消会员: 用户ID {$userId}, 传递值: " . json_encode($params['premium']));
            } else if (is_array($params['premium']) && !empty($params['premium'])) {
                // 正常的会员数据
                $premiumData = $params['premium'];
            }
        }

        unset($params['premium']);

        // 如果需要取消会员
        if ($cancelPremium) {
            // 查询用户
            $user = userModel::find($userId);
            if (!$user) {
                return json(['code' => 0, 'msg' => '用户不存在']);
            }

            // 获取会员信息
            $premium = $user->premium;

            // 如果用户有会员记录，直接删除
            if ($premium) {
                try {
                    // 记录日志
                    LogService::log("删除用户会员信息 - 用户ID: {$userId}, 会员ID: {$premium->id}");

                    // 强制删除会员记录
                    $deleteResult = $premium->delete(true);

                    if (!$deleteResult) {
                        LogService::error(new \Exception("会员记录删除失败 - 用户ID: {$userId}"));
                    }
                } catch (\Exception $e) {
                    LogService::error($e, "删除会员记录出错");
                }
            }

            // 处理用户基本信息更新
            $updateResult = UserService::updateUser($params, $roles, []);

            return json([
                'code' => 200,
                'msg' => '用户信息更新成功，会员已取消',
                'data' => $updateResult['data'] ?? null
            ]);
        }

        // 常规更新用户
        $result = UserService::updateUser($params, $roles, $premiumData);

        return json($result);
    }

    /**
     * 查询用户列表
     * 支持以下查询条件：
     * - id：精确匹配
     * - username：模糊匹配
     * - phone：模糊匹配
     * - email：模糊匹配
     * - status：用户状态（0=禁用，1=正常）
     * - deleted：删除状态（0=未删除，1=已删除）
     */
    function selectUserListWithRoles()
    {
        // 获取所有请求参数
        $params = request()->param();

        // 初始化查询条件
        $where = [];

        // 处理查询条件
        if (!empty($params['id'])) {
            $where[] = ['id', '=', $params['id']];
        }

        if (!empty($params['username'])) {
            $where[] = ['username', 'like', '%' . $params['username'] . '%'];
        }

        if (!empty($params['phone'])) {
            $where[] = ['phone', 'like', '%' . $params['phone'] . '%'];
        }

        if (!empty($params['email'])) {
            $where[] = ['email', 'like', '%' . $params['email'] . '%'];
        }

        // 用户状态查询（status：0=禁用，1=正常）
        if (isset($params['status'])) {
            $where[] = ['status', '=', intval($params['status'])];
        }

        // 删除状态查询（deleted：0=未删除，1=已删除）
        $queryDeleted = null;
        if (isset($params['deleted'])) {
            if ($params['deleted'] == 1) {
                // 只查询已删除的用户
                $queryDeleted = 'only_deleted';
            } else if ($params['deleted'] == 0) {
                // 只查询未删除的用户
                $queryDeleted = 'not_deleted';
            }
        }

        // 设置分页参数，默认第一页，每页100条
        $page = isset($params['page']) ? intval($params['page']) : 1;
        $pageSize = isset($params['page_size']) ? intval($params['page_size']) : 100;

        // 调用服务获取用户列表
        $result = UserService::getUserList($where, $page, $pageSize, $queryDeleted);

        return json($result);
    }

    /**
     * 删除用户
     * 支持管理员删除任意用户，普通用户只能删除自己
     * 如果参数real=true，则执行物理删除，同时删除关联数据和文件
     * 否则执行软删除
     */
    function delete(): Json
    {
        $params = request()->param();

        // 验证参数
        $validate = Validate::rule([
            'id' => ValidateRule::isRequire(null, '用户ID必须传递')
        ]);

        if (!$validate->check($params)) {
            return json([
                'code' => 501,
                'msg' => '参数错误',
                'info' => $validate->getError()
            ]);
        }

        // 检查是否真实删除（确保布尔值转换正确）
        $realDelete = false;
        if (isset($params['real'])) {
            // 处理各种可能的值
            if ($params['real'] === true || $params['real'] === 'true' || $params['real'] === 1 || $params['real'] === '1') {
                $realDelete = true;
            }
        }

        // 获取用户ID
        $targetUid = $params['id'];
        $currentUid = request()->JWTUid ?? 0;

        // ✅ 数据权限检查
        $permissionCheck = $this->checkUserDataPermission($currentUid, $targetUid);
        if (!$permissionCheck['allowed']) {
            return json(['code' => 403, 'msg' => $permissionCheck['message']]);
        }

        // 记录操作日志
        LogService::log("请求删除用户：用户ID {$targetUid}，删除方式：" . ($realDelete ? '物理删除' : '软删除'));

        // 调用服务删除用户
        $result = UserService::deleteUser($targetUid, $realDelete);

        return json($result);
    }

    /**
     * 恢复删除的用户
     */
    function restore(): Json
    {
        $id = request()->param()['id'];

        // 调用服务恢复用户
        $result = UserService::restoreUser($id);

        return json($result);
    }

    /**
     * 退出登录
     */
    function logout(): Json
    {
        // 调用服务退出登录
        $result = UserService::logout(request()->JWTUid);

        return json($result);
    }

    /**
     * 开通会员
     * @return Json
     */
    function activatePremium(): Json
    {
        $params = request()->param();

        // 基本参数验证
        if (empty($params['user_id'])) {
            return json(['code' => 0, 'msg' => '用户ID不能为空']);
        }

        // 获取用户ID
        $userId = $params['user_id'];

        // 检查用户是否存在
        $user = userModel::find($userId);
        if (!$user) {
            return json(['code' => 0, 'msg' => '用户不存在']);
        }

        // 检查是否要取消会员
        if (
            (isset($params['cancel']) && $params['cancel']) ||
            (isset($params['type']) && $params['type'] === 0) ||
            (isset($params['premium']) && ($params['premium'] === null || $params['premium'] === 'null' || empty($params['premium'])))
        ) {

            // 记录日志
            LogService::log("通过activatePremium方法请求取消会员 - 用户ID: {$userId}");

            // 获取会员信息
            $premium = $user->premium;

            // 如果用户有会员记录，直接删除
            if ($premium) {
                try {
                    // 记录日志
                    LogService::log("删除用户会员信息 - 用户ID: {$userId}, 会员ID: {$premium->id}");

                    // 强制删除会员记录
                    $deleteResult = $premium->delete(true);

                    if ($deleteResult) {
                        return json(['code' => 1, 'msg' => '会员已取消']);
                    } else {
                        return json(['code' => 0, 'msg' => '会员取消失败']);
                    }
                } catch (\Exception $e) {
                    LogService::error($e, "删除会员记录出错");
                    return json(['code' => 0, 'msg' => '会员取消失败：' . $e->getMessage()]);
                }
            } else {
                // 用户本来就没有会员
                return json(['code' => 1, 'msg' => '用户未开通会员，无需取消']);
            }
        }

        // 检查是否有有效的会员数据
        $hasValidData = false;
        if (!empty($params['expiration_time']) || !empty($params['remark']) || isset($params['type'])) {
            $hasValidData = true;
        }

        // 如果没有有效的会员数据，则不操作
        if (!$hasValidData) {
            return json(['code' => 0, 'msg' => '未提供有效的会员数据，无法操作']);
        }

        try {
            // 准备会员数据
            $premiumData = [];

            // 如果传递了create_time，直接使用
            if (!empty($params['create_time'])) {
                $premiumData['create_time'] = $params['create_time'];
            } else {
                $premiumData['create_time'] = date('Y-m-d H:i:s');
            }

            // 设置用户ID
            $premiumData['user_id'] = $userId;

            // 如果传递了过期时间，直接使用
            if (!empty($params['expiration_time'])) {
                $premiumData['expiration_time'] = $params['expiration_time'];
                $typeText = '自定义期限会员';
            } else {
                // 否则根据会员类型计算过期时间
                $type = $params['type'] ?? 1; // 默认为月度会员

                switch ($type) {
                    case 1: // 月度会员
                        $premiumData['expiration_time'] = date('Y-m-d H:i:s', strtotime('+30 days'));
                        $typeText = '月度会员';
                        break;
                    case 2: // 季度会员
                        $premiumData['expiration_time'] = date('Y-m-d H:i:s', strtotime('+90 days'));
                        $typeText = '季度会员';
                        break;
                    case 3: // 年度会员
                        $premiumData['expiration_time'] = date('Y-m-d H:i:s', strtotime('+365 days'));
                        $typeText = '年度会员';
                        break;
                    case 4: // 永久会员
                        $premiumData['expiration_time'] = '2099-01-01 00:00:00'; // 更新为2099年作为永久标记
                        $typeText = '永久会员';
                        break;
                    default:
                        $premiumData['expiration_time'] = date('Y-m-d H:i:s', strtotime('+30 days'));
                        $typeText = '月度会员';
                }
            }

            // 如果传递了备注，直接使用
            if (!empty($params['remark'])) {
                $premiumData['remark'] = $params['remark'];
            } else {
                // 否则使用默认会员类型文本
                $premiumData['remark'] = isset($typeText) ? $typeText : '普通会员';
            }

            // 记录会员操作日志
            LogService::sql("处理会员数据 - 用户ID: {$userId}，会员类型: {$typeText}，到期时间: {$premiumData['expiration_time']}");

            // 确保数据插入成功的Model类
            Db::startTrans();
            try {
                // 检查用户是否已有会员记录
                $premium = $user->premium;

                // 新建或更新会员记录
                if (!$premium) {
                    // 直接创建一个新的会员记录
                    $premium = new \app\api\model\premium();

                    // 生成会员ID
                    if (!empty($params['id'])) {
                        $premiumData['id'] = $params['id'];
                    } else {
                        // 使用NumUtil生成5位数字ID
                        $premiumData['id'] = \utils\NumUtil::generateNumberCode(1, 5);

                        // 确保ID不重复
                        while (\app\api\model\premium::where('id', $premiumData['id'])->find()) {
                            $premiumData['id'] = \utils\NumUtil::generateNumberCode(1, 5);
                        }
                    }

                    // 保存所有数据
                    $result = $premium->save($premiumData);

                    if (!$result) {
                        throw new \Exception('创建会员记录失败');
                    }

                    LogService::log("为用户{$userId}创建新会员记录，ID：{$premiumData['id']}，到期时间：{$premiumData['expiration_time']}");
                } else {
                    // 判断是否永久会员
                    if (strpos($premium->expiration_time, '2099-01-01') !== false) {
                        Db::rollback();
                        return json(['code' => 1, 'msg' => '用户已是永久会员，无需更新', 'data' => $premium]);
                    }

                    // 更新现有会员记录
                    if (!empty($params['id']) && $premium->id != $params['id']) {
                        // 如果提交了新ID且与当前不同，检查ID是否可用
                        if (!\app\api\model\premium::where('id', $params['id'])->find()) {
                            $premium->id = $params['id'];
                        } else {
                            Db::rollback();
                            return json(['code' => 0, 'msg' => '会员ID已存在，无法修改']);
                        }
                    }

                    $premium->expiration_time = $premiumData['expiration_time'];
                    $premium->remark = $premiumData['remark'];
                    $result = $premium->save();

                    if (!$result && !$premium->isExists()) {
                        throw new \Exception('更新会员记录失败');
                    }

                    LogService::log("更新用户{$userId}的会员记录，ID：{$premium->id}，到期时间：{$premiumData['expiration_time']}");
                }

                // 提交事务
                Db::commit();

                // 获取更新后的用户和会员信息
                $updatedUser = userModel::with(['premium'])->find($userId);

                return json([
                    'code' => 1,
                    'msg' => '会员操作成功',
                    'data' => [
                        'user' => $updatedUser,
                        'premium' => $updatedUser->premium
                    ]
                ]);
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                throw $e;
            }
        } catch (\Exception $e) {
            LogService::error($e);
            return json(['code' => 0, 'msg' => '会员操作失败：' . $e->getMessage()]);
        }
    }

    /**
     * 取消会员
     * @return Json
     */
    function cancelPremium(): Json
    {
        // 参数验证
        $validate = Validate::rule([
            'user_id' => ValidateRule::isRequire(null, '用户ID必须传递'),
        ]);

        $params = request()->param();
        $validateResult = $validate->batch()->check($params);
        if (!$validateResult) {
            return json(['code' => 501, 'msg' => '参数错误', 'info' => $validate->getError()]);
        }

        // 获取参数
        $userId = $params['user_id'];

        // 调用服务取消会员
        $result = UserService::cancelPremium($userId);

        return json($result);
    }

    /**
     * 查询会员状态
     * @return Json
     */
    function getPremiumStatus(): Json
    {
        // 获取参数
        $userId = request()->param('user_id');

        // 基本参数验证
        if (empty($userId)) {
            return json(['code' => 0, 'msg' => '用户ID不能为空']);
        }

        try {
            // 查询用户
            $user = userModel::find($userId);
            if (!$user) {
                return json(['code' => 0, 'msg' => '用户不存在']);
            }

            // 获取会员信息
            $premium = $user->premium;

            // 如果用户没有会员记录
            if (!$premium) {
                return json([
                    'code' => 0,
                    'msg' => '未开通会员',
                    'data' => [
                        'is_active' => false,
                        'type' => 0,
                        'type_text' => '普通用户',
                        'create_time' => null,
                        'expiration_time' => null,
                        'remark' => null,
                        'user_id' => $userId
                    ]
                ]);
            }

            // 获取当前时间
            $now = date('Y-m-d H:i:s');

            // 判断会员是否有效
            $isActive = false;
            $daysRemaining = 0;

            // 判断是否永久会员
            if (strpos($premium->expiration_time, '2080-01-01') !== false) {
                $isActive = true;
                $daysRemaining = '永久';
            } else if ($premium->expiration_time > $now) {
                $isActive = true;
                $daysRemaining = ceil((strtotime($premium->expiration_time) - strtotime($now)) / 86400);
            }

            return json([
                'code' => 1,
                'msg' => '获取会员状态成功',
                'data' => [
                    'id' => $premium->id,
                    'user_id' => $premium->user_id,
                    'create_time' => $premium->create_time,
                    'expiration_time' => $premium->expiration_time,
                    'remark' => $premium->remark,
                    'is_active' => $isActive,
                    'days_remaining' => $daysRemaining,
                    'user' => [
                        'id' => $user->id,
                        'username' => $user->username,
                        'email' => $user->email,
                        'avatar' => $user->avatar,
                        'status' => $user->status
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            LogService::error($e);
            return json(['code' => 0, 'msg' => '获取会员状态失败：' . $e->getMessage()]);
        }
    }

    /**
     * 刷新Token接口 - 适配前端tokenManager
     * 接收当前token，返回新的token和过期时间
     */
    function refreshToken(): Json
    {
        $params = request()->param();

        // 参数验证
        $validate = Validate::rule([
            'token' => ValidateRule::isRequire(null, '当前token必须传递')
        ]);

        $validateResult = $validate->batch()->check($params);
        if (!$validateResult) {
            return json(['code' => 501, 'msg' => '参数错误', 'info' => $validate->getError()]);
        }

        $currentToken = $params['token'];

        try {
            // 1. 验证当前token的有效性
            $verifyResult = JWTUtil::verifyToken($currentToken);

            if ($verifyResult['code'] !== 200) {
                return json([
                    'code' => 401,
                    'msg' => 'Token验证失败：' . $verifyResult['msg']
                ]);
            }

            // 2. 获取token中的用户信息
            $tokenData = $verifyResult['data'];
            $userId = $tokenData['data']['id'] ?? null;

            if (!$userId) {
                return json([
                    'code' => 401,
                    'msg' => '无法从token中获取用户信息'
                ]);
            }

            // 3. 检查用户是否存在且状态正常
            $user = userModel::find($userId);
            if (!$user) {
                return json(['code' => 404, 'msg' => '用户不存在']);
            }

            if (!$user['status']) {
                return json(['code' => 403, 'msg' => '用户已被禁用']);
            }

            // 4. 检查Redis中的token是否匹配（防止token被撤销）
            $redisKey = 'lt_' . $userId;
            $redisToken = RedisUtil::getString($redisKey);

            if ($redisToken !== $currentToken) {
                return json([
                    'code' => 401,
                    'msg' => 'Token已失效，请重新登录'
                ]);
            }

            // 5. 生成新的token
            $payloads = [
                'loginTime' => time(),
                'account' => $user['username'],
                'id' => $userId,
                'platform' => 'Web',
                'fingerprint' => $tokenData['data']['fingerprint'] ?? 'Web'
            ];

            // 设置新的过期时间（3天，与登录时保持一致）
            $expireSeconds = 60 * 60 * 24 * 3; // 3天的秒数
            $newExpireTime = time() + $expireSeconds; // 过期时间戳

            // 生成新token
            $newToken = JWTUtil::generateToken($payloads, $newExpireTime);

            // 6. 更新Redis中的token（第三个参数是秒数，不是时间戳）
            RedisUtil::setString($redisKey, $newToken, $expireSeconds);

            // 记录日志
            LogService::log("用户Token续签成功：{$user['username']}({$userId})");

            // 7. 返回新token（格式与前端期望匹配）
            return json([
                'code' => 200,
                'msg' => '续签成功',
                'token' => $newToken,
                'expireTime' => $newExpireTime
            ]);
        } catch (\Exception $e) {
            LogService::error($e, "Token续签异常");
            return json([
                'code' => 500,
                'msg' => 'Token续签异常' . $e->getMessage()
            ]);
        }
    }

    /**
     * 用户注册
     */
    function register(): Json
    {
        $params = request()->param();

        // 参数验证
        $validate = Validate::rule([
            'username' => ValidateRule::isRequire(null, '用户名必须传递')->max(255, '用户名长度不能超过255个字符')->unique('users', '用户名已存在'),
            'password' => ValidateRule::isRequire(null, '密码必须传递')->min(6, '密码长度不能少于6个字符'),
            'email' => ValidateRule::isRequire(null, '邮箱必须传递')->email('邮箱格式不正确')->unique('users', '邮箱已被使用'),
        ]);

        $validateResult = $validate->batch()->check($params);
        if (!$validateResult) {
            return json(['code' => 501, 'msg' => '参数错误', 'info' => $validate->getError()]);
        }

        // 处理注册数据（密码会由模型自动加密）
        $userData = [
            'username' => $params['username'],
            'password' => $params['password'],  // 模型会自动加密
            'email' => $params['email'],
            'nickname' => $params['nickname'] ?? $params['username'],
            'status' => 1, // 默认启用
            'gender' => $params['gender'] ?? 2, // 默认未知
        ];

        // 调用服务进行注册
        $result = UserService::register($userData);

        return json($result);
    }

    /**
     * 修改密码
     */
    function changePassword(): Json
    {
        $params = request()->param();

        // 参数验证
        $validate = Validate::rule([
            'old_password' => ValidateRule::isRequire(null, '旧密码必须传递'),
            'new_password' => ValidateRule::isRequire(null, '新密码必须传递')->min(6, '密码长度不能少于6个字符'),
        ]);

        $validateResult = $validate->batch()->check($params);
        if (!$validateResult) {
            return json(['code' => 501, 'msg' => '参数错误', 'info' => $validate->getError()]);
        }

        // 从 JWT 中获取用户ID（通过中间件注入）
        $userId = request()->JWTUid ?? null;

        if (!$userId) {
            return json(['code' => 401, 'msg' => '未授权，请重新登录']);
        }

        // 调用服务修改密码
        $result = UserService::changePassword($userId, $params['old_password'], $params['new_password']);

        return json($result);
    }

    /**
     * 请求密码重置（发送验证码）
     */
    function requestPasswordReset(): Json
    {
        $params = request()->param();

        // 参数验证（使用正则表达式验证邮箱）
        $validate = Validate::rule([
            'email' => ValidateRule::isRequire(null, '邮箱必须传递')->regex('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', '邮箱格式不正确'),
        ]);

        $validateResult = $validate->batch()->check($params);
        if (!$validateResult) {
            return json(['code' => 501, 'msg' => '参数错误', 'info' => $validate->getError()]);
        }

        // 调用服务请求密码重置
        $result = UserService::requestPasswordReset($params['email']);

        return json($result);
    }

    /**
     * 校验重置Token
     */
    function verifyResetToken(): Json
    {
        $params = request()->param();

        // 参数验证
        $validate = Validate::rule([
            'token' => ValidateRule::isRequire(null, 'Token必须传递'),
        ]);

        $validateResult = $validate->batch()->check($params);
        if (!$validateResult) {
            return json(['code' => 501, 'msg' => '参数错误', 'info' => $validate->getError()]);
        }

        // 调用服务验证token
        $result = UserService::verifyResetToken($params['token']);

        return json($result);
    }

    /**
     * 重置密码
     */
    function resetPassword(): Json
    {
        $params = request()->param();

        // 参数验证
        $validate = Validate::rule([
            'token' => ValidateRule::isRequire(null, '令牌必须传递'),
            'new_password' => ValidateRule::isRequire(null, '新密码必须传递')->min(6, '密码长度不能少于6位'),
        ]);

        $validateResult = $validate->batch()->check($params);
        if (!$validateResult) {
            return json(['code' => 501, 'msg' => '参数错误', 'info' => $validate->getError()]);
        }

        // 调用服务重置密码
        $result = UserService::resetPassword($params['token'], $params['new_password']);

        return json($result);
    }
}
