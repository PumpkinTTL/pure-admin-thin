<?php

namespace app\api\services;

use app\api\model\ExperienceLog;
use app\api\model\LevelRecord;
use app\api\model\Level;
use app\api\model\users;
use think\facade\Db;

class ExperienceService
{
    /**
     * 经验来源配置
     */
    const EXPERIENCE_RULES = [
        // 用户等级经验来源
        'daily_login' => ['amount' => 2, 'name' => '每日登录', 'description' => '每日登录一次', 'types' => ['user', 'interaction']],
        'complete_profile' => ['amount' => 50, 'name' => '完善资料', 'description' => '完善个人资料', 'types' => ['user']],
        
        // 写作等级经验来源
        'article_publish' => ['amount' => 50, 'name' => '发布文章', 'description' => '每发布一篇文章', 'types' => ['writer', 'user']],
        'article_quality' => ['amount' => 100, 'name' => '优质文章', 'description' => '发布优质文章额外奖励', 'types' => ['writer']],
        
        // 读者等级经验来源
        'article_read' => ['amount' => 1, 'name' => '阅读文章', 'description' => '每阅读一篇文章', 'types' => ['reader', 'user']],
        'reading_time' => ['amount' => 5, 'name' => '阅读时长', 'description' => '每阅读10分钟', 'types' => ['reader']],
        
        // 互动等级经验来源
        'comment_publish' => ['amount' => 10, 'name' => '发布评论', 'description' => '每发布一条评论', 'types' => ['interaction', 'user']],
        'comment_like' => ['amount' => 3, 'name' => '评论获赞', 'description' => '评论被点赞一次', 'types' => ['interaction']],
        'article_like' => ['amount' => 5, 'name' => '点赞文章', 'description' => '给文章点赞', 'types' => ['interaction']],
        'share' => ['amount' => 8, 'name' => '分享内容', 'description' => '分享文章或内容', 'types' => ['interaction', 'user']],
        
        // 通用
        'manual' => ['amount' => 0, 'name' => '手动添加', 'description' => '管理员手动添加', 'types' => ['user', 'writer', 'reader', 'interaction']],
    ];

    /**
     * 添加经验（核心方法）
     * @param string $targetType 目标类型（user/writer/reader/interaction）
     * @param int $targetId 目标ID（通常是用户ID）
     * @param int $amount 经验值
     * @param string $sourceType 来源类型
     * @param int|null $sourceId 来源ID
     * @param string $description 描述
     */
    public static function addExperience($targetType, $targetId, $amount, $sourceType, $sourceId = null, $description = '')
    {
        try {
            // 验证类型
            if (!Level::isValidType($targetType)) {
                return ['code' => 0, 'msg' => '无效的等级类型'];
            }
            
            Db::startTrans();
            
            // 获取等级记录
            $levelRecord = LevelRecord::getRecord($targetType, $targetId);
            if (!$levelRecord) {
                // 如果不存在，创建一个
                $levelRecord = LevelRecord::createRecord($targetType, $targetId);
            }
            
            // 记录升级前的等级
            $levelBefore = $levelRecord->current_level;
            
            // 添加经验
            $levelRecord->total_experience += $amount;
            
            // 防止总经验值为负数
            if ($levelRecord->total_experience < 0) {
                $levelRecord->total_experience = 0;
            }
            
            // 检查是否升级或降级
            $isLevelUp = 0;
            $isLevelDown = 0;
            $levelAfter = $levelBefore;
            
            // 根据新的总经验值查询应该是哪个等级
            $newLevelInfo = Level::getLevelByExperience($targetType, $levelRecord->total_experience);
            
            if ($newLevelInfo) {
                if ($newLevelInfo->level > $levelRecord->current_level) {
                    // 升级
                    $levelAfter = $newLevelInfo->level;
                    $levelRecord->current_level = $levelAfter;
                    $levelRecord->level_up_count += ($levelAfter - $levelBefore);
                    $levelRecord->last_level_up_time = date('Y-m-d H:i:s');
                    $isLevelUp = 1;
                } elseif ($newLevelInfo->level < $levelRecord->current_level) {
                    // 降级
                    $levelAfter = $newLevelInfo->level;
                    $levelRecord->current_level = $levelAfter;
                    $isLevelDown = 1;
                }
            }
            
            // 计算当前等级内的经验值
            if ($newLevelInfo) {
                $levelRecord->experience_in_level = $levelRecord->total_experience - $newLevelInfo->min_experience;
            }
            
            // 保存等级记录
            if (!$levelRecord->save()) {
                Db::rollback();
                return ['code' => 0, 'msg' => '更新等级失败'];
            }
            
            // 如果是用户等级，更新用户表的level字段（冗余）
            if ($targetType === Level::TYPE_USER) {
                users::where('id', '=', $targetId)->update(['level' => $levelRecord->current_level]);
            }
            
            // 记录经验日志
            ExperienceLog::addLog(
                $targetType,
                $targetId,
                $amount,
                $sourceType,
                $sourceId,
                $description,
                $levelBefore,
                $levelAfter,
                $isLevelUp,
                $isLevelDown
            );
            
            Db::commit();
            
            return [
                'code' => 200,
                'msg' => '添加经验成功',
                'data' => [
                    'is_level_up' => $isLevelUp,
                    'is_level_down' => $isLevelDown,
                    'level_before' => $levelBefore,
                    'level_after' => $levelAfter,
                    'experience' => $amount,
                    'current_level' => $levelRecord->current_level,
                    'total_experience' => $levelRecord->total_experience
                ]
            ];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => 500, 'msg' => '添加经验失败：' . $e->getMessage()];
        }
    }

    /**
     * 获取目标的等级信息
     */
    public static function getLevelInfo($targetType, $targetId)
    {
        try {
            // 验证类型
            if (!Level::isValidType($targetType)) {
                return ['code' => 0, 'msg' => '无效的等级类型'];
            }
            
            $levelRecord = LevelRecord::getRecord($targetType, $targetId);
            if (!$levelRecord) {
                return ['code' => 0, 'msg' => '等级记录不存在'];
            }
            
            // 获取当前等级信息
            $currentLevelInfo = Level::where('type', $targetType)
                ->where('level', $levelRecord->current_level)
                ->find();
            
            // 获取下一等级信息
            $nextLevelInfo = Level::getNextLevel($targetType, $levelRecord->current_level);
            
            // 计算进度
            $experienceProgress = 100;
            if ($currentLevelInfo && $nextLevelInfo) {
                $currentMin = $currentLevelInfo->min_experience;
                $nextMin = $nextLevelInfo->min_experience;
                $needExp = $nextMin - $currentMin;
                if ($needExp > 0) {
                    $experienceProgress = round(($levelRecord->experience_in_level / $needExp) * 100, 2);
                }
            }
            
            return [
                'code' => 200,
                'msg' => '获取成功',
                'data' => [
                    'target_type' => $targetType,
                    'target_id' => $targetId,
                    'current_level' => $levelRecord->current_level,
                    'level_name' => $currentLevelInfo ? $currentLevelInfo->name : '',
                    'level_icon' => $currentLevelInfo ? $currentLevelInfo->icon : '',
                    'level_color' => $currentLevelInfo ? $currentLevelInfo->color : '',
                    'total_experience' => $levelRecord->total_experience,
                    'experience_in_level' => $levelRecord->experience_in_level,
                    'level_up_count' => $levelRecord->level_up_count,
                    'last_level_up_time' => $levelRecord->last_level_up_time,
                    'next_level' => $nextLevelInfo ? $nextLevelInfo->level : null,
                    'next_level_name' => $nextLevelInfo ? $nextLevelInfo->name : '已满级',
                    'next_level_experience' => $nextLevelInfo ? $nextLevelInfo->min_experience : null,
                    'experience_progress' => $experienceProgress
                ]
            ];
        } catch (\Exception $e) {
            return ['code' => 500, 'msg' => '获取等级信息失败：' . $e->getMessage()];
        }
    }

    /**
     * 获取经验日志
     */
    public static function getExperienceLogs($targetType, $targetId, $page = 1, $pageSize = 20)
    {
        try {
            // 验证类型
            if (!Level::isValidType($targetType)) {
                return ['code' => 0, 'msg' => '无效的等级类型'];
            }
            
            $result = ExperienceLog::getLogs($targetType, $targetId, $page, $pageSize);
            
            return [
                'code' => 200,
                'msg' => '获取成功',
                'data' => [
                    'list' => $result['list'],
                    'pagination' => [
                        'total' => $result['total'],
                        'current' => $page,
                        'page_size' => $pageSize,
                        'pages' => ceil($result['total'] / $pageSize)
                    ]
                ]
            ];
        } catch (\Exception $e) {
            return ['code' => 500, 'msg' => '获取经验日志失败：' . $e->getMessage()];
        }
    }

    /**
     * 撤销经验记录
     */
    public static function revokeExperience($logId)
    {
        try {
            Db::startTrans();

            // 获取经验日志
            $log = ExperienceLog::find($logId);
            if (!$log) {
                Db::rollback();
                return ['code' => 0, 'msg' => '经验记录不存在'];
            }

            if ($log->status == 0) {
                Db::rollback();
                return ['code' => 0, 'msg' => '该经验记录已被撤销'];
            }

            // 获取等级记录
            $levelRecord = LevelRecord::getRecord($log->target_type, $log->target_id);
            if (!$levelRecord) {
                Db::rollback();
                return ['code' => 0, 'msg' => '等级记录不存在'];
            }

            // 减少经验值
            $levelRecord->total_experience -= $log->experience_amount;
            if ($levelRecord->total_experience < 0) {
                $levelRecord->total_experience = 0;
            }

            // 重新计算等级
            $newLevelInfo = Level::getLevelByExperience($log->target_type, $levelRecord->total_experience);
            if ($newLevelInfo) {
                $levelRecord->current_level = $newLevelInfo->level;
                $levelRecord->experience_in_level = $levelRecord->total_experience - $newLevelInfo->min_experience;
            } else {
                $levelRecord->current_level = 1;
                $levelRecord->experience_in_level = 0;
            }

            if (!$levelRecord->save()) {
                Db::rollback();
                return ['code' => 0, 'msg' => '更新等级记录失败'];
            }

            // 如果是用户等级，更新用户表
            if ($log->target_type === Level::TYPE_USER) {
                users::where('id', '=', $log->target_id)->update(['level' => $levelRecord->current_level]);
            }

            // 更新经验日志状态
            ExperienceLog::revokeLog($logId);

            Db::commit();

            LogService::log("撤销经验记录：类型 {$log->target_type}，目标ID {$log->target_id}，经验值 {$log->experience_amount}");

            return [
                'code' => 200,
                'msg' => '撤销成功',
                'data' => [
                    'current_level' => $levelRecord->current_level,
                    'total_experience' => $levelRecord->total_experience
                ]
            ];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => 500, 'msg' => '撤销经验失败：' . $e->getMessage()];
        }
    }

    /**
     * 获取经验来源统计
     */
    public static function getExperienceSourceStats($targetType, $targetId)
    {
        try {
            // 验证类型
            if (!Level::isValidType($targetType)) {
                return ['code' => 0, 'msg' => '无效的等级类型'];
            }
            
            $stats = ExperienceLog::getSourceStats($targetType, $targetId);

            return [
                'code' => 200,
                'msg' => '获取成功',
                'data' => $stats
            ];
        } catch (\Exception $e) {
            return ['code' => 500, 'msg' => '获取经验来源统计失败：' . $e->getMessage()];
        }
    }

    /**
     * 获取经验来源配置
     */
    public static function getExperienceSources($targetType = null)
    {
        $sources = [];
        foreach (self::EXPERIENCE_RULES as $type => $rule) {
            // 如果指定了类型，只返回该类型支持的来源
            if ($targetType && !in_array($targetType, $rule['types'])) {
                continue;
            }
            
            $sources[] = [
                'type' => $type,
                'name' => $rule['name'],
                'experience' => $rule['amount'],
                'description' => $rule['description'],
                'support_types' => $rule['types']
            ];
        }
        
        return [
            'code' => 200,
            'msg' => '获取成功',
            'data' => $sources
        ];
    }

    /**
     * 获取用户所有类型的等级信息
     */
    public static function getUserAllLevels($userId)
    {
        try {
            $types = [Level::TYPE_USER, Level::TYPE_WRITER, Level::TYPE_READER, Level::TYPE_INTERACTION];
            $result = [];
            
            foreach ($types as $type) {
                $info = self::getLevelInfo($type, $userId);
                if ($info['code'] == 200) {
                    $result[$type] = $info['data'];
                }
            }
            
            return [
                'code' => 200,
                'msg' => '获取成功',
                'data' => $result
            ];
        } catch (\Exception $e) {
            return ['code' => 500, 'msg' => '获取用户等级信息失败：' . $e->getMessage()];
        }
    }

    /**
     * 获取等级记录列表（联查用户信息）
     * @param array $params 查询参数，包含 target_type（必需）、page、page_size
     */
    public static function getLevelRecords($params = [])
    {
        try {
            $targetType = $params['target_type'] ?? Level::TYPE_USER;
            
            // 验证类型
            if (!Level::isValidType($targetType)) {
                return ['code' => 0, 'msg' => '无效的等级类型'];
            }
            
            $page = isset($params['page']) ? intval($params['page']) : 1;
            $pageSize = isset($params['page_size']) ? intval($params['page_size']) : 20;
            
            // 联查用户信息
            $query = Db::table('bl_level_records')
                ->alias('lr')
                ->leftJoin('users u', 'lr.target_id = u.id')
                ->where('lr.target_type', $targetType)
                ->field('lr.*, u.id as user_id, u.username, u.email, u.avatar');
            
            $total = $query->count();
            
            $list = $query->order('lr.total_experience', 'desc')
                ->page($page, $pageSize)
                ->select()
                ->toArray();
            
            // 补充等级名称信息
            foreach ($list as &$record) {
                $level = Level::where('type', $targetType)
                    ->where('level', $record['current_level'])
                    ->find();
                if ($level) {
                    $record['level_name'] = $level->name;
                    $record['level_icon'] = $level->icon;
                    $record['level_color'] = $level->color;
                }
                // 确保 id 字段是用户ID（用于前端选择）
                $record['id'] = $record['user_id'];
            }
            
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
                'msg' => '获取等级记录列表失败：' . $e->getMessage()
            ];
        }
    }
}
