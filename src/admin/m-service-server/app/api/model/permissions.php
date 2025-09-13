<?php

namespace app\api\model;

use think\Model;
use think\model\concern\SoftDelete;

class permissions extends Model
{
  // 引入软删除特性
  use SoftDelete;
  
  // 软删除字段设置
  protected $deleteTime = 'delete_time';
  protected $defaultSoftDelete = null;
  
  // 隐藏中间表字段
  protected $hidden = ['pivot'];

  // 自动写入时间戳
  protected $autoWriteTimestamp = true;
  protected $createTime = 'create_time';
  protected $updateTime = 'update_time';

  // 设置字段信息
  protected $schema = [
    'id'           => 'int',
    'name'         => 'string',
    'description'  => 'string',
    'create_time'  => 'datetime',
    'update_time'  => 'datetime',
    'delete_time'  => 'datetime',
    'iden'         => 'string'
  ];

  // 追加输出的属性
  protected $append = [];
  
  // 设置不需要写入数据库的字段
  protected $disuse = ['real'];

  // 与角色的多对多关联 - 使用模型关联方法，ThinkPHP会自动处理表前缀
  public function roles()
  {
    return $this->belongsToMany(roles::class, 'role_permissions', 'permission_id', 'role_id');
  }

  // 与菜单的一对多关联 - 使用模型关联方法，ThinkPHP会自动处理表前缀
  public function menus()
  {
    return $this->hasMany(Menus::class, 'permission_id', 'id');
  }
  
  // 与API的多对多关联
  public function apis()
  {
    return $this->belongsToMany(Api::class, permissionsApi::class, 'permission_id', 'api_id');
  }
}