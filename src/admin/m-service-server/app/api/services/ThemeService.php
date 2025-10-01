<?php

namespace app\api\services;

use app\api\model\ThemeConfig;
use think\facade\Log;
use think\facade\Cache;

class ThemeService
{
    // 缓存键前缀
    const CACHE_PREFIX = 'theme_';

    // 缓存时间（秒）
    const CACHE_TIME = 3600; // 1小时

    /**
     * 获取当前主题配置（客户端调用）
     * @return array
     */
    public static function getCurrentTheme()
    {
        try {
            // 尝试从缓存获取
            $cacheKey = self::CACHE_PREFIX . 'current';
            $theme = Cache::get($cacheKey);

            if (!$theme) {
                // 从数据库获取
                $themeModel = ThemeConfig::getCurrentTheme();
                if (!$themeModel) {
                    return [
                        'code' => 404,
                        'msg' => '未找到当前主题配置',
                        'data' => null
                    ];
                }

                $theme = $themeModel->toArray();

                // 缓存结果
                Cache::set($cacheKey, $theme, self::CACHE_TIME);
            }

            return [
                'code' => 200,
                'msg' => 'success',
                'data' => $theme
            ];
        } catch (\Exception $e) {
            Log::error('获取当前主题失败: ' . $e->getMessage());
            return [
                'code' => 500,
                'msg' => '获取当前主题失败: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * 获取主题列表
     * @param array $params 查询参数
     * @return array
     */
    public static function getThemeList($params = [])
    {
        try {
            $page = $params['page'] ?? 1;
            $pageSize = $params['page_size'] ?? 20;
            $keyword = $params['keyword'] ?? '';
            $isActive = $params['is_active'] ?? null;

            // 处理空字符串的情况
            if ($isActive === '' || $isActive === null) {
                $isActive = null;
            } else {
                $isActive = (int)$isActive;
            }

            $query = ThemeConfig::order('sort_order', 'asc')
                ->order('id', 'asc');

            // 关键词搜索
            if (!empty($keyword)) {
                $query->where('theme_name|description', 'like', '%' . $keyword . '%');
            }

            // 状态筛选
            if ($isActive !== null) {
                $query->where('is_active', $isActive);
            }

            // 分页查询
            $result = $query->paginate([
                'list_rows' => $pageSize,
                'page' => $page
            ]);

            $list = $result->items();
            $total = $result->total();

            // 添加额外字段
            foreach ($list as &$item) {
                $item['status_text'] = $item->status_text;
                $item['current_text'] = $item->current_text;
                $item['type_text'] = $item->type_text;
            }

            return [
                'code' => 200,
                'msg' => 'success',
                'data' => [
                    'list' => $list,
                    'total' => $total,
                    'page' => $page,
                    'page_size' => $pageSize
                ]
            ];
        } catch (\Exception $e) {
            Log::error('获取主题列表失败: ' . $e->getMessage());
            return [
                'code' => 500,
                'msg' => '获取主题列表失败: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * 根据主题键获取主题详情
     * @param string $themeKey
     * @return array
     */
    public static function getThemeByKey($themeKey)
    {
        try {
            // 尝试从缓存获取
            $cacheKey = self::CACHE_PREFIX . 'key_' . $themeKey;
            $theme = Cache::get($cacheKey);

            if (!$theme) {
                $themeModel = ThemeConfig::getByThemeKey($themeKey);
                if (!$themeModel) {
                    return [
                        'code' => 404,
                        'msg' => '主题不存在',
                        'data' => null
                    ];
                }

                $theme = $themeModel->toArray();

                // 缓存结果
                Cache::set($cacheKey, $theme, self::CACHE_TIME);
            }

            return [
                'code' => 200,
                'msg' => 'success',
                'data' => $theme
            ];
        } catch (\Exception $e) {
            Log::error('获取主题详情失败: ' . $e->getMessage());
            return [
                'code' => 500,
                'msg' => '获取主题详情失败: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * 创建主题配置
     * @param array $data
     * @return array
     */
    public static function createTheme($data)
    {
        try {
            // 验证主题键唯一性
            if (!ThemeConfig::isThemeKeyUnique($data['theme_key'])) {
                return [
                    'code' => 400,
                    'msg' => '主题键已存在',
                    'data' => null
                ];
            }

            // 创建主题
            $theme = ThemeConfig::create($data);

            // 清除相关缓存
            self::clearThemeCache();

            return [
                'code' => 200,
                'msg' => '创建成功',
                'data' => $theme
            ];
        } catch (\Exception $e) {
            Log::error('创建主题失败: ' . $e->getMessage());
            return [
                'code' => 500,
                'msg' => '创建主题失败: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * 更新主题配置
     * @param int $id
     * @param array $data
     * @return array
     */
    public static function updateTheme($id, $data)
    {
        try {
            $theme = ThemeConfig::find($id);
            if (!$theme) {
                return [
                    'code' => 404,
                    'msg' => '主题不存在',
                    'data' => null
                ];
            }

            // 验证主题键唯一性（排除当前记录）
            if (isset($data['theme_key']) && !ThemeConfig::isThemeKeyUnique($data['theme_key'], $id)) {
                return [
                    'code' => 400,
                    'msg' => '主题键已存在',
                    'data' => null
                ];
            }

            // 更新主题
            $theme->save($data);

            // 清除相关缓存
            self::clearThemeCache();

            return [
                'code' => 200,
                'msg' => '更新成功',
                'data' => $theme
            ];
        } catch (\Exception $e) {
            Log::error('更新主题失败: ' . $e->getMessage());
            return [
                'code' => 500,
                'msg' => '更新主题失败: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * 删除主题配置
     * @param int $id
     * @return array
     */
    public static function deleteTheme($id)
    {
        try {
            $theme = ThemeConfig::find($id);
            if (!$theme) {
                return [
                    'code' => 404,
                    'msg' => '主题不存在',
                    'data' => null
                ];
            }

            // 检查是否为当前主题
            if ($theme->is_current == ThemeConfig::CURRENT_YES) {
                return [
                    'code' => 400,
                    'msg' => '当前使用的主题不能删除',
                    'data' => null
                ];
            }

            // 软删除
            $theme->delete();

            // 清除相关缓存
            self::clearThemeCache();

            return [
                'code' => 200,
                'msg' => '删除成功',
                'data' => null
            ];
        } catch (\Exception $e) {
            Log::error('删除主题失败: ' . $e->getMessage());
            return [
                'code' => 500,
                'msg' => '删除主题失败: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * 设置当前主题
     * @param int $themeId
     * @return array
     */
    public static function setCurrentTheme($themeId)
    {
        try {
            ThemeConfig::setCurrentTheme($themeId);

            // 清除相关缓存
            self::clearThemeCache();

            return [
                'code' => 200,
                'msg' => '设置成功',
                'data' => null
            ];
        } catch (\Exception $e) {
            Log::error('设置当前主题失败: ' . $e->getMessage());
            return [
                'code' => 500,
                'msg' => '设置当前主题失败: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * 切换主题状态
     * @param int $themeId
     * @return array
     */
    public static function toggleThemeStatus($themeId)
    {
        try {
            ThemeConfig::toggleStatus($themeId);

            // 清除相关缓存
            self::clearThemeCache();

            return [
                'code' => 200,
                'msg' => '状态切换成功',
                'data' => null
            ];
        } catch (\Exception $e) {
            Log::error('切换主题状态失败: ' . $e->getMessage());
            return [
                'code' => 500,
                'msg' => '切换主题状态失败: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * 清除主题相关缓存
     */
    private static function clearThemeCache()
    {
        // 清除当前主题缓存
        Cache::delete(self::CACHE_PREFIX . 'current');

        // 清除所有主题键缓存（这里简化处理，实际可以更精确）
        Cache::clear();
    }
}
