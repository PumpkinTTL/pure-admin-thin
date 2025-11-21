<?php

namespace app\api\model;

use think\Model;
use app\api\utils\EmailTemplateUtil;

class EmailTemplate extends Model
{
    protected $table = 'bl_email_templates';
    protected $pk = 'id';
    
    // 开启软删除
    use \think\model\concern\SoftDelete;
    protected $deleteTime = 'delete_time';
    
    // 类型转换
    protected $type = [
        'id' => 'integer',
        'type' => 'integer',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'created_by' => 'integer',
    ];
    
    // JSON字段
    protected $json = ['variables'];
    
    // 模板类型常量
    const TYPE_PASSWORD_RESET = 1;     // 密码重置
    const TYPE_NOTICE = 2;             // 公告通知
    const TYPE_MARKETING = 3;          // 营销推广
    const TYPE_HOLIDAY = 4;            // 节庆祝福
    
    /**
     * 根据模板标识获取模板
     * @param string $code 模板标识
     * @return EmailTemplate|null
     */
    public static function getByCode(string $code)
    {
        return self::where('code', $code)
            ->where('is_active', 1)
            ->find();
    }
    
    /**
     * 渲染模板（替换变量）
     * @param array $data 变量数据
     * @return array ['subject' => string, 'content' => string]
     */
    public function render(array $data): array
    {
        return EmailTemplateUtil::renderTemplate($this->subject, $this->content, $data);
    }
    
    /**
     * 获取类型名称
     * @return string
     */
    public function getTypeNameAttr(): string
    {
        $types = [
            self::TYPE_PASSWORD_RESET => '密码重置',
            self::TYPE_NOTICE => '公告通知',
            self::TYPE_MARKETING => '营销推广',
            self::TYPE_HOLIDAY => '节庆祝福',
        ];
        
        return $types[$this->type] ?? '未知';
    }
    
    /**
     * 关联创建人
     */
    public function creator()
    {
        return $this->belongsTo(users::class, 'created_by');
    }
}
