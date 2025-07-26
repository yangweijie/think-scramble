<?php

/**
 * ThinkScramble 本地构建脚本
 */

echo "🚀 Building ThinkScramble CLI...\n";

// 检查 PHP 版本
if (version_compare(PHP_VERSION, '8.0.0', '<')) {
    echo "❌ Error: PHP 8.0+ is required. Current: " . PHP_VERSION . "\n";
    exit(1);
}

// 检查依赖
if (!file_exists('vendor/autoload.php')) {
    echo "📦 Installing dependencies...\n";
    exec('composer install --no-dev --optimize-autoloader', $output, $returnCode);
    if ($returnCode !== 0) {
        echo "❌ Failed to install dependencies\n";
        exit(1);
    }
}

// 创建构建目录
if (!is_dir('dist')) {
    mkdir('dist', 0755, true);
}

if (!is_dir('build/tools')) {
    mkdir('build/tools', 0755, true);
}

// 下载 Box (PHPacker)
$boxPath = 'build/tools/box.phar';
if (!file_exists($boxPath)) {
    echo "📥 Downloading Box (PHPacker)...\n";
    $boxUrl = 'https://github.com/box-project/box/releases/latest/download/box.phar';
    $boxContent = file_get_contents($boxUrl);
    
    if ($boxContent === false) {
        echo "❌ Failed to download Box\n";
        exit(1);
    }
    
    file_put_contents($boxPath, $boxContent);
    chmod($boxPath, 0755);
}

// 验证 Box
echo "🔍 Verifying Box installation...\n";
exec("php $boxPath --version", $output, $returnCode);
if ($returnCode !== 0) {
    echo "❌ Box verification failed\n";
    exit(1);
}

// 运行语法检查
echo "🔍 Running syntax check...\n";
$syntaxErrors = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('src'));

foreach ($iterator as $file) {
    if ($file->getExtension() === 'php') {
        exec("php -l " . escapeshellarg($file->getPathname()), $output, $returnCode);
        if ($returnCode !== 0) {
            $syntaxErrors[] = $file->getPathname();
        }
    }
}

if (!empty($syntaxErrors)) {
    echo "❌ Syntax errors found in:\n";
    foreach ($syntaxErrors as $file) {
        echo "  - $file\n";
    }
    exit(1);
}

// 测试 CLI
echo "🧪 Testing CLI...\n";
exec('php bin/scramble --version', $output, $returnCode);
if ($returnCode !== 0) {
    echo "❌ CLI test failed\n";
    exit(1);
}

// 构建 PHAR
echo "📦 Building PHAR...\n";
exec("php $boxPath compile --config=box.json", $output, $returnCode);
if ($returnCode !== 0) {
    echo "❌ PHAR build failed\n";
    exit(1);
}

if (!file_exists('dist/scramble.phar')) {
    echo "❌ PHAR file not found\n";
    exit(1);
}

// 设置执行权限
chmod('dist/scramble.phar', 0755);

// 测试 PHAR
echo "🧪 Testing PHAR...\n";
exec('php dist/scramble.phar --version', $output, $returnCode);
if ($returnCode !== 0) {
    echo "❌ PHAR test failed\n";
    exit(1);
}

// 创建跨平台可执行文件
echo "🌍 Creating cross-platform executables...\n";

// Linux/macOS 可执行文件
copy('dist/scramble.phar', 'dist/scramble-linux');
chmod('dist/scramble-linux', 0755);

// Windows 批处理文件
file_put_contents('dist/scramble.bat', '@echo off
php "%~dp0scramble.phar" %*
');

// Windows PowerShell 脚本
file_put_contents('dist/scramble.ps1', '#!/usr/bin/env pwsh
php "$PSScriptRoot/scramble.phar" @args
');

// 生成安装脚本
echo "📝 Generating install scripts...\n";

// Linux/macOS 安装脚本
file_put_contents('dist/install.sh', '#!/bin/bash

# ThinkScramble 安装脚本

set -e

INSTALL_DIR="/usr/local/bin"
BINARY_NAME="scramble"

echo "Installing ThinkScramble CLI..."

# 检查权限
if [ "$EUID" -ne 0 ]; then
    echo "Please run as root (use sudo)"
    exit 1
fi

# 复制文件
cp scramble-linux "$INSTALL_DIR/$BINARY_NAME"
chmod +x "$INSTALL_DIR/$BINARY_NAME"

echo "ThinkScramble installed successfully!"
echo "Usage: scramble --help"
');

chmod('dist/install.sh', 0755);

// Windows 安装脚本
file_put_contents('dist/install.bat', '@echo off
echo Installing ThinkScramble CLI...

set INSTALL_DIR=%USERPROFILE%\bin
if not exist "%INSTALL_DIR%" mkdir "%INSTALL_DIR%"

copy scramble.phar "%INSTALL_DIR%\"
copy scramble.bat "%INSTALL_DIR%\"

echo ThinkScramble installed successfully!
echo Add %INSTALL_DIR% to your PATH if not already added
echo Usage: scramble --help
');

// 生成使用说明
file_put_contents('dist/README.txt', 'ThinkScramble CLI - ThinkPHP OpenAPI Documentation Generator

INSTALLATION:
  Linux/macOS: Run ./install.sh as root
  Windows: Run install.bat as administrator

USAGE:
  scramble --help                    Show help
  scramble --version                 Show version
  scramble --output=api.json         Generate documentation
  scramble --watch --output=api.json Monitor file changes
  scramble --stats                   Show statistics

EXAMPLES:
  # Generate basic documentation
  scramble --output=api.json

  # Generate with middleware analysis
  scramble --output=api.json --middleware

  # Export to Postman format
  scramble --format=postman --output=api.postman.json

  # Watch for file changes
  scramble --watch --output=api.json

For more information, visit: https://github.com/yangweijie/think-scramble
');

// 创建发布包
echo "📦 Creating release package...\n";

$composerData = json_decode(file_get_contents('composer.json'), true);
$version = $composerData['version'] ?? '1.4.0';

$releaseDir = "dist/think-scramble-$version";
if (!is_dir($releaseDir)) {
    mkdir($releaseDir, 0755, true);
}

// 复制文件到发布目录
$filesToCopy = [
    'scramble.phar',
    'scramble-linux',
    'scramble.bat',
    'scramble.ps1',
    'install.sh',
    'install.bat',
    'README.txt'
];

foreach ($filesToCopy as $file) {
    copy("dist/$file", "$releaseDir/$file");
}

// 复制许可证
if (file_exists('LICENSE')) {
    copy('LICENSE', "$releaseDir/LICENSE");
} else {
    file_put_contents("$releaseDir/LICENSE", "MIT License\n");
}

// 创建压缩包
if (class_exists('ZipArchive')) {
    $zip = new ZipArchive();
    $zipFile = "dist/think-scramble-$version.zip";
    
    if ($zip->open($zipFile, ZipArchive::CREATE) === TRUE) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($releaseDir));
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $relativePath = str_replace($releaseDir . '/', '', $file->getPathname());
                $zip->addFile($file->getPathname(), "think-scramble-$version/$relativePath");
            }
        }
        
        $zip->close();
        echo "📦 Created ZIP package: $zipFile\n";
    }
}

// 显示构建信息
echo "\n✅ Build completed successfully!\n";
echo "=====================================\n";
echo "Version: $version\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Build Date: " . date('Y-m-d H:i:s') . "\n";
echo "PHAR Size: " . formatBytes(filesize('dist/scramble.phar')) . "\n";
echo "\nFiles created:\n";

$files = scandir('dist');
foreach ($files as $file) {
    if ($file !== '.' && $file !== '..' && is_file("dist/$file")) {
        $size = formatBytes(filesize("dist/$file"));
        echo "  - $file ($size)\n";
    }
}

echo "\nTo test the build:\n";
echo "  php dist/scramble.phar --version\n";
echo "\nTo install globally:\n";
echo "  sudo ./dist/install.sh\n";

function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}
