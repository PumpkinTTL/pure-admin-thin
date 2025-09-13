<?php

namespace app\api\model;

use think\Model;
use think\model\concern\SoftDelete;

class article extends Model
{
    // 引入软删除特性
    use SoftDelete;
    
    // 软删除字段设置
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = null;
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    // 设置不需要写入数据库的字段
    protected $disuse = ['real'];

    // 一对一关联：文章属于一个分类（主分类）
    public function category()
    {
        return $this->belongsTo(category::class, 'category_id', 'id');
    }

    // 一对多关联：文章有多个标签（标签也存储在category表中）
    public function tags()
    {
        return $this->belongsToMany(
            category::class,    // 关联模型
            'article_tag',     // 中间表名（需要创建）
            'category_id',       // 当前模型在中间表的字段
            'article_id'       // 目标模型在中间表的字段
        );
    }

//    关联作者信息
    public function author()
    {
        return $this->belongsTo(users::class, 'author_id', 'id');
    }

    // 关联收藏记录（用于统计收藏量）
    public function favorites()
    {
        return $this->hasMany(favorites::class, 'article_id')
            ->whereNull('delete_time'); // 只统计未删除的收藏
    }

    /**
     * 获取文章收藏量（动态属性）
     * 调用方式：$article->favorite_count
     */
    public function getFavoriteCountAttr()
    {
        return $this->favorites()->count();
    }

    /**
     * 获取是否被指定用户收藏（动态方法）
     * 调用方式：$article->isFavoritedBy($userId)
     */
    public function isFavoritedBy($userId)
    {
        if (!$userId) return false;
        return $this->favorites()
                ->where('user_id', $userId)
                ->count() > 0;
    }

    // 新增点赞关联关系
    public function likes()
    {
        return $this->hasMany(likes::class, 'article_id')
            ->whereNull('delete_time');
    }

// 获取点赞数（动态属性）
    public function getLikeCountAttr()
    {
        return $this->likes()->count();
    }

// 检查是否被指定用户点赞（动态方法）
    public function isLikedBy($userId)
    {
        if (!$userId) return false;
        return $this->likes()
                ->where('user_id', $userId)
                ->count() > 0;
    }

    public function comments()
    {
        return $this->hasMany(comments::class, 'article_id')
            ->whereNull('delete_time');
    }
    // 转换true和0-1
    public function getIsTopAttr($value)
    {
        return $value ? 1 : 0;
    }
    public function getIsRecommendAttr($value)
    {
        return $value ? 1 : 0;
    }
    public function getIsOriginalAttr($value)
    {
        return $value ? 1 : 0;  
    }
    // 0-草稿，1-发布，2-待审核，3-已下架
    public function getStatusAttr($value)
    {
        return $value ? 1 : 0;
    }
}
