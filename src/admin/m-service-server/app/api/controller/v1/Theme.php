<?php

namespace app\api\controller\v1;

use app\BaseController;
use app\api\services\ThemeService;
use app\api\services\LogService;
use think\response\Json;
use think\facade\Request;
use think\facade\Validate;

class Theme extends BaseController
{
    /**
     * 获取当前主题配置（客户端调用）
     * @return Json
     */
    public function current(): Json
    {
        try {
            // 记录操作日志
            LogService::log('获取当前主题配置', []);

            $result = ThemeService::getCurrentTheme();
            return json($result);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'msg' => '获取当前主题配置失败: ' . $e->getMessage(),
                'data' => null
            ]);
        }
    }

    /**
     * 获取主题列表
     * @return Json
     */
    public function list(): Json
    {
        try {
            $params = Request::param();

            // 记录操作日志
            LogService::log('获取主题列表', $params);

            $result = ThemeService::getThemeList($params);
            return json($result);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'msg' => '获取主题列表失败: ' . $e->getMessage(),
                'data' => null
            ]);
        }
    }

    /**
     * 根据主题键获取主题详情
     * @param string $key
     * @return Json
     */
    public function detail($key = ''): Json
    {
        try {
            if (empty($key)) {
                return json([
                    'code' => 400,
                    'msg' => '主题键不能为空',
                    'data' => null
                ]);
            }

            // 记录操作日志
            LogService::log('获取主题详情', ['theme_key' => $key]);

            $result = ThemeService::getThemeByKey($key);
            return json($result);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'msg' => '获取主题详情失败: ' . $e->getMessage(),
                'data' => null
            ]);
        }
    }

    /**
     * 创建主题配置（管理端）
     * @return Json
     */
    public function create(): Json
    {
        try {
            $params = Request::param();

            // 验证参数
            $validate = Validate::rule([
                'theme_key'   => 'require|max:100|alphaNum',
                'theme_name'  => 'require|max:200',
                'config_data' => 'require',
                'sort_order'  => 'integer|between:0,9999'
            ])->message([
                'theme_key.require'   => '主题键不能为空',
                'theme_key.max'       => '主题键长度不能超过100个字符',
                'theme_key.alphaNum'  => '主题键只能包含字母和数字',
                'theme_name.require'  => '主题名称不能为空',
                'theme_name.max'      => '主题名称长度不能超过200个字符',
                'config_data.require' => '主题配置数据不能为空',
                'sort_order.integer'  => '排序权重必须为整数',
                'sort_order.between'  => '排序权重必须在0-9999之间'
            ]);

            if (!$validate->check($params)) {
                return json([
                    'code' => 400,
                    'msg' => $validate->getError(),
                    'data' => null
                ]);
            }

            // 处理配置数据
            if (is_string($params['config_data'])) {
                $configData = json_decode($params['config_data'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return json([
                        'code' => 400,
                        'msg' => '配置数据格式错误',
                        'data' => null
                    ]);
                }
                $params['config_data'] = $configData;
            }

            // 设置默认值
            $params['is_system'] = $params['is_system'] ?? 0;
            $params['is_current'] = 0; // 新创建的主题默认不是当前主题
            $params['is_active'] = $params['is_active'] ?? 1;
            $params['sort_order'] = $params['sort_order'] ?? 0;

            // 记录操作日志
            LogService::log('创建主题配置', $params);

            $result = ThemeService::createTheme($params);
            return json($result);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'msg' => '创建主题配置失败: ' . $e->getMessage(),
                'data' => null
            ]);
        }
    }

    /**
     * 更新主题配置（管理端）
     * @param int $id
     * @return Json
     */
    public function update($id = 0): Json
    {
        try {
            if (empty($id)) {
                return json([
                    'code' => 400,
                    'msg' => '主题ID不能为空',
                    'data' => null
                ]);
            }

            $params = Request::param();
            unset($params['id']); // 移除ID参数

            // 验证参数
            $validate = Validate::rule([
                'theme_key'   => 'max:100|alphaNum',
                'theme_name'  => 'max:200',
                'sort_order'  => 'integer|between:0,9999'
            ])->message([
                'theme_key.max'       => '主题键长度不能超过100个字符',
                'theme_key.alphaNum'  => '主题键只能包含字母和数字',
                'theme_name.max'      => '主题名称长度不能超过200个字符',
                'sort_order.integer'  => '排序权重必须为整数',
                'sort_order.between'  => '排序权重必须在0-9999之间'
            ]);

            if (!$validate->check($params)) {
                return json([
                    'code' => 400,
                    'msg' => $validate->getError(),
                    'data' => null
                ]);
            }

            // 处理配置数据
            if (isset($params['config_data']) && is_string($params['config_data'])) {
                $configData = json_decode($params['config_data'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return json([
                        'code' => 400,
                        'msg' => '配置数据格式错误',
                        'data' => null
                    ]);
                }
                $params['config_data'] = $configData;
            }

            // 记录操作日志
            LogService::log('更新主题配置', array_merge(['id' => $id], $params));

            $result = ThemeService::updateTheme($id, $params);
            return json($result);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'msg' => '更新主题配置失败: ' . $e->getMessage(),
                'data' => null
            ]);
        }
    }

    /**
     * 删除主题配置（管理端）
     * @param int $id
     * @return Json
     */
    public function delete($id = 0): Json
    {
        try {
            if (empty($id)) {
                return json([
                    'code' => 400,
                    'msg' => '主题ID不能为空',
                    'data' => null
                ]);
            }

            // 记录操作日志
            LogService::log('删除主题配置', ['id' => $id]);

            $result = ThemeService::deleteTheme($id);
            return json($result);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'msg' => '删除主题配置失败: ' . $e->getMessage(),
                'data' => null
            ]);
        }
    }

    /**
     * 设置当前主题（管理端）
     * @return Json
     */
    public function setCurrent(): Json
    {
        try {
            $themeId = Request::param('theme_id');

            if (empty($themeId)) {
                return json([
                    'code' => 400,
                    'msg' => '主题ID不能为空',
                    'data' => null
                ]);
            }

            // 记录操作日志
            LogService::log('设置当前主题', ['theme_id' => $themeId]);

            $result = ThemeService::setCurrentTheme($themeId);
            return json($result);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'msg' => '设置当前主题失败: ' . $e->getMessage(),
                'data' => null
            ]);
        }
    }

    /**
     * 切换主题状态（管理端）
     * @param int $id
     * @return Json
     */
    public function toggleStatus($id = 0): Json
    {
        try {
            if (empty($id)) {
                return json([
                    'code' => 400,
                    'msg' => '主题ID不能为空',
                    'data' => null
                ]);
            }

            // 记录操作日志
            LogService::log('切换主题状态', ['id' => $id]);

            $result = ThemeService::toggleThemeStatus($id);
            return json($result);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'msg' => '切换主题状态失败: ' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
}
