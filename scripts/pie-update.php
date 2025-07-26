<?php

/**
 * PIE 更新脚本
 */

declare(strict_types=1);

echo "🔄 Updating ThinkScramble...\n";

// 检查操作系统
$os = PHP_OS_FAMILY;
echo "📋 Detected OS: {$os}\n";

// 确定安装路径
$installPaths = [
    'Linux' => '/usr/local/bin',
    'Darwin' => '/usr/local/bin',
    'Windows' => getenv('USERPROFILE') . '\\bin',
];

$installPath = $installPaths[$os] ?? '/usr/local/bin';
$binaryName = $os === 'Windows' ? 'scramble.bat' : 'scramble';
$targetFile = $installPath . DIRECTORY_SEPARATOR . $binaryName;

// 检查当前安装
if (!file_exists($targetFile)) {
    echo "❌ ThinkScramble is not installed. Please install it first.\n";
    echo "💡 Run: pie install yangweijie/think-scramble\n";
    exit(1);
}

// 获取当前版本
echo "📋 Checking current version...\n";
$output = [];
$returnCode = 0;
exec("{$targetFile} --version 2>/dev/null", $output, $returnCode);

$currentVersion = 'unknown';
if ($returnCode === 0 && !empty($output)) {
    $versionLine = $output[0];
    if (preg_match('/v?(\d+\.\d+\.\d+)/', $versionLine, $matches)) {
        $currentVersion = $matches[1];
    }
}

echo "   Current version: {$currentVersion}\n";

// 检查最新版本 (模拟 - 实际应该从 GitHub API 获取)
$latestVersion = '1.4.0'; // 这里应该从 GitHub API 获取最新版本

echo "📋 Latest version: {$latestVersion}\n";

// 比较版本
if (version_compare($currentVersion, $latestVersion, '>=')) {
    echo "✅ You already have the latest version!\n";
    exit(0);
}

echo "🔄 Update available: {$currentVersion} → {$latestVersion}\n";

// 检查权限
if ($os !== 'Windows' && !is_writable(dirname($targetFile))) {
    echo "❌ No write permission to " . dirname($targetFile) . ". Please run with sudo.\n";
    exit(1);
}

// 备份当前版本
$backupFile = $targetFile . '.backup';
if (!copy($targetFile, $backupFile)) {
    echo "⚠️ Failed to create backup, continuing anyway...\n";
} else {
    echo "💾 Created backup: {$backupFile}\n";
}

// 下载新版本 (这里模拟更新过程)
echo "📥 Downloading latest version...\n";

// 检查新的构建文件是否存在
$sourceFile = $os === 'Windows' ? 'dist/scramble.bat' : 'dist/scramble-linux';

if (!file_exists($sourceFile)) {
    echo "❌ New version files not found. Please build the latest version first.\n";
    echo "💡 Run: php build.php\n";
    
    // 恢复备份
    if (file_exists($backupFile)) {
        copy($backupFile, $targetFile);
        unlink($backupFile);
        echo "🔄 Restored backup\n";
    }
    exit(1);
}

// 替换文件
if (!copy($sourceFile, $targetFile)) {
    echo "❌ Failed to update file\n";
    
    // 恢复备份
    if (file_exists($backupFile)) {
        copy($backupFile, $targetFile);
        unlink($backupFile);
        echo "🔄 Restored backup\n";
    }
    exit(1);
}

// 设置权限 (Unix-like 系统)
if ($os !== 'Windows') {
    chmod($targetFile, 0755);
}

// Windows 特殊处理
if ($os === 'Windows') {
    $pharSource = 'dist/scramble.phar';
    $pharTarget = $installPath . DIRECTORY_SEPARATOR . 'scramble.phar';
    
    if (file_exists($pharSource)) {
        copy($pharSource, $pharTarget);
    }
}

// 验证更新
echo "🧪 Verifying update...\n";
$output = [];
$returnCode = 0;
exec("{$targetFile} --version 2>/dev/null", $output, $returnCode);

if ($returnCode === 0) {
    echo "✅ Update successful!\n";
    echo "📋 New version: " . implode("\n", $output) . "\n";
    
    // 删除备份
    if (file_exists($backupFile)) {
        unlink($backupFile);
    }
} else {
    echo "❌ Update verification failed\n";
    
    // 恢复备份
    if (file_exists($backupFile)) {
        copy($backupFile, $targetFile);
        unlink($backupFile);
        echo "🔄 Restored backup\n";
    }
    exit(1);
}

// 清理缓存
$cacheDir = sys_get_temp_dir() . '/think-scramble-cache';
if (is_dir($cacheDir)) {
    echo "🧹 Clearing cache...\n";
    
    $files = glob($cacheDir . '/*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    
    echo "✅ Cache cleared\n";
}

echo "\n🎉 Update complete!\n";
echo "📚 Changelog: https://github.com/yangweijie/think-scramble/releases\n";
echo "🚀 Try: {$binaryName} --help\n";
