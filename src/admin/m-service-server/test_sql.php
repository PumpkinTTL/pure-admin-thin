<?php
require __DIR__ . '/vendor/autoload.php';

use think\facade\Db;
use app\api\model\CardKey;

$app = new think\App();
$app->initialize();

// 开启SQL日志
Db::listen(function($sql, $time, $explain) {
    echo "[SQL] {$sql}\n";
    echo "[TIME] {$time}ms\n\n";
});

echo "=== 测试SQL ===\n\n";

try {
    echo "1. 测试 CardKey::where('card_key', 'TEST')->count()\n";
    $count = CardKey::where('card_key', 'TEST')->count();
    echo "结果: {$count}\n\n";
} catch (\Exception $e) {
    echo "错误: " . $e->getMessage() . "\n\n";
}

try {
    echo "2. 测试 CardKey 模型信息\n";
    $model = new CardKey();
    echo "表名: " . $model->getTable() . "\n";
    echo "主键: " . $model->getPk() . "\n\n";
} catch (\Exception $e) {
    echo "错误: " . $e->getMessage() . "\n\n";
}

echo "===  完成 ===\n";

