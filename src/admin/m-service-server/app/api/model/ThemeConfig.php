<?php

namespace app\api\model;

use think\Model;
use think\model\concern\SoftDelete;

class ThemeConfig extends Model
{
    // 引入软删除特性
    use SoftDelete;
    
    // 设置表名
    protected $name = 'theme_configs';
    
    // 设置表前缀
    protected $prefix = 'bl_';
    
    // 软删除字段设置
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = null;
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    // 设置字段信息
    protected $schema = [
        'id'            => 'int',
        'theme_key'     => 'string',
        'theme_name'    => 'string',
        'description'   => 'string',
        'preview_image' => 'string',
        'config_data'   => 'json',
        'is_system'     => 'int',
        'is_current'    => 'int',
        'is_active'     => 'int',
        'sort_order'    => 'int',
        'create_time'   => 'datetime',
        'update_time'   => 'datetime',
        'delete_time'   => 'datetime'
    ];
    
    // 允许批量赋值的字段
    protected $field = [
        'theme_key',
        'theme_name', 
        'description',
        'preview_image',
        'config_data',
        'is_system',
        'is_current',
        'is_active',
        'sort_order'
    ];
    
    // JSON字段
    protected $json = ['config_data'];
    
    // 状态常量
    const STATUS_INACTIVE = 0;  // 未启用
    const STATUS_ACTIVE = 1;    // 启用
    
    const CURRENT_NO = 0;       // 非当前主题
    const CURRENT_YES = 1;      // 当前主题
    
    const SYSTEM_NO = 0;        // 非系统主题
    const SYSTEM_YES = 1;       // 系统主题
    
    /**
     * 状态描述映射
     */
    public static $statusMap = [
        self::STATUS_INACTIVE => '未启用',
        self::STATUS_ACTIVE => '启用'
    ];
    
    /**
     * 获取当前使用的主题
     * @return ThemeConfig|null
     */
    public static function getCurrentTheme()
    {
        return self::where('is_current', self::CURRENT_YES)
                   ->where('is_active', self::STATUS_ACTIVE)
                   ->find();
    }
    
    /**
     * 获取所有启用的主题列表
     * @return \think\Collection
     */
    public static function getActiveThemes()
    {
        return self::where('is_active', self::STATUS_ACTIVE)
                   ->order('sort_order', 'asc')
                   ->order('id', 'asc')
                   ->select();
    }
    
    /**
     * 根据主题键获取主题
     * @param string $themeKey
     * @return ThemeConfig|null
     */
    public static function getByThemeKey($themeKey)
    {
        return self::where('theme_key', $themeKey)
                   ->where('is_active', self::STATUS_ACTIVE)
                   ->find();
    }
    
    /**
     * 设置当前主题
     * @param int $themeId
     * @return bool
     * @throws \Exception
     */
    public static function setCurrentTheme($themeId)
    {
        // 开启事务
        self::startTrans();
        try {
            // 先将所有主题设为非当前
            self::where('is_current', self::CURRENT_YES)
                ->update(['is_current' => self::CURRENT_NO]);
            
            // 设置指定主题为当前
            $result = self::where('id', $themeId)
                         ->where('is_active', self::STATUS_ACTIVE)
                         ->update(['is_current' => self::CURRENT_YES]);
            
            if (!$result) {
                throw new \Exception('主题不存在或未启用');
            }
            
            // 提交事务
            self::commit();
            return true;
            
        } catch (\Exception $e) {
            // 回滚事务
            self::rollback();
            throw $e;
        }
    }
    
    /**
     * 切换主题状态
     * @param int $themeId
     * @return bool
     * @throws \Exception
     */
    public static function toggleStatus($themeId)
    {
        $theme = self::find($themeId);
        if (!$theme) {
            throw new \Exception('主题不存在');
        }
        
        // 如果是当前主题，不允许禁用
        if ($theme->is_current == self::CURRENT_YES && $theme->is_active == self::STATUS_ACTIVE) {
            throw new \Exception('当前使用的主题不能禁用');
        }
        
        $newStatus = $theme->is_active == self::STATUS_ACTIVE ? self::STATUS_INACTIVE : self::STATUS_ACTIVE;
        return $theme->save(['is_active' => $newStatus]);
    }
    
    /**
     * 验证主题键是否唯一
     * @param string $themeKey
     * @param int $excludeId 排除的ID（用于更新时验证）
     * @return bool
     */
    public static function isThemeKeyUnique($themeKey, $excludeId = null)
    {
        $query = self::where('theme_key', $themeKey);
        if ($excludeId) {
            $query->where('id', '<>', $excludeId);
        }
        return $query->count() == 0;
    }
    
    /**
     * 获取状态文本
     * @param mixed $value
     * @param array $data
     * @return string
     */
    public function getStatusTextAttr($value, $data)
    {
        return self::$statusMap[$data['is_active']] ?? '未知';
    }
    
    /**
     * 获取是否当前主题文本
     * @param mixed $value
     * @param array $data
     * @return string
     */
    public function getCurrentTextAttr($value, $data)
    {
        return $data['is_current'] == self::CURRENT_YES ? '是' : '否';
    }
    
    /**
     * 获取主题类型文本
     * @param mixed $value
     * @param array $data
     * @return string
     */
    public function getTypeTextAttr($value, $data)
    {
        return $data['is_system'] == self::SYSTEM_YES ? '系统主题' : '自定义主题';
    }
}
