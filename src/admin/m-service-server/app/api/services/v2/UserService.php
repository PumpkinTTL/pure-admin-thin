<?php

namespace app\api\services\v2;

use app\api\model\users;
use app\api\model\premium;
use app\api\model\userRoles;
use app\api\model\LevelRecord;
use app\api\model\ExperienceLog;
use app\api\model\LoginLog;
use app\api\model\comments;
use app\api\model\likes;
use app\api\model\favorites;
use app\api\services\LogService;
use think\facade\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 客户端用户服务类
 * 专注于客户端用户的基础操作，不涉及角色、权限等管理功能
 */
class UserService
{
  /**
   * 获取用户信息
   * @param int $userId 用户ID
   * @return array
   * @throws DataNotFoundException
   * @throws DbException
   * @throws ModelNotFoundException
   */
  public static function getUserInfo(int $userId): array
  {
    // 查询用户信息，包含会员信息
    $user = users::with(['premium'])->where('id', $userId)->find();

    if (!$user) {
      return ['code' => 404, 'msg' => '用户不存在'];
    }

    // 检查用户状态
    if ($user->status == 0) {
      return ['code' => 403, 'msg' => '账号已被禁用'];
    }

    // 脱敏处理手机号
    $phone = $user->phone ?? '';
    if ($phone && strlen($phone) == 11) {
      $phone = substr($phone, 0, 3) . '****' . substr($phone, 7);
    }

    // 构建返回数据
    $userData = [
      'id' => $user->id,
      'username' => $user->username ?? '',
      'nickname' => $user->nickname ?? '',
      'email' => $user->email ?? '',
      'avatar' => $user->avatar ?? '',
      'gender' => $user->gender ?? 0,
      'signature' => $user->signature ?? '',
      'phone' => $phone,
      'status' => $user->status ?? 1,
      'last_login' => $user->last_login ?? '',
      'register_source' => $user->register_source ?? '',
      'premium' => null
    ];

    // 处理会员信息
    if ($user->premium) {
      $userData['premium'] = [
        'id' => $user->premium->id,
        'expiration_time' => $user->premium->expiration_time,
        'is_active' => strtotime($user->premium->expiration_time) > time(),
        'remark' => $user->premium->remark ?? ''
      ];
    }

    return ['code' => 200, 'msg' => '获取成功', 'data' => $userData];
  }

  /**
   * 更新用户资料
   * 只允许更新安全字段：nickname, gender, signature, avatar
   * 不会影响角色、权限、会员等其他信息
   * 
   * @param int $userId 用户ID
   * @param array $data 更新数据
   * @return array
   */
  public static function updateUserProfile(int $userId, array $data): array
  {
    // 检查用户是否存在
    $user = users::where('id', $userId)->find();
    if (!$user) {
      return ['code' => 404, 'msg' => '用户不存在'];
    }

    // 准备更新数据
    $updateData = ['update_time' => date('Y-m-d H:i:s')];

    // 只允许更新的安全字段
    $allowedFields = ['nickname', 'gender', 'signature', 'avatar'];

    foreach ($allowedFields as $field) {
      if (isset($data[$field])) {
        $updateData[$field] = $data[$field];
      }
    }

    // 如果没有要更新的字段
    if (count($updateData) <= 1) {
      return ['code' => 400, 'msg' => '没有可更新的字段'];
    }

    // 执行更新 - 只更新用户基本信息，不涉及关联表
    $result = users::where('id', $userId)->update($updateData);

    if ($result === false) {
      return ['code' => 500, 'msg' => '更新失败'];
    }

    // 获取更新后的用户信息
    $updatedUser = users::where('id', $userId)->find();

    // 获取更新后的完整用户信息（脱敏处理）
    $updatedUserInfo = self::getUserInfo($userId);

    return [
      'code' => 200,
      'msg' => '更新成功',
      'data' => $updatedUserInfo['code'] === 200 ? $updatedUserInfo['data'] : [
        'id' => $updatedUser->id,
        'nickname' => $updatedUser->nickname ?? '',
        'gender' => $updatedUser->gender ?? 0,
        'signature' => $updatedUser->signature ?? '',
        'avatar' => $updatedUser->avatar ?? '',
        'update_time' => $updatedUser->update_time
      ]
    ];
  }

  /**
   * 检查会员状态
   * @param int $userId 用户ID
   * @return array
   */
  public static function checkPremiumStatus(int $userId): array
  {
    try {
      $user = users::with(['premium'])->where('id', $userId)->find();

      if (!$user) {
        return ['code' => 404, 'msg' => '用户不存在'];
      }

      // 如果没有会员信息
      if (!$user->premium) {
        return [
          'code' => 200,
          'msg' => '非会员用户',
          'data' => [
            'is_premium' => false,
            'expiration_time' => null,
            'is_active' => false
          ]
        ];
      }

      // 检查会员是否有效
      $expirationTime = $user->premium->expiration_time;
      $isActive = strtotime($expirationTime) > time();

      return [
        'code' => 200,
        'msg' => '获取成功',
        'data' => [
          'is_premium' => true,
          'premium_id' => $user->premium->id,
          'expiration_time' => $expirationTime,
          'is_active' => $isActive,
          'days_remaining' => $isActive ? ceil((strtotime($expirationTime) - time()) / 86400) : 0,
          'remark' => $user->premium->remark ?? ''
        ]
      ];
    } catch (\Exception $e) {
      LogService::error($e);
      return ['code' => 500, 'msg' => '获取会员状态失败：' . $e->getMessage()];
    }
  }

  /**
   * 软删除用户账号及所有关联数据
   * @param int $userId 用户ID
   * @param string $password 用户密码（用于验证）
   * @return array
   */
  public static function deleteAccount(int $userId, string $password): array
  {
    // 使用数据库事务确保数据一致性
    Db::startTrans();

    try {
      // 查询用户
      $user = users::where('id', $userId)->find();

      if (!$user) {
        Db::rollback();
        return ['code' => 404, 'msg' => '用户不存在'];
      }

      // 验证密码
      $hashedPassword = hash('sha256', $password);
      if ($user->password !== $hashedPassword) {
        Db::rollback();
        return ['code' => 401, 'msg' => '密码错误'];
      }

      $currentTime = date('Y-m-d H:i:s');
      $deletedCount = 0;

      // 1. 软删除用户主表
      $userResult = users::where('id', $userId)->update([
        'delete_time' => $currentTime,
        'status' => 0, // 同时禁用账号
        'update_time' => $currentTime
      ]);
      if ($userResult) $deletedCount++;

      // 2. 软删除用户角色关联（如果表支持软删除）
      try {
        $roleResult = userRoles::where('user_id', $userId)->update([
          'delete_time' => $currentTime
        ]);
        if ($roleResult) $deletedCount++;
      } catch (\Exception $e) {
        // 如果表不支持软删除，记录日志但不中断流程
        LogService::log("用户角色关联表不支持软删除，跳过：" . $e->getMessage());
      }

      // 3. 软删除等级记录
      try {
        $levelResult = LevelRecord::where('user_id', $userId)->update([
          'delete_time' => $currentTime
        ]);
        if ($levelResult) $deletedCount++;
      } catch (\Exception $e) {
        LogService::log("等级记录表不支持软删除，跳过：" . $e->getMessage());
      }

      // 4. 软删除经验日志
      try {
        $expResult = ExperienceLog::where('user_id', $userId)->update([
          'delete_time' => $currentTime
        ]);
        if ($expResult) $deletedCount++;
      } catch (\Exception $e) {
        LogService::log("经验日志表不支持软删除，跳过：" . $e->getMessage());
      }

      // 5. 软删除登录日志
      try {
        $loginResult = LoginLog::where('user_id', $userId)->update([
          'delete_time' => $currentTime
        ]);
        if ($loginResult) $deletedCount++;
      } catch (\Exception $e) {
        LogService::log("登录日志表不支持软删除，跳过：" . $e->getMessage());
      }

      // 6. 软删除用户评论
      try {
        $commentResult = comments::where('user_id', $userId)->update([
          'delete_time' => $currentTime
        ]);
        if ($commentResult) $deletedCount++;
      } catch (\Exception $e) {
        LogService::log("评论表不支持软删除，跳过：" . $e->getMessage());
      }

      // 7. 软删除用户点赞记录
      try {
        $likeResult = likes::where('user_id', $userId)->update([
          'delete_time' => $currentTime
        ]);
        if ($likeResult) $deletedCount++;
      } catch (\Exception $e) {
        LogService::log("点赞记录表不支持软删除，跳过：" . $e->getMessage());
      }

      // 8. 软删除用户收藏记录
      try {
        $favoriteResult = favorites::where('user_id', $userId)->update([
          'delete_time' => $currentTime
        ]);
        if ($favoriteResult) $deletedCount++;
      } catch (\Exception $e) {
        LogService::log("收藏记录表不支持软删除，跳过：" . $e->getMessage());
      }

      // 9. 软删除会员信息（如果存在）
      try {
        $premiumResult = premium::where('user_id', $userId)->update([
          'delete_time' => $currentTime
        ]);
        if ($premiumResult) $deletedCount++;
      } catch (\Exception $e) {
        LogService::log("会员信息表不支持软删除，跳过：" . $e->getMessage());
      }

      // 提交事务
      Db::commit();

      // 记录详细的操作日志
      LogService::log("用户主动注销账号完成：用户ID {$userId}，共处理 {$deletedCount} 个数据表");

      return [
        'code' => 200,
        'msg' => '账号已注销，所有相关数据将被保留30天后永久删除',
        'data' => [
          'user_id' => $userId,
          'deleted_tables' => $deletedCount,
          'delete_time' => $currentTime
        ]
      ];
    } catch (\Exception $e) {
      // 回滚事务
      Db::rollback();
      LogService::error($e);
      return ['code' => 500, 'msg' => '注销失败：' . $e->getMessage()];
    }
  }

  /**
   * 检查用户名是否可用
   * @param string $username 用户名
   * @return array
   */
  public static function checkUsernameAvailable(string $username): array
  {
    $existingUser = users::where('username', $username)->find();

    return [
      'code' => 200,
      'msg' => $existingUser ? '用户名已存在' : '用户名可用',
      'data' => [
        'username' => $username,
        'available' => !$existingUser
      ]
    ];
  }

  /**
   * 检查邮箱是否可用
   * @param string $email 邮箱
   * @return array
   */
  public static function checkEmailAvailable(string $email): array
  {
    $existingUser = users::where('email', $email)->find();

    return [
      'code' => 200,
      'msg' => $existingUser ? '邮箱已被使用' : '邮箱可用',
      'data' => [
        'email' => $email,
        'available' => !$existingUser
      ]
    ];
  }

  /**
   * 用户登出
   * @param int $userId 用户ID
   * @return array
   */
  public static function logout(int $userId): array
  {
    try {
      // 这里可以添加登出逻辑，比如：
      // 1. 清除 Redis 中的 token
      // 2. 记录登出日志
      // 3. 更新最后活动时间等

      LogService::log("用户登出：用户ID {$userId}");

      return [
        'code' => 200,
        'msg' => '退出登录成功'
      ];
    } catch (\Exception $e) {
      LogService::error($e);
      return ['code' => 500, 'msg' => '退出登录失败：' . $e->getMessage()];
    }
  }
}
