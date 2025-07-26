<?php

/**
 * PIE 安装脚本
 */

declare(strict_types=1);

echo "🥧 Installing ThinkScramble via PIE...\n";

// 检查 PHP 版本
if (version_compare(PHP_VERSION, '8.0.0', '<')) {
    echo "❌ Error: PHP 8.0+ is required. Current: " . PHP_VERSION . "\n";
    exit(1);
}

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

echo "📁 Install path: {$installPath}\n";

// 创建安装目录
if (!is_dir($installPath)) {
    if (!mkdir($installPath, 0755, true)) {
        echo "❌ Failed to create install directory: {$installPath}\n";
        exit(1);
    }
    echo "📁 Created install directory: {$installPath}\n";
}

// 检查权限
if ($os !== 'Windows' && !is_writable($installPath)) {
    echo "❌ No write permission to {$installPath}. Please run with sudo.\n";
    exit(1);
}

// 复制文件
$sourceFile = $os === 'Windows' ? 'dist/scramble.bat' : 'dist/scramble-linux';
$targetFile = $installPath . DIRECTORY_SEPARATOR . $binaryName;

if (!file_exists($sourceFile)) {
    echo "❌ Source file not found: {$sourceFile}\n";
    echo "💡 Please run 'php build.php' first to build the executable.\n";
    exit(1);
}

if (!copy($sourceFile, $targetFile)) {
    echo "❌ Failed to copy file to {$targetFile}\n";
    exit(1);
}

// 设置权限 (Unix-like 系统)
if ($os !== 'Windows') {
    chmod($targetFile, 0755);
}

// Windows 特殊处理
if ($os === 'Windows') {
    // 同时复制 PHAR 文件
    $pharSource = 'dist/scramble.phar';
    $pharTarget = $installPath . DIRECTORY_SEPARATOR . 'scramble.phar';
    
    if (file_exists($pharSource)) {
        copy($pharSource, $pharTarget);
    }
    
    echo "💡 Add {$installPath} to your PATH environment variable if not already added.\n";
}

echo "✅ ThinkScramble installed successfully!\n";
echo "🚀 Usage: {$binaryName} --help\n";

// 验证安装
echo "🧪 Testing installation...\n";
$testCommand = $os === 'Windows' ? 
    "cd /d {$installPath} && {$binaryName} --version" : 
    "{$targetFile} --version";

$output = [];
$returnCode = 0;
exec($testCommand, $output, $returnCode);

if ($returnCode === 0) {
    echo "✅ Installation test passed!\n";
    echo "📋 Version: " . implode("\n", $output) . "\n";
} else {
    echo "⚠️ Installation test failed, but files were copied successfully.\n";
}

echo "\n🎉 Installation complete!\n";
echo "📚 Documentation: https://github.com/yangweijie/think-scramble\n";
echo "🐛 Issues: https://github.com/yangweijie/think-scramble/issues\n";
