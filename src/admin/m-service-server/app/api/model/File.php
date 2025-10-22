<?php

namespace app\api\model;

use think\Model;
use think\model\concern\SoftDelete;

class File extends Model
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
    
    // 主键设置
    protected $pk = 'file_id';
    
    // 设置数据表名称
    protected $table = 'bl_files';
    
    // 关联上传用户
    public function user()
    {
        return $this->hasOne(users::class, 'id', 'user_id');
    }
    
    // 根据存储类型获取文本描述
    public function getStorageTypeTextAttr($value, $data)
    {
        $types = [
            0 => '本地存储',
            1 => '阿里云OSS',
            2 => '七牛云',
            3 => '腾讯云COS'
        ];
        return $types[$data['storage_type']] ?? '未知存储';
    }
    
    // 获取文件大小的格式化显示
    public function getFileSizeFormatAttr($value, $data)
    {
        $size = $data['file_size'];
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }
        return round($size, 2) . ' ' . $units[$i];
    }
} 