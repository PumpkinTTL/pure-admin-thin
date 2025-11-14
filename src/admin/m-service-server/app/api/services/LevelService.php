<?php

namespace app\api\services;

use app\api\model\Level;
use app\api\model\LevelRecord;
use app\api\services\LogService;
use think\facade\Db;
use utils\NumUtil;

class LevelService
{
    /**
     * 获取等级列表
     * @param array $params 查询参数，包含 type（必需）、name、status、page、page_size
     */
    public static function getLevelList($params = [])
    {
        try {
            $type = $params['type'] ?? Level::TYPE_USER;
            
            // 验证类型
            if (!Level::isValidType($type)) {
                return ['code' => 0, 'msg' => '无效的等级类型'];
            }
            
            $where = [['type', '=', $type]];
            
            if (!empty($params['name'])) {
                $where[] = ['name', 'like', '%' . $params['name'] . '%'];
            }
            
            if (isset($params['status'])) {
                $where[] = ['status', '=', intval($params['status'])];
            }
            
            $page = isset($params['page']) ? intval($params['page']) : 1;
            $pageSize = isset($params['page_size']) ? intval($params['page_size']) : 20;
            
            $query = Level::where($where);
            $total = $query->count();
            
            $list = $query->order('level', 'asc')
                ->page($page, $pageSize)
                ->select()
                ->toArray();
            
            return [
                'code' => 200,
                'msg' => '获取成功',
                'data' => [
                    'list' => $list,
                    'pagination' => [
                        'total' => $total,
                        'current' => $page,
                        'page_size' => $pageSize,
                        'pages' => ceil($total / $pageSize)
                    ]
                ]
            ];
        } catch (\Exception $e) {
            return [
                'code' => 500,
                'msg' => '获取等级列表失败：' . $e->getMessage()
            ];
        }
    }

    /**
     * 新增等级
     */
    public static function addLevel($params)
    {
        try {
            $type = $params['type'] ?? Level::TYPE_USER;
            
            // 验证类型
            if (!Level::isValidType($type)) {
                return ['code' => 0, 'msg' => '无效的等级类型'];
            }
            
            // 检查等级是否已存在
            $exists = Level::where('type', $type)
                ->where('level', $params['level'])
                ->find();
            
            if ($exists) {
                $typeName = Level::getAllTypes()[$type] ?? $type;
                return ['code' => 0, 'msg' => "{$typeName}的等级 {$params['level']} 已存在（{$exists->name}），请使用其他等级数字或编辑现有等级"];
            }
            
            // 新增等级
            $level = new Level();
            $level->type = $type;
            $level->name = $params['name'];
            $level->level = $params['level'];
            $level->min_experience = $params['min_experience'];
            $level->max_experience = $params['max_experience'] ?? 0;
            $level->description = $params['description'] ?? '';
            $level->status = $params['status'] ?? 1;
            
            if (!$level->save()) {
                return ['code' => 0, 'msg' => '添加等级失败'];
            }
            
            return ['code' => 200, 'msg' => '添加等级成功', 'data' => $level->toArray()];
        } catch (\Exception $e) {
            return ['code' => 500, 'msg' => '添加等级失败：' . $e->getMessage()];
        }
    }

    /**
     * 编辑等级
     */
    public static function updateLevel($id, $params)
    {
        try {
            $level = Level::find($id);
            if (!$level) {
                return ['code' => 0, 'msg' => '等级不存在'];
            }
            
            $level->name = $params['name'] ?? $level->name;
            $level->min_experience = $params['min_experience'] ?? $level->min_experience;
            $level->max_experience = $params['max_experience'] ?? $level->max_experience;
            $level->description = $params['description'] ?? $level->description;
            $level->status = isset($params['status']) ? $params['status'] : $level->status;
            
            if (!$level->save()) {
                return ['code' => 0, 'msg' => '更新等级失败'];
            }
            
            return ['code' => 200, 'msg' => '更新等级成功', 'data' => $level->toArray()];
        } catch (\Exception $e) {
            return ['code' => 500, 'msg' => '更新等级失败：' . $e->getMessage()];
        }
    }

    /**
     * 删除等级（物理删除）
     * @param int $id 等级ID
     */
    public static function deleteLevel($id)
    {
        try {
            $level = Level::find($id);
            if (!$level) {
                return ['code' => 0, 'msg' => '等级不存在'];
            }
            
            // 检查是否有用户在使用该等级
            $usageCount = LevelRecord::where('target_type', $level->type)
                ->where('current_level', $level->level)
                ->count();
            
            if ($usageCount > 0) {
                return ['code' => 0, 'msg' => "有 {$usageCount} 个用户正在使用该等级，无法删除"];
            }
            
            // 物理删除
            if (!$level->force()->delete()) {
                return ['code' => 0, 'msg' => '删除等级失败'];
            }
            
            LogService::log("删除等级：ID {$id}，类型 {$level->type}，等级 {$level->level}");
            
            return ['code' => 200, 'msg' => '删除等级成功'];
        } catch (\Exception $e) {
            return ['code' => 500, 'msg' => '删除等级失败：' . $e->getMessage()];
        }
    }

    /**
     * 根据经验值获取等级
     */
    public static function getLevelByExperience($type, $experience)
    {
        try {
            $level = Level::getLevelByExperience($type, $experience);
            
            if (!$level) {
                // 返回该类型的第一级
                $level = Level::where('type', $type)->where('level', 1)->find();
            }
            
            return $level ? $level->toArray() : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 获取下一等级所需经验
     */
    public static function getNextLevelExperience($type, $currentLevel)
    {
        try {
            $nextLevel = Level::getNextLevel($type, $currentLevel);
            return $nextLevel ? $nextLevel->min_experience : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 初始化用户等级记录（所有类型）
     */
    public static function initializeUserLevels($userId)
    {
        try {
            $types = [Level::TYPE_USER, Level::TYPE_WRITER, Level::TYPE_READER, Level::TYPE_INTERACTION];
            
            foreach ($types as $type) {
                // 检查是否已存在
                $exists = LevelRecord::where('target_type', $type)
                    ->where('target_id', $userId)
                    ->find();
                
                if (!$exists) {
                    LevelRecord::createRecord($type, $userId);
                }
            }
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 更新等级状态
     */
    public static function setLevelStatus($id, $status)
    {
        try {
            $level = Level::find($id);
            if (!$level) {
                return ['code' => 0, 'msg' => '等级不存在'];
            }

            $level->status = $status;
            if (!$level->save()) {
                return ['code' => 0, 'msg' => '更新状态失败'];
            }

            LogService::log("更新等级状态：等级ID {$id}，类型：{$level->type}，状态：" . ($status ? '启用' : '禁用'));

            return ['code' => 200, 'msg' => '更新状态成功', 'data' => $level->toArray()];
        } catch (\Exception $e) {
            return ['code' => 500, 'msg' => '更新状态失败：' . $e->getMessage()];
        }
    }

    /**
     * 获取等级详情
     */
    public static function getLevelDetail($id)
    {
        try {
            $level = Level::find($id);
            if (!$level) {
                return ['code' => 0, 'msg' => '等级不存在'];
            }

            // 统计使用该等级的用户数量
            $userCount = LevelRecord::where('target_type', $level->type)
                ->where('current_level', $level->level)
                ->count();

            return [
                'code' => 200,
                'msg' => '获取成功',
                'data' => [
                    'level' => $level->toArray(),
                    'user_count' => $userCount
                ]
            ];
        } catch (\Exception $e) {
            return ['code' => 500, 'msg' => '获取等级详情失败：' . $e->getMessage()];
        }
    }

    /**
     * 获取所有等级类型
     */
    public static function getAllTypes()
    {
        return [
            'code' => 200,
            'msg' => '获取成功',
            'data' => Level::getAllTypes()
        ];
    }
    
    /**
     * 批量删除等级
     * @param array $ids 等级ID数组
     */
    public static function batchDeleteLevels($ids)
    {
        try {
            if (empty($ids) || !is_array($ids)) {
                return ['code' => 0, 'msg' => '请选择要删除的等级'];
            }
            
            $successCount = 0;
            $failedCount = 0;
            $errors = [];
            
            foreach ($ids as $id) {
                $result = self::deleteLevel($id);
                if ($result['code'] === 200) {
                    $successCount++;
                } else {
                    $failedCount++;
                    $errors[] = "ID {$id}: {$result['msg']}";
                }
            }
            
            if ($failedCount === 0) {
                return ['code' => 200, 'msg' => "成功删除 {$successCount} 个等级"];
            } else if ($successCount === 0) {
                return ['code' => 0, 'msg' => '批量删除失败', 'errors' => $errors];
            } else {
                return [
                    'code' => 200, 
                    'msg' => "成功删除 {$successCount} 个，失败 {$failedCount} 个",
                    'errors' => $errors
                ];
            }
        } catch (\Exception $e) {
            return ['code' => 500, 'msg' => '批量删除失败：' . $e->getMessage()];
        }
    }
    
    /**
     * 批量更新等级状态
     * @param array $ids 等级ID数组
     * @param int $status 状态（0=禁用，1=启用）
     */
    public static function batchUpdateStatus($ids, $status)
    {
        try {
            if (empty($ids) || !is_array($ids)) {
                return ['code' => 0, 'msg' => '请选择要更新的等级'];
            }
            
            $status = intval($status);
            $count = Level::whereIn('id', $ids)->update(['status' => $status]);
            
            $statusText = $status === 1 ? '启用' : '禁用';
            LogService::log("批量{$statusText}等级：IDs " . implode(',', $ids));
            
            return ['code' => 200, 'msg' => "成功{$statusText} {$count} 个等级"];
        } catch (\Exception $e) {
            return ['code' => 500, 'msg' => '批量更新状态失败：' . $e->getMessage()];
        }
    }
}
