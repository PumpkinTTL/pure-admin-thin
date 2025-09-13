<?php

namespace app\api\model;

use think\Model;
use think\model\concern\SoftDelete;

class resource extends Model
{
    // 引入软删除特性
    use SoftDelete;
    
    // 软删除字段设置
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = null;
    
    // 设置时间戳格式为日期时间格式
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 解决true和false转换1和0的问题
    public function getIsPremiumAttr($value, $data)
    {
        $IsPremium = [1 => true, 0 => false];
        return $IsPremium[$value];
    }

    // 一对多关联下载方式
    public function downloadLinks()
    {
        return $this->hasMany(DownloadMethod::class, 'resource_id', 'id');
    }

    // 多对多关联标签
    public function tags()
    {
        return $this->belongsToMany(category::class, 'resource_tag', 'category_id', 'resource_id');
    }

    // 关联上传作者
    public function author()
    {
        return $this->hasOne(users::class, 'id', 'user_id');
    }

//    关联类别
    public function category()
    {
        return $this->hasOne(category::class, 'id', 'category_id');
    }
}