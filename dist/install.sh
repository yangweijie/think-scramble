#!/bin/bash

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
