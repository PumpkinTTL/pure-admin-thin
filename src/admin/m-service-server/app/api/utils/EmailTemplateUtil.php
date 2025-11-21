<?php

namespace app\api\utils;

/**
 * 邮件模板工具类
 * 用于处理邮件模板的占位符替换等操作
 */
class EmailTemplateUtil
{
    /**
     * 替换模板中的占位符
     * @param string $template 模板内容（支持主题或内容）
     * @param array $variables 变量数组 ['key' => 'value']
     * @return string 替换后的内容
     */
    public static function replacePlaceholders(string $template, array $variables): string
    {
        // 添加默认变量
        $variables['year'] = $variables['year'] ?? date('Y');
        
        // 替换单花括号占位符 {key}
        foreach ($variables as $key => $value) {
            // 确保 value 是字符串
            $value = is_array($value) ? json_encode($value) : (string)$value;
            $template = str_replace('{' . $key . '}', $value, $template);
        }
        
        return $template;
    }
    
    /**
     * 批量替换主题和内容
     * @param string $subject 邮件主题
     * @param string $content 邮件内容
     * @param array $variables 变量数组
     * @return array ['subject' => string, 'content' => string]
     */
    public static function renderTemplate(string $subject, string $content, array $variables): array
    {
        return [
            'subject' => self::replacePlaceholders($subject, $variables),
            'content' => self::replacePlaceholders($content, $variables)
        ];
    }
    
    /**
     * 验证模板变量是否完整
     * @param string $template 模板内容
     * @param array $requiredVars 必填变量列表
     * @return array ['valid' => bool, 'missing' => array]
     */
    public static function validateVariables(string $template, array $requiredVars): array
    {
        $missing = [];
        
        foreach ($requiredVars as $var) {
            if (strpos($template, '{' . $var . '}') !== false) {
                // 模板中包含该变量，检查是否提供了值
                continue;
            }
        }
        
        return [
            'valid' => empty($missing),
            'missing' => $missing
        ];
    }
    
    /**
     * 提取模板中的所有占位符
     * @param string $template 模板内容
     * @return array 占位符列表（不含花括号）
     */
    public static function extractPlaceholders(string $template): array
    {
        $placeholders = [];
        
        // 匹配 {xxx} 格式的占位符
        preg_match_all('/\{([a-z_]+)\}/', $template, $matches);
        
        if (!empty($matches[1])) {
            $placeholders = array_unique($matches[1]);
        }
        
        return $placeholders;
    }
    
    /**
     * 预览模板（使用示例数据）
     * @param string $subject 邮件主题
     * @param string $content 邮件内容
     * @param array $sampleData 示例数据
     * @return array ['subject' => string, 'content' => string]
     */
    public static function preview(string $subject, string $content, array $sampleData = []): array
    {
        // 默认示例数据
        $defaults = [
            'username' => '张三',
            'title' => '系统通知标题',
            'content' => '这是通知内容示例',
            'reset_url' => 'https://example.com/reset?token=abc123',
            'code' => '123456',
            'expire_minutes' => '10',
            'publish_time' => date('Y-m-d H:i:s'),
            'year' => date('Y')
        ];
        
        // 合并用户提供的示例数据
        $data = array_merge($defaults, $sampleData);
        
        return self::renderTemplate($subject, $content, $data);
    }
}
