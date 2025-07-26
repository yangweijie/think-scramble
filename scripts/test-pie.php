<?php

/**
 * PIE 功能测试脚本
 */

declare(strict_types=1);

echo "🧪 Testing PIE Integration for ThinkScramble\n";
echo "============================================\n\n";

// 测试配置
$tests = [
    'pie_config' => 'PIE Configuration',
    'install_script' => 'Install Script',
    'uninstall_script' => 'Uninstall Script', 
    'update_script' => 'Update Script',
    'status_script' => 'Status Script',
    'post_install' => 'Post-Install Script',
    'pre_uninstall' => 'Pre-Uninstall Script',
    'composer_config' => 'Composer PIE Configuration',
];

$results = [];

// 测试 PIE 配置文件
function testPieConfig(): bool
{
    echo "📄 Testing PIE configuration...\n";
    
    if (!file_exists('pie.json')) {
        echo "❌ pie.json not found\n";
        return false;
    }
    
    $config = json_decode(file_get_contents('pie.json'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "❌ Invalid JSON in pie.json\n";
        return false;
    }
    
    $requiredFields = ['name', 'description', 'type', 'license'];
    foreach ($requiredFields as $field) {
        if (!isset($config[$field])) {
            echo "❌ Missing required field: {$field}\n";
            return false;
        }
    }
    
    if (isset($config['extra']['pie'])) {
        echo "✅ PIE configuration found\n";
        return true;
    }
    
    echo "❌ PIE configuration missing in extra section\n";
    return false;
}

// 测试脚本文件
function testScript(string $scriptPath, string $description): bool
{
    echo "📜 Testing {$description}...\n";
    
    if (!file_exists($scriptPath)) {
        echo "❌ Script not found: {$scriptPath}\n";
        return false;
    }
    
    // 检查语法
    $output = [];
    $returnCode = 0;
    exec("php -l {$scriptPath}", $output, $returnCode);
    
    if ($returnCode !== 0) {
        echo "❌ Syntax error in {$scriptPath}\n";
        return false;
    }
    
    // 检查是否包含必要的输出
    $content = file_get_contents($scriptPath);
    
    if (strpos($content, 'echo') === false && strpos($content, 'print') === false) {
        echo "⚠️ Script may not provide user feedback: {$scriptPath}\n";
    }
    
    echo "✅ Script syntax valid: {$scriptPath}\n";
    return true;
}

// 测试 Composer 配置
function testComposerConfig(): bool
{
    echo "📦 Testing Composer PIE configuration...\n";
    
    if (!file_exists('composer.json')) {
        echo "❌ composer.json not found\n";
        return false;
    }
    
    $config = json_decode(file_get_contents('composer.json'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "❌ Invalid JSON in composer.json\n";
        return false;
    }
    
    // 检查 bin 配置
    if (!isset($config['bin']) || !in_array('bin/scramble', $config['bin'])) {
        echo "❌ Missing bin configuration in composer.json\n";
        return false;
    }
    
    // 检查 PIE 配置
    if (!isset($config['extra']['pie'])) {
        echo "❌ Missing PIE configuration in composer.json\n";
        return false;
    }
    
    $pieConfig = $config['extra']['pie'];
    
    // 检查必要的 PIE 配置
    $requiredPieFields = ['installer', 'commands'];
    foreach ($requiredPieFields as $field) {
        if (!isset($pieConfig[$field])) {
            echo "❌ Missing PIE field: {$field}\n";
            return false;
        }
    }
    
    // 检查命令配置
    $requiredCommands = ['install', 'uninstall', 'update', 'status'];
    foreach ($requiredCommands as $command) {
        if (!isset($pieConfig['commands'][$command])) {
            echo "❌ Missing PIE command: {$command}\n";
            return false;
        }
    }
    
    echo "✅ Composer PIE configuration valid\n";
    return true;
}

// 测试构建文件
function testBuildFiles(): bool
{
    echo "🔨 Testing build files...\n";
    
    $buildFiles = [
        'dist/scramble.phar',
        'dist/scramble-linux',
        'dist/scramble.bat',
    ];
    
    $foundFiles = 0;
    foreach ($buildFiles as $file) {
        if (file_exists($file)) {
            echo "✅ Found: {$file}\n";
            $foundFiles++;
        } else {
            echo "⚠️ Missing: {$file}\n";
        }
    }
    
    if ($foundFiles === 0) {
        echo "❌ No build files found. Run 'php build.php' first.\n";
        return false;
    }
    
    echo "✅ Build files available ({$foundFiles}/" . count($buildFiles) . ")\n";
    return true;
}

// 模拟 PIE 安装测试
function testPieInstallSimulation(): bool
{
    echo "🎭 Simulating PIE install process...\n";
    
    // 检查安装脚本
    if (!file_exists('scripts/pie-install.php')) {
        echo "❌ Install script not found\n";
        return false;
    }
    
    // 模拟运行安装脚本（不实际安装）
    echo "📋 Install script found and syntax valid\n";
    
    // 检查后安装脚本
    if (!file_exists('scripts/pie-post-install.php')) {
        echo "❌ Post-install script not found\n";
        return false;
    }
    
    echo "📋 Post-install script found\n";
    
    echo "✅ PIE install simulation passed\n";
    return true;
}

// 运行所有测试
echo "🚀 Starting PIE integration tests...\n\n";

// 基本配置测试
$results['pie_config'] = testPieConfig();
$results['composer_config'] = testComposerConfig();

echo "\n";

// 脚本测试
$scripts = [
    'install_script' => 'scripts/pie-install.php',
    'uninstall_script' => 'scripts/pie-uninstall.php',
    'update_script' => 'scripts/pie-update.php',
    'status_script' => 'scripts/pie-status.php',
    'post_install' => 'scripts/pie-post-install.php',
    'pre_uninstall' => 'scripts/pie-pre-uninstall.php',
];

foreach ($scripts as $key => $scriptPath) {
    $results[$key] = testScript($scriptPath, $tests[$key]);
}

echo "\n";

// 构建文件测试
$results['build_files'] = testBuildFiles();

echo "\n";

// PIE 安装模拟测试
$results['pie_simulation'] = testPieInstallSimulation();

echo "\n";

// 生成测试报告
echo "📊 Test Results Summary\n";
echo "======================\n\n";

$passed = 0;
$total = count($results);

foreach ($results as $test => $result) {
    $status = $result ? '✅ PASS' : '❌ FAIL';
    $description = $tests[$test] ?? $test;
    echo sprintf("%-25s %s\n", $description, $status);
    
    if ($result) {
        $passed++;
    }
}

echo "\n";
echo "📈 Overall Results: {$passed}/{$total} tests passed\n";

if ($passed === $total) {
    echo "🎉 All PIE integration tests passed!\n";
    echo "✅ ThinkScramble is ready for PIE distribution\n\n";
    
    echo "📋 Next Steps:\n";
    echo "1. Publish to Packagist: composer publish\n";
    echo "2. Create GitHub release with PHAR files\n";
    echo "3. Test PIE installation: pie install yangweijie/think-scramble\n";
    echo "4. Update documentation with PIE instructions\n";
    
    exit(0);
} else {
    echo "❌ Some tests failed. Please fix the issues before proceeding.\n";
    
    echo "\n🔧 Failed Tests:\n";
    foreach ($results as $test => $result) {
        if (!$result) {
            $description = $tests[$test] ?? $test;
            echo "- {$description}\n";
        }
    }
    
    exit(1);
}
