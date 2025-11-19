<?php

namespace app\api\model;

use think\Model;

class PasswordReset extends Model
{
    protected $table = 'bl_password_resets';
    protected $pk = 'id';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = false;

    // 类型转换
    protected $type = [
        'user_id' => 'integer',
        'is_used' => 'boolean',
        'expire_time' => 'datetime',
    ];

    /**
     * 生成重置令牌
     * @param int $userId 用户ID
     * @param string $email 邮箱
     * @return array ['token' => string, 'expire_time' => string]
     */
    public static function generateToken(int $userId, string $email): array
    {
        // 生成唯一令牌（64字符）
        $token = bin2hex(random_bytes(32));
        
        // 设置过期时间（10分钟）
        $expireTime = date('Y-m-d H:i:s', time() + 600);
        
        // 作废该用户之前的所有未使用的令牌
        self::where('user_id', $userId)
            ->where('is_used', 0)
            ->update(['is_used' => 1]);
        
        // 创建新令牌
        $reset = new self();
        $reset->user_id = $userId;
        $reset->email = $email;
        $reset->token = $token;
        $reset->expire_time = $expireTime;
        $reset->is_used = 0;
        $reset->save();
        
        return [
            'token' => $token,
            'expire_time' => $expireTime
        ];
    }

    /**
     * 验证令牌
     * @param string $token 令牌
     * @return array
     */
    public static function verifyToken(string $token): array
    {
        $reset = self::where('token', $token)
            ->where('is_used', 0)
            ->find();

        if (!$reset) {
            return ['valid' => false, 'msg' => '令牌无效'];
        }

        // 检查是否过期
        if (strtotime($reset->expire_time) < time()) {
            return ['valid' => false, 'msg' => '令牌已过期，请重新申请'];
        }

        return [
            'valid' => true,
            'user_id' => $reset->user_id,
            'email' => $reset->email
        ];
    }

    /**
     * 标记令牌为已使用
     * @param string $token 令牌
     * @return bool
     */
    public static function markAsUsed(string $token): bool
    {
        return self::where('token', $token)->update(['is_used' => 1]) > 0;
    }

    /**
     * 清理过期的令牌（可以通过定时任务调用）
     * @return int 清理的数量
     */
    public static function cleanExpiredTokens(): int
    {
        return self::where('expire_time', '<', date('Y-m-d H:i:s'))
            ->delete();
    }
}
