<?php

namespace app\api\model;

use think\Model;

/**
 * 登录日志模型
 */
class LoginLog extends Model
{
    protected $table = 'bl_login_logs';
    protected $pk = 'id';
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'login_time';
    protected $updateTime = false;
    
    // 类型转换
    protected $type = [
        'id' => 'integer',
        'user_id' => 'integer',
        'status' => 'integer',
    ];
    
    // 登录状态常量
    const STATUS_SUCCESS = 1; // 登录成功
    const STATUS_FAILED = 0;  // 登录失败
    
    /**
     * 记录登录成功日志
     * @param int $userId 用户ID
     * @param string $username 用户名
     * @param string $ip IP地址
     * @param string $userAgent 用户代理
     * @param string $platform 平台
     * @param string $device 设备指纹
     * @return void
     */
    public static function recordLogin(int $userId, string $username, string $ip, string $userAgent, string $platform = 'Web', string $device = ''): void
    {
        self::create([
            'user_id' => $userId,
            'username' => $username,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'platform' => $platform,
            'device' => $device,
            'status' => self::STATUS_SUCCESS,
            'fail_reason' => null,
        ]);
    }
    
    /**
     * 记录登录失败日志
     * @param string $username 用户名（可能是账号/邮箱/手机号）
     * @param string $ip IP地址
     * @param string $failReason 失败原因
     * @param string $userAgent 用户代理
     * @param string $platform 平台
     * @return void
     */
    public static function recordLoginFailed(string $username, string $ip, string $failReason, string $userAgent, string $platform = 'Web'): void
    {
        self::create([
            'user_id' => null,
            'username' => $username,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'platform' => $platform,
            'device' => '',
            'status' => self::STATUS_FAILED,
            'fail_reason' => $failReason,
        ]);
    }
    
    /**
     * 获取用户最近的登录记录
     * @param int $userId 用户ID
     * @param int $limit 返回数量
     * @return array
     */
    public static function getRecentLogins(int $userId, int $limit = 10): array
    {
        return self::where('user_id', $userId)
            ->where('status', self::STATUS_SUCCESS)
            ->order('login_time', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }
    
    /**
     * 获取用户最后登录时间
     * @param int $userId 用户ID
     * @return string|null
     */
    public static function getLastLoginTime(int $userId): ?string
    {
        $log = self::where('user_id', $userId)
            ->where('status', self::STATUS_SUCCESS)
            ->order('login_time', 'desc')
            ->find();
        
        return $log ? $log->login_time : null;
    }
    
    /**
     * 统计某个IP的失败登录次数
     * @param string $ip IP地址
     * @param int $minutes 时间范围（分钟）
     * @return int
     */
    public static function countFailedAttempts(string $ip, int $minutes = 30): int
    {
        $startTime = date('Y-m-d H:i:s', time() - $minutes * 60);
        
        return self::where('ip', $ip)
            ->where('status', self::STATUS_FAILED)
            ->where('login_time', '>=', $startTime)
            ->count();
    }
}
