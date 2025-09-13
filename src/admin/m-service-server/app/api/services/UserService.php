<?php
namespace app\api\services;

use app\api\model\userRoles;
use app\api\model\users;
use app\api\model\premium;
use app\api\model\roles;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Db;
use utils\JWTUtil;
use utils\RedisUtil;
use utils\SecretUtil;

/**
 * 用户服务类
 */
class UserService
{
    /**
     * 用户登录
     * @param string $account 账号（可以是用户ID、邮箱或手机号）
     * @param string $password 密码
     * @param string $action 登录方式
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function login(string $account, string $password, string $action = 'pwd', string $fingerprint = 'Web'): array
    {

        $hidden = ['phone', 'email', 'password', 'ip_address', 'delete_time', 'create_time', 'update_time'];
        // 密码登录
        if ($action == 'pwd') {
            // 查询用户信息带角色和权限
            $res = users::with([
                'roles.permissions' => function ($query) {
                }
            ])->where('id|email|phone', $account)->where('password', hash('sha256', $password))->hidden($hidden)->find();

            if (!$res) {
                return ['code' => 500, 'msg' => '账号或密码错误', 'data' => null];
            } else if (!$res['status']) {
                return ['code' => 502, 'msg' => '用户被封禁中', 'data' => null];
            }
            // JWT载荷                
            $payloads['loginTime'] = time();
            $payloads['account'] = $account;
            $payloads['id'] = $res['id'];
            $payloads['platform'] = 'Web';
            $payloads['fingerprint'] = $fingerprint;
            $expireTime = time() + 60 * 11;
            // JWT生成
            $token = JWTUtil::generateToken($payloads, $expireTime);
            // 保存到redis
            RedisUtil::setString('lt_' . $res['id'], $token, $expireTime);

            // 记录日志
            LogService::log("用户登录成功：{$account}({$res['id']})");

            // 计算过期时间戳（当前时间 + 24小时）

            return [
                'code' => 200,
                'msg' => '密码登录成功',
                'token' => $token,
                'data' => $res,
                // 过期时间戳
                'expireTime' => $expireTime
            ];
        } else {
            return ['code' => 500, 'msg' => '其他方式登录暂不支持'];
        }
    }

    /**
     * 查询用户信息（包含角色和会员信息）
     * @param int $userId 用户ID
     * @return array
     */
    public static function getUserInfo(int $userId): array
    {
        try {
            $user = users::with([
                'roles' => function ($query) {
                    $query->field(['description', 'name', 'iden']);
                },
                'premium' => function ($query) {
                    $query->field(['id', 'user_id', 'expiration_time', 'remark']);
                }
            ])->hidden(['password', 'ip_address', 'delete_time'])->find($userId);

            if (!$user) {
                return ['code' => 404, 'msg' => '用户不存在', 'data' => null];
            }
            // 过滤手机号
            if (!empty($user->phone) && strlen($user->phone) >= 7) {
                $user->phone = substr($user->phone, 0, 3) . '*****' . substr($user->phone, -3);
            }
            return ['code' => 200, 'msg' => '用户信息查询成功', 'data' => $user];
        } catch (\Exception $e) {
            LogService::error($e);
            return ['code' => 500, 'msg' => '查询失败：' . $e->getMessage()];
        }
    }

    /**
     * 注册用户
     * @param array $userData 用户数据
     * @return array|bool
     */
    public static function register(array $userData)
    {
        try {
            // 检查用户名是否已存在
            $exists = users::where('username', $userData['username'])->find();
            if ($exists) {
                return ['code' => 0, 'msg' => '用户名已存在'];
            }

            // 检查邮箱是否已存在
            if (isset($userData['email']) && !empty($userData['email'])) {
                $existEmail = users::where('email', $userData['email'])->find();
                if ($existEmail) {
                    return ['code' => 0, 'msg' => '邮箱已被使用'];
                }
            }

            // 创建用户
            $user = new users();
            $user->save($userData);

            // 记录日志
            LogService::log("新用户注册成功：{$user->username}({$user->id})", $userData);

            return ['code' => 1, 'msg' => '注册成功', 'data' => $user];
        } catch (\Exception $e) {
            LogService::error($e);
            return ['code' => 0, 'msg' => '注册失败：' . $e->getMessage()];
        }
    }

    /**
     * 添加用户（后台管理）
     * @param array $userData 用户数据
     * @param array $roles 角色ID数组
     * @param array $premiumData 会员数据，包含expiration_time和remark字段
     * @return array
     */
    public static function addUser(array $userData, array $roles = [], array $premiumData = []): array
    {
        $users = new users();

        // 检查id是否存在
        $exist = $users->find($userData['id']);
        if ($exist) {
            return ['code' => 503, 'msg' => '此id已存在', 'data' => null];
        }

        $userId = $userData['id'];

        // 使用事务确保数据一致性
        Db::startTrans();
        try {
            // 保存用户基本信息
            $save = $users->save($userData);
            if (!$save) {
                throw new \Exception('用户保存失败');
            }

            // 处理角色数据
            $batchData = [];

            // 如果roles不存在或为空数组，设置默认角色ID为6
            if (empty($roles) || !is_array($roles)) {
                $batchData[] = [
                    'user_id' => $userId,
                    'role_id' => 6  // 默认角色ID
                ];
            } else {
                // 处理传入的角色数组
                foreach ($roles as $roleId) {
                    if (is_numeric($roleId) && $roleId > 0) {
                        $batchData[] = [
                            'user_id' => $userId,
                            'role_id' => (int) $roleId
                        ];
                    }
                }
            }

            // 批量插入角色数据
            if (!empty($batchData)) {
                userRoles::insertAll($batchData);  // 使用insertAll替代create进行批量插入
            }

            // 处理会员信息
            if (!empty($premiumData)) {
                // 如果提供了会员数据，创建会员记录
                // 确保会员数据包含必要字段
                if (!isset($premiumData['create_time'])) {
                    $premiumData['create_time'] = date('Y-m-d H:i:s');
                }

                if (!isset($premiumData['user_id'])) {
                    $premiumData['user_id'] = $userId;
                }

                // 生成会员ID，如果没有提供的话
                if (!isset($premiumData['id'])) {
                    // 使用NumUtil生成5位数字ID
                    $premiumData['id'] = \utils\NumUtil::generateNumberCode(1, 5);

                    // 确保ID不重复
                    while (\app\api\model\premium::where('id', $premiumData['id'])->find()) {
                        $premiumData['id'] = \utils\NumUtil::generateNumberCode(1, 5);
                    }
                }

                // 设置默认过期时间，如果没有提供的话
                if (!isset($premiumData['expiration_time'])) {
                    $premiumData['expiration_time'] = date('Y-m-d H:i:s', strtotime('+30 days'));
                }

                // 设置默认备注，如果没有提供的话
                if (!isset($premiumData['remark'])) {
                    $premiumData['remark'] = '普通会员';
                }

                // 创建会员记录
                $premium = new \app\api\model\premium();
                $premium->save($premiumData);

                LogService::log("为新用户添加会员信息：用户ID {$userId}，会员ID {$premiumData['id']}，到期时间 {$premiumData['expiration_time']}");
            }

            Db::commit();

            // 记录日志
            LogService::log("添加用户成功：{$userData['username']}({$userId})");

            // 重新查询用户信息（包含角色和会员）
            $user = users::with(['roles', 'premium'])->find($userId);

            return [
                'code' => 200,
                'msg' => '新增成功',
                'data' => [
                    'user' => $user,
                    'assigned_roles' => array_column($batchData, 'role_id') // 返回分配的角色ID数组
                ]
            ];

        } catch (\Exception $e) {
            Db::rollback();
            LogService::error($e);
            return [
                'code' => 500,
                'msg' => '操作失败: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 更新用户信息
     * @param array $userData 用户数据
     * @param array $roles 角色ID数组
     * @param array $premiumData 会员数据，包含expiration_time和remark字段
     * @return array
     */
    public static function updateUser(array $userData, array $roles = [], array $premiumData = []): array
    {
        $subMsg = 'null';

        // 判断是否只更新状态
        if (isset($userData['updateStatusOnly'])) {
            unset($userData['roles']);
            unset($userData['updateStatusOnly']);
            $res = users::where('id', $userData['id'])->update($userData);

            return ['code' => $res ? 200 : 500, 'msg' => '状态更新' . ($res ? '成功' : '失败')];
        }

        $uid = $userData['id'];

        // 检查用户是否存在
        $user = users::with(['roles'])->where('id', $uid)->find();
        if (!$user) {
            return ['code' => 504, 'msg' => '操作失败', 'info' => '用户不存在'];
        }

        $userData['update_time'] = date('Y-m-d H:i:s', time());

        // 使用事务确保数据一致性
        Db::startTrans();
        try {
            // 拿到旧的角色ids
            $oldUserRolesIds = $user->roles()->column('id');

            // 重新排序比对
            sort($oldUserRolesIds);
            sort($roles);

            // 比对两个角色组的信息是否一致
            if ($oldUserRolesIds != $roles) {
                // 修改了角色信息
                // 直接删除绑定的角色
                $isDeletes = self::deleteRolesByUid($uid);
                $batchInsertData = [];
                foreach ($roles as $roleId) {
                    $batchInsertData[] = [
                        'user_id' => $uid, // 用户ID    
                        'role_id' => $roleId // 角色ID
                    ];
                }
                $insertRoleBatch = self::insertRoleBatch($batchInsertData);
                if ($insertRoleBatch > 0 && $isDeletes) {
                    $subMsg = '角色分配成功';
                }
            }

            // 修改用户信息
            unset($userData['roles']);

            $newUserInfo = users::where('id', $userData['id'])->update($userData);

            // 处理会员信息
            if (!empty($premiumData)) {
                // 获取现有会员信息
                $premium = $user->premium;

                // 如果用户已有会员信息，则更新；否则创建新的会员记录
                if ($premium) {
                    // 判断是否永久会员
                    if (!isset($premium->expiration_time) || strpos($premium->expiration_time, '2099-01-01') === false) {
                        // 更新会员信息
                        if (isset($premiumData['expiration_time'])) {
                            $premium->expiration_time = $premiumData['expiration_time'];
                        }

                        if (isset($premiumData['remark'])) {
                            $premium->remark = $premiumData['remark'];
                        }

                        // 如果提供了ID且与现有ID不同，检查新ID是否可用
                        if (isset($premiumData['id']) && $premium->id != $premiumData['id']) {
                            if (!premium::where('id', $premiumData['id'])->find()) {
                                $premium->id = $premiumData['id'];
                            } else {
                                throw new \Exception('会员ID已存在，无法修改');
                            }
                        }

                        $premium->save();
                        LogService::log("更新用户会员信息：用户ID {$uid}，会员ID {$premium->id}，到期时间 {$premium->expiration_time}");
                    }
                } else {
                    // 创建新会员记录
                    // 确保会员数据包含必要字段
                    if (!isset($premiumData['create_time'])) {
                        $premiumData['create_time'] = date('Y-m-d H:i:s');
                    }

                    if (!isset($premiumData['user_id'])) {
                        $premiumData['user_id'] = $uid;
                    }

                    // 生成会员ID，如果没有提供的话
                    if (!isset($premiumData['id'])) {
                        // 使用NumUtil生成5位数字ID
                        $premiumData['id'] = \utils\NumUtil::generateNumberCode(1, 5);

                        // 确保ID不重复
                        while (premium::where('id', $premiumData['id'])->find()) {
                            $premiumData['id'] = \utils\NumUtil::generateNumberCode(1, 5);
                        }
                    }

                    // 设置默认过期时间，如果没有提供的话
                    if (!isset($premiumData['expiration_time'])) {
                        $premiumData['expiration_time'] = date('Y-m-d H:i:s', strtotime('+30 days'));
                    }

                    // 设置默认备注，如果没有提供的话
                    if (!isset($premiumData['remark'])) {
                        $premiumData['remark'] = '普通会员';
                    }

                    // 创建会员记录
                    $premium = new premium();
                    $premium->save($premiumData);

                    LogService::log("为用户添加会员信息：用户ID {$uid}，会员ID {$premiumData['id']}，到期时间 {$premiumData['expiration_time']}");
                }

                $subMsg .= '，会员信息更新成功';
            }

            Db::commit();

            // 重新查询用户信息（包含角色和会员）
            $updatedUser = users::with(['roles', 'premium'])->find($uid);

            if ($newUserInfo) {
                return [
                    'code' => 200,
                    'msg' => '信息更新成功',
                    'data' => $updatedUser,
                    'subMsg' => $subMsg
                ];
            } else {
                return [
                    'code' => 502,
                    'msg' => '数据库操作失败',
                    'subMsg' => $subMsg
                ];
            }

        } catch (\Exception $e) {
            Db::rollback();
            LogService::error($e);
            return [
                'code' => 500,
                'msg' => '操作失败: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 删除用户角色关联
     * @param int $userId 用户ID
     * @return bool
     */
    public static function deleteRolesByUid(int $userId): bool
    {
        try {
            userRoles::where('user_id', $userId)->delete();
            return true;
        } catch (\Exception $e) {
            LogService::error($e);
            return false;
        }
    }

    /**
     * 批量插入用户角色关联
     * @param array $data 角色数据
     * @return int
     */
    public static function insertRoleBatch(array $data): int
    {
        try {
            return userRoles::insertAll($data);
        } catch (\Exception $e) {
            LogService::error($e);
            return 0;
        }
    }

    /**
     * 获取用户列表，支持条件查询和分页
     * @param array $where 查询条件
     * @param int $page 当前页码
     * @param int $pageSize 每页记录数
     * @param string $queryDeleted 删除状态：only_deleted-只查询已删除，not_deleted-只查询未删除，null-查询所有
     * @return array
     */
    public static function getUserList(array $where = [], int $page = 1, int $pageSize = 100, string $queryDeleted = null): array
    {
        try {
            $query = users::with([
                'roles' => function ($query) {
                },
                'premium' => function ($query) {
                }
            ])->order('update_time', 'desc');

            // 应用查询条件
            if (!empty($where)) {
                $query->where($where);
            }

            // 根据删除状态筛选
            if ($queryDeleted === 'only_deleted') {
                // 只查询已删除的用户
                $query->onlyTrashed();
            } else if ($queryDeleted === 'not_deleted') {
                // 只查询未删除的用户
                // 不使用withTrashed，这样默认就是未删除的用户
            } else {
                // 查询所有用户（包括已删除）
                $query->withTrashed();
            }

            // 分页查询
            $total = $query->count(); // 总记录数
            $list = $query->page($page, $pageSize)->select();

            // 返回分页数据
            return [
                'code' => 200,
                'data' => [
                    'list' => $list,
                    'pagination' => [
                        'total' => $total,
                        'current' => $page,
                        'page_size' => $pageSize,
                        'pages' => ceil($total / $pageSize)
                    ]
                ]
            ];
        } catch (\Exception $e) {
            LogService::error($e);
            return ['code' => 500, 'msg' => '查询失败：' . $e->getMessage()];
        }
    }

    /**
     * 删除用户
     * @param int $userId 用户ID
     * @param bool $realDelete 是否真实删除（物理删除）
     * @return array
     */
    public static function deleteUser(int $userId, bool $realDelete = false): array
    {
        try {
            // 查询用户
            $user = users::withTrashed()->find($userId);

            if (!$user) {
                return ['code' => 404, 'msg' => '用户不存在'];
            }

            // 如果是真实删除，先处理相关数据
            if ($realDelete) {
                // 记录操作
                LogService::log("开始物理删除用户 - 用户ID: {$userId}, 用户名: {$user->username}");

                Db::startTrans();
                try {
                    // 1. 删除用户角色关联
                    userRoles::where('user_id', $userId)->delete(true);

                    // 2. 删除用户会员信息
                    if ($user->premium) {
                        $user->premium->delete(true);
                    }

                    // 3. 删除用户头像文件
                    if (!empty($user->avatar) && class_exists('\\utils\\FileUtil')) {
                        \utils\FileUtil::deleteFile($user->avatar);
                    }

                    // 4. 执行物理删除用户 - 直接使用模型操作
                    Db::name('users')->where('id', $userId)->delete(true);

                    Db::commit();

                    LogService::log("用户物理删除成功 - 用户ID: {$userId}");
                    return ['code' => 200, 'msg' => '用户已永久删除'];
                } catch (\Exception $e) {
                    Db::rollback();
                    LogService::error($e);
                    return ['code' => 500, 'msg' => '删除失败：' . $e->getMessage()];
                }
            } else {
                // 软删除 - 使用ThinkPHP的软删除机制
                $result = users::destroy($userId);

                // 记录操作
                $logMsg = $result ? "成功" : "失败";
                LogService::log("用户软删除{$logMsg} - 用户ID: {$userId}");

                return ['code' => $result ? 200 : 500, 'msg' => '数据删除' . ($result ? '成功' : '失败')];
            }
        } catch (\Exception $e) {
            LogService::error($e);
            return ['code' => 500, 'msg' => '删除失败：' . $e->getMessage()];
        }
    }

    /**
     * 恢复软删除的用户
     * @param int $userId 用户ID
     * @return array
     */
    public static function restoreUser(int $userId): array
    {
        try {
            $user = users::onlyTrashed()->find($userId);
            if (!$user) {
                return ['code' => 404, 'msg' => '用户不存在或未被删除'];
            }

            // 记录恢复用户的详细信息
            $username = $user->username;
            $email = $user->email;
            $deleteTime = $user->delete_time;

            // 恢复用户
            $result = $user->restore();

            if (!$result) {
                throw new \Exception('恢复用户记录失败');
            }

            LogService::log("恢复用户：用户ID {$userId}, 用户名 {$username}, 邮箱 {$email}, 删除时间 {$deleteTime}");

            return ['code' => 200, 'msg' => '用户恢复成功', 'data' => $user];
        } catch (\Exception $e) {
            LogService::error($e, "恢复用户失败：用户ID {$userId}");
            return ['code' => 500, 'msg' => '恢复失败：' . $e->getMessage()];
        }
    }

    /**
     * 退出登录
     * @param int $userId 用户ID
     * @return array
     */
    public static function logout(int $userId): array
    {
        try {
            $isDelete = RedisUtil::deleteString('lt_' . $userId);

            if ($isDelete) {
                LogService::log("用户退出登录：用户ID {$userId}");
            }

            return ['code' => $isDelete ? 200 : 500, 'msg' => '注销' . ($isDelete ? '成功' : '失败')];
        } catch (\Exception $e) {
            LogService::error($e);
            return ['code' => 500, 'msg' => '退出失败：' . $e->getMessage()];
        }
    }

    /**
     * 开通或续费会员
     * @param int $userId 用户ID
     * @param int $type 会员类型：1月度，2季度，3年度，4永久
     * @param string $remark 备注信息
     * @return array
     */
    public static function activatePremium(int $userId, int $type, string $remark = ''): array
    {
        try {
            // 查询用户
            $user = users::find($userId);
            if (!$user) {
                return ['code' => 0, 'msg' => '用户不存在'];
            }

            // 计算会员时间
            $now = date('Y-m-d H:i:s');
            $expiration = '';
            $typeText = '';

            switch ($type) {
                case 1: // 月度会员
                    $expiration = date('Y-m-d H:i:s', strtotime('+30 days'));
                    $typeText = '月度会员';
                    break;
                case 2: // 季度会员
                    $expiration = date('Y-m-d H:i:s', strtotime('+90 days'));
                    $typeText = '季度会员';
                    break;
                case 3: // 年度会员
                    $expiration = date('Y-m-d H:i:s', strtotime('+365 days'));
                    $typeText = '年度会员';
                    break;
                case 4: // 永久会员
                    $expiration = '2080-01-01 00:00:00'; // 表示永久
                    $typeText = '永久会员';
                    break;
                default:
                    return ['code' => 0, 'msg' => '无效的会员类型'];
            }

            // 如果没有提供备注，使用默认的会员类型文本
            if (empty($remark)) {
                $remark = $typeText;
            }

            // 准备会员数据
            $premiumData = [
                'create_time' => $now,  // 确保提供创建时间
                'expiration_time' => $expiration,
                'remark' => $remark
            ];

            // 获取现有会员记录
            $premium = $user->premium;

            // 如果已有会员且未过期，并且不是永久会员，则延长时间
            if ($premium && $premium->is_active && $type != 4) {
                // 如果当前用户已经是永久会员，则无需更新
                if (strpos($premium->expiration_time, '2080-01-01') !== false) {
                    return ['code' => 1, 'msg' => '用户已是永久会员，无需更新', 'data' => $premium];
                }

                // 如果当前会员未过期，则在现有期限上增加时间
                if ($premium->expiration_time > $now) {
                    switch ($type) {
                        case 1: // 月度会员
                            $premiumData['expiration_time'] = date('Y-m-d H:i:s', strtotime($premium->expiration_time . ' +30 days'));
                            break;
                        case 2: // 季度会员
                            $premiumData['expiration_time'] = date('Y-m-d H:i:s', strtotime($premium->expiration_time . ' +90 days'));
                            break;
                        case 3: // 年度会员
                            $premiumData['expiration_time'] = date('Y-m-d H:i:s', strtotime($premium->expiration_time . ' +365 days'));
                            break;
                    }
                }
            }

            // 创建或更新会员信息
            $result = users::createOrUpdatePremium($userId, $premiumData);

            if ($result) {
                // 记录日志
                LogService::log("用户开通会员成功：用户ID {$userId}，类型 {$type}，到期时间 {$premiumData['expiration_time']}");

                // 返回最新的会员信息
                return ['code' => 1, 'msg' => '会员开通成功', 'data' => $result];
            } else {
                return ['code' => 0, 'msg' => '会员开通失败'];
            }
        } catch (\Exception $e) {
            LogService::error($e);
            return ['code' => 0, 'msg' => '操作失败：' . $e->getMessage()];
        }
    }

    /**
     * 取消会员
     * @param int $userId 用户ID
     * @return array
     */
    public static function cancelPremium(int $userId): array
    {
        try {
            // 查询用户
            $user = users::find($userId);
            if (!$user) {
                LogService::log("取消会员失败：用户不存在 - 用户ID {$userId}", [], 'warning');
                return ['code' => 0, 'msg' => '用户不存在'];
            }

            // 获取会员信息
            $premium = $user->premium;

            // 如果用户本来就没有会员，直接返回成功
            if (!$premium) {
                LogService::log("用户无需取消会员：用户未开通会员 - 用户ID {$userId}");
                return ['code' => 1, 'msg' => '用户未开通会员，无需取消'];
            }

            // 生成SQL日志
            LogService::sql("准备取消会员 - 用户ID: {$userId}, 会员ID: " . $premium->id);

            // 使用事务确保数据一致性
            Db::startTrans();
            try {
                // 直接记录会员信息，避免后面对象被删除后无法访问
                $logInfo = [
                    'user_id' => $userId,
                    'premium_id' => $premium->id,
                    'expiration_time' => $premium->expiration_time
                ];

                // 直接删除会员记录
                $deleteResult = $premium->delete(true);

                if (!$deleteResult) {
                    throw new \Exception('删除会员记录失败');
                }

                Db::commit();

                // 记录日志
                LogService::log("用户取消会员成功 - 用户ID: {$logInfo['user_id']}, 会员ID: {$logInfo['premium_id']}, 删除前到期时间: {$logInfo['expiration_time']}");

                return [
                    'code' => 1,
                    'msg' => '会员已取消',
                    'data' => $logInfo
                ];
            } catch (\Exception $e) {
                Db::rollback();
                throw $e;
            }
        } catch (\Exception $e) {
            LogService::error($e, "取消会员出错 - 用户ID: {$userId}");
            return ['code' => 0, 'msg' => '会员取消失败: ' . $e->getMessage()];
        }
    }

    /**
     * 检查用户会员状态
     * @param int $userId 用户ID
     * @return array
     */
    public static function checkPremiumStatus(int $userId): array
    {
        try {
            // 查询用户
            $user = users::find($userId);
            if (!$user) {
                return ['code' => 0, 'msg' => '用户不存在'];
            }

            // 获取会员信息
            $premium = $user->premium;

            if (!$premium) {
                return [
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
                ];
            }

            // 判断会员是否有效
            $now = date('Y-m-d H:i:s');
            $isActive = false;

            // 判断是否永久会员
            if (strpos($premium->expiration_time, '2080-01-01') !== false) {
                $isActive = true;
            } else if ($premium->expiration_time > $now) {
                $isActive = true;
            }

            // 返回会员信息
            return [
                'code' => 1,
                'msg' => '获取成功',
                'data' => [
                    'id' => $premium->id,
                    'user_id' => $premium->user_id,
                    'is_active' => $isActive,
                    'type' => $premium->type,
                    'type_text' => $premium->remark,
                    'create_time' => $premium->create_time,
                    'expiration_time' => $premium->expiration_time,
                    'remark' => $premium->remark,
                    // 增加剩余天数信息
                    'days_remaining' => $isActive ?
                        (strpos($premium->expiration_time, '2080-01-01') !== false ?
                            '永久' :
                            ceil((strtotime($premium->expiration_time) - strtotime($now)) / 86400)
                        ) :
                        0
                ]
            ];
        } catch (\Exception $e) {
            LogService::error($e);
            return ['code' => 0, 'msg' => '操作失败：' . $e->getMessage()];
        }
    }
}