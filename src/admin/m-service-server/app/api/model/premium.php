<?php

namespace app\api\model;

use think\Model;

class premium extends Model
{
    // 设置主键
    protected $pk = 'id';
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;
    
    // 创建时间字段
    protected $createTime = 'create_time';
    
    // 禁用更新时间字段
    protected $updateTime = false;
    
    // 类型转换
    protected $type = [
        'create_time' => 'datetime',
        'expiration_time' => 'datetime'
    ];
    
    /**
     * 判断会员是否有效
     * @return bool
     */
    public function getIsActiveAttr()
    {
        // 获取当前时间
        $now = date('Y-m-d H:i:s');
        
        // 过期时间为空或者过期时间大于当前时间，表示会员有效
        if (empty($this->expiration_time)) {
            return false;
        }
        
        // 2080-01-01 表示永久会员
        if (strpos($this->expiration_time, '2080-01-01') !== false) {
            return true;
        }
        
        return $this->expiration_time > $now;
    }
    
    /**
     * 获取会员类型文本
     */
    public function getTypeTextAttr()
    {
        if (empty($this->remark)) {
            return '普通用户';
        }
        
        return $this->remark;
    }
    
    /**
     * 获取会员类型代码
     */
    public function getTypeAttr()
    {
        if (empty($this->remark)) {
            return 0;
        }
        
        // 根据备注判断会员类型
        if (strpos($this->remark, '永久') !== false) {
            return 4; // 永久会员
        } else if (strpos($this->remark, '年') !== false) {
            return 3; // 年度会员
        } else if (strpos($this->remark, '季') !== false) {
            return 2; // 季度会员
        } else if (strpos($this->remark, '月') !== false) {
            return 1; // 月度会员
        }
        
        return 0; // 未知类型
    }
    
    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(users::class, 'user_id', 'id');
    }
}