<?php
// 测试批量生成接口
namespace think;

require __DIR__ . '/vendor/autoload.php';

use app\api\model\CardKey;
use app\api\model\CardType;
use utils\CardKeyUtil;

// 初始化应用
$app = new App();
$app->initialize();

echo "=== 测试批量生成 ===\n\n";

// 测试1: 检查表结构
try {
    $fields = \think\facade\Db::query('DESCRIBE card_keys');
    echo "表结构:\n";
    foreach ($fields as $field) {
        echo "  - {$field['Field']} ({$field['Type']})\n";
    }
    echo "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n\n";
}

// 测试2: 调用CardKeyUtil生成
try {
    echo "调用 CardKeyUtil::batchGenerate(2, 1)...\n";
    $result = CardKeyUtil::batchGenerate(2, 1, []);
    echo "Result:\n";
    print_r($result);
    echo "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n\n";
}

// 测试3: 检查是否有code字段的查询
try {
    echo "测试 where('card_key', 'XXX')...\n";
    $exists = CardKey::where('card_key', 'TEST-CODE')->count();
    echo "OK, count: $exists\n\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n\n";
}

echo "=== 测试完成 ===\n";

