<?php

namespace app\api\model;

use think\Model;
use think\model\concern\SoftDelete;

class users extends Model
{
//    软删除
    use SoftDelete;

    protected $pk = 'id';
    protected $deleteTime = 'delete_time';
    protected $autoWriteTimestamp = true;
    protected $hidden = ['password', 'roles.pivot'];
//    过滤字段
    protected $disuse = ['[roles]'];

    // 类型转换        
    protected $type = [
        'status' => 'boolean',
        'last_login_time' => 'timestamp',
    ];
    
    /**
     * 密码加密修改器（SHA256 + 盐值）
     * @param $value
     * @return string
     */
    public function setPasswordAttr($value)
    {
        // 使用应用密钥作为盐值
        $salt = env('APP_KEY', 'default_salt_key_change_me');
        return hash('sha256', $value . $salt);
    }
    
//    解决渲染的问题 
    public function getStatusAttr($value)
    {
        $status = [1 => true, 0 => false];
        return $status[$value];
    }

    // 用户与角色的多对多关联
    public function roles(): \think\model\relation\BelongsToMany
    {
        return $this->belongsToMany(roles::class, 'user_roles', 'role_id', 'user_id');
    }

    // 关联premium
    public function premium(): \think\model\relation\HasOne
    {
        return $this->hasOne(premium::class, 'user_id', 'id');
    }

    // 关联用户等级记录（一个用户有多个类型的等级记录）
    public function levelRecords(): \think\model\relation\HasMany
    {
        return $this->hasMany(LevelRecord::class, 'target_id', 'id');
    }
    
    /**
     * 模型转数组时，将levelRecords转为level_records
     */
    public function toArray(): array
    {
        $data = parent::toArray();
        
        // 如果存在levelRecords，同时创廻a level_records 别名
        if (isset($data['levelRecords'])) {
            $data['level_records'] = $data['levelRecords'];
        }
        
        return $data;
    }

    // 关联经验日志
    public function experienceLogs(): \think\model\relation\HasMany
    {
        return $this->hasMany(ExperienceLog::class, 'user_id', 'id');
    }
    
    /**
     * 创建或更新会员
     * @param int $userId 用户ID
     * @param array $premiumData 会员数据
     * @return bool|premium 成功返回premium对象，失败返回false
     */
    public static function createOrUpdatePremium(int $userId, array $premiumData)
    {
        // 如果会员数据为空，直接返回成功
        if (empty($premiumData)) {
            return true;
        }
        
        try {
            // 查找用户
            $user = self::findOrFail($userId);
            
            // 记录日志
            \app\api\services\LogService::log("处理用户会员数据 - 用户ID: {$userId}", [], 'info');
            
            // 检查是否已有会员记录
            $premium = $user->premium;
            
            if (!$premium) {
                // 创建新的会员记录
                $premium = new premium();
                $premium->user_id = $userId;
                
                // 如果没有提供创建时间，设置为当前时间
                if (!isset($premiumData['create_time'])) {
                    $premium->create_time = date('Y-m-d H:i:s');
                } else {
                    $premium->create_time = $premiumData['create_time'];
                }
                
                // 生成会员ID（如果没有提供）
                if (isset($premiumData['id'])) {
                    $premium->id = $premiumData['id'];
                } else {
                    // 使用NumUtil生成5位数字ID
                    $premiumId = \utils\NumUtil::generateNumberCode(1, 5);
                    
                    // 确保ID不重复
                    while (premium::where('id', $premiumId)->find()) {
                        $premiumId = \utils\NumUtil::generateNumberCode(1, 5);
                    }
                    
                    $premium->id = $premiumId;
                }
            } else {
                // 判断是否永久会员
                if (strpos($premium->expiration_time, '2080-01-01') !== false) {
                    // 已经是永久会员，不需要更新
                    return $premium;
                }
                
                // 如果提供了新的ID，尝试更新
                if (isset($premiumData['id']) && $premium->id != $premiumData['id']) {
                    // 检查新ID是否已存在
                    if (!premium::where('id', $premiumData['id'])->find()) {
                        $premium->id = $premiumData['id'];
                    } else {
                        throw new \Exception('会员ID已存在，无法修改');
                    }
                }
            }
            
            // 设置会员数据
            if (isset($premiumData['expiration_time'])) {
                $premium->expiration_time = $premiumData['expiration_time'];
            } else if (!$premium->isExists() || empty($premium->expiration_time)) {
                // 如果是新记录且没有设置过期时间，默认设置为30天后
                $premium->expiration_time = date('Y-m-d H:i:s', strtotime('+30 days'));
            }
            
            // 设置会员备注
            if (isset($premiumData['remark'])) {
                $premium->remark = $premiumData['remark'];
            } else if (!$premium->isExists() || empty($premium->remark)) {
                // 如果是新记录且没有设置备注，设置默认备注
                $premium->remark = '普通会员';
            }
            
            // 保存会员数据
            $result = $premium->save();
            
            if (!$result && !$premium->isExists()) {
                throw new \Exception('会员数据保存失败');
            }
            
            return $premium;
        } catch (\Exception $e) {
            // 记录错误日志
            \app\api\services\LogService::error($e);
            return false;
        }
    }
    
    /**
     * 验证用户密码（SHA256 + 盐值）
     * @param string $password
     * @return bool
     */
    public function verifyPassword(string $password): bool
    {
        $salt = env('APP_KEY', 'default_salt_key_change_me');
        $hashedPassword = hash('sha256', $password . $salt);
        return $hashedPassword === $this->password;
    }
    
    /**
     * 更新用户登录信息
     */
    public function updateLoginInfo(): void
    {
        $this->last_login_time = time();
        $this->last_login_ip = request()->ip();
        $this->save();
    }
}