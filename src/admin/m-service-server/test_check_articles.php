<?php
// 测试脚本：检查文章的visibility字段值

require __DIR__ . '/vendor/autoload.php';

// 引入ThinkPHP应用
$app = require __DIR__ . '/../../../../../../vendor/topthink/framework/src/helper.php';

try {
    // 直接使用Db Facade
    echo "==================== 检查文章visibility值 ====================\n";
    
    // 查询所有文章的visibility字段
    $articles = \think\facade\Db::table('article')
        ->field('id, title, visibility, author_id')
        ->limit(20)
        ->select()
        ->toArray();
    
    echo "文章数据 (前20条):\n";
    foreach ($articles as $article) {
        $vis = $article['visibility'] ?? 'NULL';
        echo "ID: {$article['id']}, Title: {$article['title']}, Visibility: {$vis}, AuthorID: {$article['author_id']}\n";
    }
    
    echo "\n==================== Visibility 统计 ====================\n";
    $stats = \think\facade\Db::table('article')
        ->field('visibility, COUNT(*) as count')
        ->group('visibility')
        ->select()
        ->toArray();
    
    echo "Visibility分布:\n";
    foreach ($stats as $stat) {
        $vis = $stat['visibility'] ?? 'NULL';
        echo "{$vis}: {$stat['count']}\n";
    }
    
} catch (\Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

