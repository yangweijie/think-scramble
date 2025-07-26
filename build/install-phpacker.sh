#!/bin/bash

# PHPacker 安装脚本

echo "Installing PHPacker..."

# 检查 PHP 版本
php_version=$(php -r "echo PHP_VERSION;")
echo "Current PHP version: $php_version"

if ! php -r "exit(version_compare(PHP_VERSION, '8.0.0', '>=') ? 0 : 1);"; then
    echo "Error: PHP 8.0+ is required"
    exit 1
fi

# 创建构建目录
mkdir -p build/tools

# 下载 PHPacker
echo "Downloading PHPacker..."
curl -L https://github.com/box-project/box/releases/latest/download/box.phar -o build/tools/box.phar

# 验证下载
if [ ! -f "build/tools/box.phar" ]; then
    echo "Error: Failed to download PHPacker"
    exit 1
fi

# 设置执行权限
chmod +x build/tools/box.phar

# 验证安装
echo "Verifying PHPacker installation..."
php build/tools/box.phar --version

echo "PHPacker installed successfully!"
