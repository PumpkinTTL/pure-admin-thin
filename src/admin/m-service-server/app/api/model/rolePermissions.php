<?php

namespace app\api\model;

use think\Model;

class rolePermissions extends Model
{
    // 设置表名
    protected $name = 'role_permissions';
    
    // 关闭自动写入时间戳
    protected $autoWriteTimestamp = false;
    
    // 设置主键
    protected $pk = ['role_id', 'permission_id'];
    
    // 角色关联
    public function role()
    {
        return $this->belongsTo(roles::class, 'role_id', 'id');
    }
    
    // 权限关联
    public function permission()
    {
        return $this->belongsTo(permissions::class, 'permission_id', 'id');
    }
} 