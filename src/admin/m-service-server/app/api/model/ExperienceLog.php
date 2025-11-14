<?php

namespace app\api\model;

use think\Model;

class ExperienceLog extends Model
{
    protected $table = 'bl_experience_logs';
    protected $pk = 'id';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = false; // 经验日志不需要更新时间

    // 类型转换
    protected $type = [
        'target_id' => 'integer',
        'experience_amount' => 'integer',
        'source_id' => 'integer',
        'level_before' => 'integer',
        'level_after' => 'integer',
        'is_level_up' => 'integer',
        'is_level_down' => 'integer',
        'status' => 'integer',
    ];

    /**
     * 关联用户（target通常是用户）
     */
    public function user(): \think\model\relation\BelongsTo
    {
        return $this->belongsTo(users::class, 'target_id', 'id');
    }

    /**
     * 获取目标的经验日志
     * @param string $targetType 目标类型
     * @param int $targetId 目标ID（0 表示加载所有用户）
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @return array
     */
    public static function getLogs(string $targetType, int $targetId, int $page = 1, int $pageSize = 20): array
    {
        $query = self::alias('log')
            ->leftJoin('users u', 'log.target_id = u.id')
            ->field('log.*, u.username, u.nickname, u.avatar')
            ->where('log.target_type', $targetType);
        
        // 如果 target_id 不为 0，则筛选指定用户
        if ($targetId > 0) {
            $query->where('log.target_id', $targetId);
        }
        
        $result = $query->order('log.create_time', 'desc')
            ->paginate([
                'list_rows' => $pageSize,
                'page' => $page
            ]);

        return [
            'list' => $result->items(),
            'total' => $result->total(),
            'page' => $page,
            'page_size' => $pageSize
        ];
    }

    /**
     * 记录经验变化
     * @param string $targetType 目标类型
     * @param int $targetId 目标ID
     * @param int $experienceAmount 经验值变化
     * @param string $sourceType 来源类型
     * @param int|null $sourceId 来源ID
     * @param string|null $description 描述
     * @param int $levelBefore 变化前等级
     * @param int $levelAfter 变化后等级
     * @param int $isLevelUp 是否升级
     * @param int $isLevelDown 是否降级
     * @return ExperienceLog
     */
    public static function addLog(
        string $targetType,
        int $targetId,
        int $experienceAmount,
        string $sourceType,
        ?int $sourceId = null,
        ?string $description = null,
        int $levelBefore = 1,
        int $levelAfter = 1,
        int $isLevelUp = 0,
        int $isLevelDown = 0
    ): ExperienceLog {
        $log = new self();
        $log->target_type = $targetType;
        $log->target_id = $targetId;
        $log->experience_amount = $experienceAmount;
        $log->source_type = $sourceType;
        $log->source_id = $sourceId;
        $log->description = $description;
        $log->level_before = $levelBefore;
        $log->level_after = $levelAfter;
        $log->is_level_up = $isLevelUp;
        $log->is_level_down = $isLevelDown;
        $log->status = 1;
        $log->save();

        return $log;
    }

    /**
     * 撤销经验日志
     * @param int $logId 日志ID
     * @return bool
     */
    public static function revokeLog(int $logId): bool
    {
        return self::where('id', $logId)->update(['status' => 0]) !== false;
    }

    /**
     * 获取经验来源统计
     * @param string $targetType 目标类型
     * @param int $targetId 目标ID
     * @return array
     */
    public static function getSourceStats(string $targetType, int $targetId): array
    {
        return self::where('target_type', $targetType)
            ->where('target_id', $targetId)
            ->where('status', 1)
            ->field('source_type, COUNT(*) as count, SUM(experience_amount) as total_exp')
            ->group('source_type')
            ->select()
            ->toArray();
    }
}
