<?php

/**
 * PIE 卸载脚本
 */

declare(strict_types=1);

echo "🗑️ Uninstalling ThinkScramble...\n";

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

echo "📁 Looking for installation in: {$installPath}\n";

// 检查文件是否存在
if (!file_exists($targetFile)) {
    echo "ℹ️ ThinkScramble is not installed in {$installPath}\n";
    
    // 尝试查找其他可能的位置
    $otherPaths = [
        '/usr/bin/scramble',
        '/usr/local/bin/scramble',
        getenv('HOME') . '/.local/bin/scramble',
    ];
    
    foreach ($otherPaths as $path) {
        if (file_exists($path)) {
            echo "📍 Found installation at: {$path}\n";
            $targetFile = $path;
            break;
        }
    }
    
    if (!file_exists($targetFile)) {
        echo "❌ ThinkScramble installation not found.\n";
        exit(1);
    }
}

// 检查权限
if ($os !== 'Windows' && !is_writable(dirname($targetFile))) {
    echo "❌ No write permission to " . dirname($targetFile) . ". Please run with sudo.\n";
    exit(1);
}

// 备份当前版本信息
echo "📋 Current installation info:\n";
$output = [];
$returnCode = 0;
exec("{$targetFile} --version 2>/dev/null", $output, $returnCode);

if ($returnCode === 0) {
    echo "   Version: " . implode("\n   ", $output) . "\n";
}

// 删除主文件
if (unlink($targetFile)) {
    echo "✅ Removed: {$targetFile}\n";
} else {
    echo "❌ Failed to remove: {$targetFile}\n";
    exit(1);
}

// Windows 特殊处理 - 删除 PHAR 文件
if ($os === 'Windows') {
    $pharFile = $installPath . DIRECTORY_SEPARATOR . 'scramble.phar';
    if (file_exists($pharFile)) {
        if (unlink($pharFile)) {
            echo "✅ Removed: {$pharFile}\n";
        } else {
            echo "⚠️ Failed to remove: {$pharFile}\n";
        }
    }
}

// 清理可能的缓存文件
$cacheDir = sys_get_temp_dir() . '/think-scramble-cache';
if (is_dir($cacheDir)) {
    echo "🧹 Cleaning cache directory: {$cacheDir}\n";
    
    $files = glob($cacheDir . '/*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    
    if (rmdir($cacheDir)) {
        echo "✅ Cache directory cleaned\n";
    }
}

echo "✅ ThinkScramble uninstalled successfully!\n";
echo "💡 Thank you for using ThinkScramble!\n";

// 提供反馈链接
echo "\n📝 We'd love your feedback:\n";
echo "   🐛 Report issues: https://github.com/yangweijie/think-scramble/issues\n";
echo "   💬 Discussions: https://github.com/yangweijie/think-scramble/discussions\n";
