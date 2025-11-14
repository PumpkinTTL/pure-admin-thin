<?php

namespace app\api\model;

use think\Model;
use think\model\concern\SoftDelete;

class Level extends Model
{
    use SoftDelete;

    protected $table = 'bl_levels';
    protected $pk = 'id';
    protected $deleteTime = 'delete_time';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 类型转换
    protected $type = [
        'level' => 'integer',
        'min_experience' => 'integer',
        'max_experience' => 'integer',
        'status' => 'integer',
    ];

    // 等级类型常量
    const TYPE_USER = 'user';           // 用户等级
    const TYPE_WRITER = 'writer';       // 写作等级
    const TYPE_READER = 'reader';       // 读者等级
    const TYPE_INTERACTION = 'interaction'; // 互动等级

    /**
     * 关联等级记录
     * 一个等级配置可以对应多个记录
     */
    public function levelRecords(): \think\model\relation\HasMany
    {
        return $this->hasMany(LevelRecord::class, 'current_level', 'level')
            ->where('target_type', $this->getData('type'));
    }

    /**
     * 根据经验值获取对应的等级
     * @param string $type 等级类型
     * @param int $experience 经验值
     * @return Level|null
     */
    public static function getLevelByExperience(string $type, int $experience)
    {
        return self::where('type', $type)
            ->where('status', 1)
            ->where('min_experience', '<=', $experience)
            ->where(function ($query) use ($experience) {
                $query->where('max_experience', '>=', $experience)
                    ->whereOr('max_experience', 0); // 0表示无上限
            })
            ->order('level', 'asc')
            ->find();
    }

    /**
     * 获取下一个等级
     * @param string $type 等级类型
     * @param int $currentLevel 当前等级
     * @return Level|null
     */
    public static function getNextLevel(string $type, int $currentLevel)
    {
        return self::where('type', $type)
            ->where('status', 1)
            ->where('level', '>', $currentLevel)
            ->order('level', 'asc')
            ->find();
    }

    /**
     * 获取所有启用的等级列表（按等级升序）
     * @param string $type 等级类型
     * @return array
     */
    public static function getEnabledLevels(string $type): array
    {
        return self::where('type', $type)
            ->where('status', 1)
            ->order('level', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * 获取所有等级类型
     * @return array
     */
    public static function getAllTypes(): array
    {
        return [
            self::TYPE_USER => '用户等级',
            self::TYPE_WRITER => '写作等级',
            self::TYPE_READER => '读者等级',
            self::TYPE_INTERACTION => '互动等级',
        ];
    }

    /**
     * 验证等级类型是否有效
     * @param string $type
     * @return bool
     */
    public static function isValidType(string $type): bool
    {
        return in_array($type, [
            self::TYPE_USER,
            self::TYPE_WRITER,
            self::TYPE_READER,
            self::TYPE_INTERACTION
        ]);
    }
}
