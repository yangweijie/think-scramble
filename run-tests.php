<?php

/**
 * ThinkScramble 测试运行器
 * 
 * 提供详细的测试结果和统计信息
 */

echo "🚀 ThinkScramble 测试套件\n";
echo "========================\n\n";

// 检查 PHP 版本
$phpVersion = PHP_VERSION;
echo "📋 环境信息:\n";
echo "  PHP 版本: {$phpVersion}\n";
echo "  内存限制: " . ini_get('memory_limit') . "\n";
echo "  最大执行时间: " . ini_get('max_execution_time') . "s\n\n";

// 检查扩展
$extensions = [
    'json' => '✅',
    'mbstring' => '✅', 
    'openssl' => '✅',
    'xdebug' => extension_loaded('xdebug') ? '✅' : '❌',
    'pcov' => extension_loaded('pcov') ? '✅' : '❌',
];

echo "🔧 PHP 扩展:\n";
foreach ($extensions as $ext => $status) {
    echo "  {$ext}: {$status}\n";
}
echo "\n";

// 检查覆盖率驱动
$coverageDriver = 'none';
if (extension_loaded('xdebug')) {
    $coverageDriver = 'xdebug';
} elseif (extension_loaded('pcov')) {
    $coverageDriver = 'pcov';
}

echo "📊 覆盖率驱动: {$coverageDriver}\n";
if ($coverageDriver === 'none') {
    echo "⚠️  注意: 没有可用的覆盖率驱动。要启用覆盖率，请安装 Xdebug 或 PCOV。\n";
}
echo "\n";

// 运行测试
echo "🧪 运行测试...\n";
echo "================\n\n";

$startTime = microtime(true);
$startMemory = memory_get_usage(true);

// 执行 Pest 测试
$isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
$pestBin = $isWindows ? 'vendor\\bin\\pest.bat' : 'vendor/bin/pest';

// 检查 Pest 是否存在
if (!file_exists($pestBin)) {
    $pestBin = $isWindows ? '.\\vendor\\bin\\pest.bat' : './vendor/bin/pest';
}

$command = $pestBin;
$output = [];
$returnCode = 0;

exec($command . ' 2>&1', $output, $returnCode);

$endTime = microtime(true);
$endMemory = memory_get_usage(true);

// 显示测试输出
foreach ($output as $line) {
    echo $line . "\n";
}

echo "\n";

// 显示统计信息
$duration = round(($endTime - $startTime) * 1000, 2);
$memoryUsed = $endMemory - $startMemory;
$memoryUsedMB = round($memoryUsed / 1024 / 1024, 2);

echo "📈 测试统计:\n";
echo "  执行时间: {$duration}ms\n";
echo "  内存使用: {$memoryUsedMB}MB\n";
echo "  返回码: {$returnCode}\n";

if ($returnCode === 0) {
    echo "  状态: ✅ 所有测试通过\n";
} else {
    echo "  状态: ❌ 测试失败\n";
}

echo "\n";

// 显示测试文件信息
echo "📁 测试文件:\n";
$testFiles = glob('tests/**/*Test.php');
foreach ($testFiles as $file) {
    $size = filesize($file);
    $lines = count(file($file));
    echo "  {$file} ({$lines} 行, {$size} 字节)\n";
}

echo "\n";

// 显示可用的测试命令
echo "🛠️  可用命令:\n";
echo "  composer test              - 运行所有测试\n";
echo "  composer test:unit         - 运行单元测试\n";
echo "  composer test:integration  - 运行集成测试\n";
echo "  composer test:no-coverage  - 运行测试（无覆盖率）\n";

if ($coverageDriver !== 'none') {
    echo "  composer test:coverage     - 运行测试并生成覆盖率报告\n";
    echo "  composer test:text-coverage - 显示文本覆盖率报告\n";
}

echo "\n";

// 显示项目信息
if (file_exists('composer.json')) {
    $composer = json_decode(file_get_contents('composer.json'), true);
    echo "📦 项目信息:\n";
    echo "  名称: " . ($composer['name'] ?? 'N/A') . "\n";
    echo "  描述: " . ($composer['description'] ?? 'N/A') . "\n";
    echo "  版本: " . ($composer['version'] ?? '1.0.0') . "\n";
    echo "\n";
}

// 显示建议
echo "💡 建议:\n";
if ($coverageDriver === 'none') {
    echo "  - 安装 Xdebug 或 PCOV 以启用代码覆盖率分析\n";
}
if ($returnCode !== 0) {
    echo "  - 检查失败的测试并修复相关问题\n";
    echo "  - 运行 'composer test:unit' 单独测试单元测试\n";
}
echo "  - 定期运行测试以确保代码质量\n";
echo "  - 为新功能添加相应的测试用例\n";

echo "\n🎉 测试完成！\n";

exit($returnCode);
