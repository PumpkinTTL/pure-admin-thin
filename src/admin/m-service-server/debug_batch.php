<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/vendor/autoload.php';

use think\facade\Db;
use app\api\model\CardKey;
use app\api\model\CardType;

$app = new think\App();
$app->initialize();

// 开启SQL日志
Db::listen(function($sql, $time, $explain) {
    echo "[SQL] {$sql}\n";
});

echo "=== 开始调试批量生成 ===\n\n";

try {
    echo "步骤1: 检查CardType是否存在\n";
    $type = CardType::find(1);
    if (!$type) {
        echo "错误: 类型ID=1不存在\n";
        echo "创建一个测试类型...\n";
        $type = CardType::create([
            'type_name' => '测试类型',
            'type_code' => 'TEST',
            'status' => 1,
            'sort_order' => 0
        ]);
        echo "已创建类型 ID={$type->id}\n";
    } else {
        echo "找到类型: {$type->type_name}\n";
    }
    echo "\n";

    echo "步骤2: 手动生成一个卡密\n";
    $testKey = 'TEST-' . strtoupper(substr(md5(uniqid()), 0, 12));
    echo "生成的卡密: {$testKey}\n";
    
    echo "步骤3: 检查是否已存在\n";
    $exists = CardKey::where('card_key', $testKey)->count();
    echo "存在数量: {$exists}\n\n";
    
    echo "步骤4: 插入到数据库\n";
    $data = [
        'card_key' => $testKey,
        'type_id' => $type->id,
        'status' => 0,
        'create_time' => date('Y-m-d H:i:s')
    ];
    
    $cardKey = CardKey::create($data);
    echo "插入成功! ID={$cardKey->id}\n\n";
    
    echo "步骤5: 批量插入测试\n";
    $batchData = [];
    for ($i = 0; $i < 3; $i++) {
        $batchData[] = [
            'card_key' => 'BATCH-' . strtoupper(substr(md5(uniqid()), 0, 12)),
            'type_id' => $type->id,
            'status' => 0,
            'create_time' => date('Y-m-d H:i:s')
        ];
    }
    
    $model = new CardKey();
    $result = $model->saveAll($batchData);
    echo "批量插入成功! 数量: " . count($result) . "\n\n";
    
    echo "=== 测试成功！===\n";
    
} catch (\Exception $e) {
    echo "\n!!! 发生错误 !!!\n";
    echo "错误信息: " . $e->getMessage() . "\n";
    echo "错误文件: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\n堆栈追踪:\n" . $e->getTraceAsString() . "\n";
}

