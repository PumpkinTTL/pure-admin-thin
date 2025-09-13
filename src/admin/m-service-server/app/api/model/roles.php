<?php

namespace app\api\model;

use think\Model;
use think\model\concern\SoftDelete;

class roles extends Model
{
    use SoftDelete;
    
    // 定义软删除字段
    protected $deleteTime = 'delete_time';
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'name'        => 'string',
        'description' => 'string',
        'iden'        => 'string',
        'create_time' => 'datetime',
        'update_time' => 'datetime',
        'delete_time' => 'datetime',
        'status'      => 'int',
        'show_weight' => 'int'
    ];
    
    // 追加输出的属性
    protected $append = [];
    
    // 隐藏的属性
    protected $hidden = ['pivot'];
    
    // 角色与用户的多对多关联 - 使用模型关联方法，ThinkPHP会自动处理表前缀
    public function users(): \think\model\relation\BelongsToMany
    {
        return $this->belongsToMany(users::class, 'user_roles', 'role_id', 'user_id');
    }
    
    // 角色和权限多对多关联 - 使用模型关联方法，ThinkPHP会自动处理表前缀
    public function permissions(): \think\model\relation\BelongsToMany
    {
        return $this->belongsToMany(permissions::class, 'role_permissions', 'role_id', 'permission_id');
    }
    
    // 获取状态文字描述
    public function getStatusTextAttr($value, $data)
    {
        $status = $data['status'] ?? 0;
        $statusArray = [
            0 => '禁用',
            1 => '启用'
        ];
        return $statusArray[$status] ?? '未知';
    }
}