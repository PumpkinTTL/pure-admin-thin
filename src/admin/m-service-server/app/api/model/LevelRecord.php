<?php

namespace app\api\model;

use think\Model;

class LevelRecord extends Model
{
    protected $table = 'bl_level_records';
    protected $pk = 'id';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 类型转换
    protected $type = [
        'target_id' => 'integer',
        'current_level' => 'integer',
        'total_experience' => 'integer',
        'experience_in_level' => 'integer',
        'level_up_count' => 'integer',
    ];

    /**
     * 关联用户（target通常是用户）
     */
    public function user(): \think\model\relation\BelongsTo
    {
        return $this->belongsTo(users::class, 'target_id', 'id');
    }

    /**
     * 关联等级配置
     */
    public function level(): \think\model\relation\BelongsTo
    {
        return $this->belongsTo(Level::class, 'current_level', 'level')
            ->where('type', $this->getData('target_type'));
    }

    /**
     * 获取目标的等级记录
     * @param string $targetType 目标类型
     * @param int $targetId 目标ID（通常是用户ID）
     * @return LevelRecord|null
     */
    public static function getRecord(string $targetType, int $targetId)
    {
        return self::where('target_type', $targetType)
            ->where('target_id', $targetId)
            ->find();
    }

    /**
     * 创建或初始化等级记录
     * @param string $targetType 目标类型
     * @param int $targetId 目标ID
     * @return LevelRecord
     */
    public static function createRecord(string $targetType, int $targetId): LevelRecord
    {
        $record = new self();
        $record->target_type = $targetType;
        $record->target_id = $targetId;
        $record->current_level = 1;
        $record->total_experience = 0;
        $record->experience_in_level = 0;
        $record->level_up_count = 0;
        $record->save();
        
        return $record;
    }

    /**
     * 获取用户的所有等级记录
     * @param int $userId 用户ID
     * @return array
     */
    public static function getUserAllRecords(int $userId): array
    {
        return self::where('target_id', $userId)
            ->select()
            ->toArray();
    }

    /**
     * 批量获取用户的等级信息（带等级配置详情）
     * @param int $userId 用户ID
     * @return array
     */
    public static function getUserLevelsWithDetail(int $userId): array
    {
        $records = self::where('target_id', $userId)
            ->select()
            ->toArray();
        
        $result = [];
        foreach ($records as $record) {
            $levelInfo = Level::where('type', $record['target_type'])
                ->where('level', $record['current_level'])
                ->find();
            
            $result[] = array_merge($record, [
                'level_info' => $levelInfo ? $levelInfo->toArray() : null
            ]);
        }
        
        return $result;
    }
}
