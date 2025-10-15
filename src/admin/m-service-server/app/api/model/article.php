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

    /**
     * 关联：文章可访问的用户（多对多）
     * 用于 specific_users 可见性
     */
    public function accessUsers()
    {
        return $this->belongsToMany(
            users::class,           // 关联模型
            'article_user_access',  // 中间表
            'article_id',           // 当前模型外键（article在中间表的字段）
            'user_id'               // 关联模型外键（user在中间表的字段）
        );
    }

    /**
     * 关联：文章可访问的角色（多对多）
     * 用于 specific_roles 可见性
     */
    public function accessRoles()
    {
        return $this->belongsToMany(
            roles::class,           // 关联模型
            'article_role_access',  // 中间表
            'article_id',           // 当前模型外键（article在中间表的字段）
            'role_id'               // 关联模型外键（role在中间表的字段）
        );
    }

    /**
     * 检查用户是否有权访问该文章
     * @param int $userId 用户ID
     * @param array $userRoleIds 用户角色ID数组
     * @return bool
     */
    public function canAccessBy($userId, $userRoleIds = [])
    {
        // 1. 公开文章，所有人可见
        if ($this->visibility === 'public') {
            return true;
        }

        // 2. 作者始终可以访问自己的文章
        if ($this->author_id == $userId) {
            return true;
        }

        // 3. 私密文章，只有作者可见
        if ($this->visibility === 'private') {
            return false;
        }

        // 4. 登录可见，检查是否已登录
        if ($this->visibility === 'login_required') {
            return $userId > 0;
        }

        // 5. 指定用户，检查用户访问权限表
        if ($this->visibility === 'specific_users') {
            if ($userId <= 0) {
                return false;
            }
            return $this->accessUsers()->where('user_id', $userId)->count() > 0;
        }

        // 6. 指定角色，检查角色访问权限表
        if ($this->visibility === 'specific_roles') {
            if (empty($userRoleIds)) {
                return false;
            }
            return $this->accessRoles()->whereIn('role_id', $userRoleIds)->count() > 0;
        }

        return false;
    }
}
