<?php
// 测试模型关联和withCount是否正确工作

// 设置路径
$currentDir = __DIR__ . '/src/admin/m-service-server';
require_once $currentDir . '/vendor/autoload.php';

// 引入配置
$config = include $currentDir . '/config/database.php';

// 初始化ThinkPHP
use think\facade\Db;
use app\api\model\comments;
use app\api\model\article;
use app\api\model\likes;

// 配置数据库连接
Db::setConfig($config);

echo "=== 1. 测试Comments模型关联 ===\n";

try {
    // 测试评论查询，看是否返回likes_count
    $comment = comments::withCount(['likes'])->find(6);
    if ($comment) {
        echo "评论ID: " . $comment->id . "\n";
        echo "评论内容: " . $comment->content . "\n";
        echo "likes_count: " . ($comment->likes_count ?? 'NULL') . "\n";
        
        // 手动测试关联
        $likesQuery = $comment->likes();
        echo "关联查询SQL: " . $likesQuery->buildSql() . "\n";
        $likesCount = $likesQuery->count();
        echo "手动统计点赞数: " . $likesCount . "\n";
    } else {
        echo "评论不存在\n";
    }
} catch (Exception $e) {
    echo "Comments测试错误: " . $e->getMessage() . "\n";
}

echo "\n=== 2. 测试Article模型关联 ===\n";

try {
    // 测试文章查询
    $article = article::withCount(['likes'])->find(11355);
    if ($article) {
        echo "文章ID: " . $article->id . "\n";
        echo "文章标题: " . $article->title . "\n";
        echo "likes_count: " . ($article->likes_count ?? 'NULL') . "\n";
        
        // 手动测试关联
        $likesQuery = $article->likes();
        echo "关联查询SQL: " . $likesQuery->buildSql() . "\n";
        $likesCount = $likesQuery->count();
        echo "手动统计点赞数: " . $likesCount . "\n";
    } else {
        echo "文章不存在\n";
    }
} catch (Exception $e) {
    echo "Article测试错误: " . $e->getMessage() . "\n";
}

echo "\n=== 3. 测试Likes表数据 ===\n";

try {
    $commentLikes = likes::where('target_type', 'comment')->select();
    echo "评论点赞记录数: " . count($commentLikes) . "\n";
    foreach ($commentLikes as $like) {
        echo "- 用户{$like->user_id}对评论{$like->target_id}点赞\n";
    }
    
    $articleLikes = likes::where('target_type', 'article')->select();
    echo "文章点赞记录数: " . count($articleLikes) . "\n";
    foreach ($articleLikes as $like) {
        echo "- 用户{$like->user_id}对文章{$like->target_id}点赞\n";
    }
} catch (Exception $e) {
    echo "Likes测试错误: " . $e->getMessage() . "\n";
}

echo "\n=== 测试完成 ===\n";
?>