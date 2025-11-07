<?php
/**
 * JWT Token 验证测试脚本
 * 用于验证 JWTUtil 是否正确处理篡改的 Token
 */

require __DIR__ . '/vendor/autoload.php';

use utils\JWTUtil;

echo "========================================\n";
echo "JWT Token 验证测试\n";
echo "========================================\n\n";

// 测试1: 生成一个正常的 Token
echo "【测试1】生成正常 Token\n";
$testData = [
    'loginTime' => time(),
    'account' => '103',
    'id' => 103,
    'platform' => 'Web',
    'fingerprint' => 'Web'
];

$validToken = JWTUtil::generateToken($testData, 3600);
echo "✅ 生成的 Token: " . substr($validToken, 0, 50) . "...\n\n";

// 测试2: 验证正常的 Token
echo "【测试2】验证正常 Token\n";
$result1 = JWTUtil::verifyToken($validToken);
echo "验证结果:\n";
echo "  code: {$result1['code']}\n";
echo "  msg: {$result1['msg']}\n";
echo "  结论: " . ($result1['code'] === 200 ? "✅ 通过" : "❌ 失败") . "\n\n";

// 测试3: 篡改 Token（在开头加 "1"）
echo "【测试3】篡改 Token（在开头加 '1'）\n";
$tamperedToken = '1' . $validToken;
echo "篡改后的 Token: " . substr($tamperedToken, 0, 50) . "...\n";
$result2 = JWTUtil::verifyToken($tamperedToken);
echo "验证结果:\n";
echo "  code: {$result2['code']}\n";
echo "  msg: {$result2['msg']}\n";
echo "  结论: " . ($result2['code'] !== 200 ? "✅ 正确拒绝" : "❌ 错误通过") . "\n\n";

// 测试4: 篡改 Token（修改中间部分）
echo "【测试4】篡改 Token（修改中间部分）\n";
$parts = explode('.', $validToken);
if (count($parts) === 3) {
    $parts[1] = substr($parts[1], 0, -5) . 'XXXXX'; // 修改 payload
    $tamperedToken2 = implode('.', $parts);
    echo "篡改后的 Token: " . substr($tamperedToken2, 0, 50) . "...\n";
    $result3 = JWTUtil::verifyToken($tamperedToken2);
    echo "验证结果:\n";
    echo "  code: {$result3['code']}\n";
    echo "  msg: {$result3['msg']}\n";
    echo "  结论: " . ($result3['code'] !== 200 ? "✅ 正确拒绝" : "❌ 错误通过") . "\n\n";
}

// 测试5: 完全无效的 Token
echo "【测试5】完全无效的 Token\n";
$invalidToken = "this_is_not_a_valid_jwt_token";
$result4 = JWTUtil::verifyToken($invalidToken);
echo "验证结果:\n";
echo "  code: {$result4['code']}\n";
echo "  msg: {$result4['msg']}\n";
echo "  结论: " . ($result4['code'] !== 200 ? "✅ 正确拒绝" : "❌ 错误通过") . "\n\n";

// 测试6: 生成过期的 Token（-10秒）
echo "【测试6】验证已过期的 Token\n";
$expiredToken = JWTUtil::generateToken($testData, -10);
sleep(1); // 等待1秒确保过期
$result5 = JWTUtil::verifyToken($expiredToken);
echo "验证结果:\n";
echo "  code: {$result5['code']}\n";
echo "  msg: {$result5['msg']}\n";
echo "  结论: " . ($result5['code'] === 103 ? "✅ 正确识别为过期" : "❌ 未识别过期") . "\n\n";

echo "========================================\n";
echo "测试完成\n";
echo "========================================\n";

// 测试7: 使用你提供的真实 Token（篡改版）
echo "\n【测试7】验证你提供的篡改 Token\n";
$yourTamperedToken = "1eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiIiLCJhdWQiOiIiLCJpYXQiOjE3NjI1MzUyMDksIm5iZiI6MTc2MjUzNTIwOSwiZXhwIjozNTI1MDc3NjE4LCJkYXRhIjp7ImxvZ2luVGltZSI6MTc2MjUzNTIwOSwiYWNjb3VudCI6IjEwMyIsImlkIjoxMDMsInBsYXRmb3JtIjoiV2ViIiwiZmluZ2VycHJpbnQiOiJXZWIifX0.lLEXzgViL7K-ZXoRvvqJ3eoTQXYenzmQLh-38iFWOkU";
echo "Token: " . substr($yourTamperedToken, 0, 60) . "...\n";
$result6 = JWTUtil::verifyToken($yourTamperedToken);
echo "验证结果:\n";
echo "  code: {$result6['code']}\n";
echo "  msg: {$result6['msg']}\n";
echo "  结论: " . ($result6['code'] !== 200 ? "✅ 正确拒绝" : "❌ 错误通过") . "\n\n";

// 测试8: 验证原始 Token（去掉开头的 "1"）
echo "【测试8】验证原始 Token（去掉开头的 '1'）\n";
$originalToken = substr($yourTamperedToken, 1);
echo "Token: " . substr($originalToken, 0, 60) . "...\n";
$result7 = JWTUtil::verifyToken($originalToken);
echo "验证结果:\n";
echo "  code: {$result7['code']}\n";
echo "  msg: {$result7['msg']}\n";
echo "  结论: " . ($result7['code'] === 200 ? "✅ 验证通过" : "❌ 验证失败: {$result7['msg']}") . "\n";
