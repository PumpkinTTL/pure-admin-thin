<?php
require __DIR__ . '/vendor/autoload.php';

use think\facade\Db;

// 初始化框架
$app = new think\App();
$app->initialize();

echo "=== 检查 card_keys 表结构 ===\n\n";

try {
    $fields = Db::query('DESCRIBE bl_card_keys');
    echo "当前表字段:\n";
    foreach ($fields as $field) {
        echo sprintf("%-25s %-15s %s\n", $field['Field'], $field['Type'], $field['Key']);
    }
} catch (\Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
}

echo "\n=== 检查完毕 ===\n";

