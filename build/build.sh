#!/bin/bash

# ThinkScramble 构建脚本

set -e

echo "🚀 Building ThinkScramble CLI..."

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 函数定义
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# 检查依赖
check_dependencies() {
    log_info "Checking dependencies..."
    
    # 检查 PHP
    if ! command -v php &> /dev/null; then
        log_error "PHP is not installed"
        exit 1
    fi
    
    php_version=$(php -r "echo PHP_VERSION;")
    log_info "PHP version: $php_version"
    
    if ! php -r "exit(version_compare(PHP_VERSION, '8.0.0', '>=') ? 0 : 1);"; then
        log_error "PHP 8.0+ is required"
        exit 1
    fi
    
    # 检查 Composer
    if ! command -v composer &> /dev/null; then
        log_error "Composer is not installed"
        exit 1
    fi
    
    log_success "Dependencies check passed"
}

# 安装依赖
install_dependencies() {
    log_info "Installing dependencies..."
    
    # 安装 Composer 依赖
    composer install --no-dev --optimize-autoloader --no-interaction
    
    # 安装 Box (PHPacker)
    if [ ! -f "build/tools/box.phar" ]; then
        log_info "Installing Box (PHPacker)..."
        mkdir -p build/tools
        curl -L https://github.com/box-project/box/releases/latest/download/box.phar -o build/tools/box.phar
        chmod +x build/tools/box.phar
    fi
    
    log_success "Dependencies installed"
}

# 运行测试
run_tests() {
    log_info "Running tests..."
    
    # 检查语法错误
    find src -name "*.php" -exec php -l {} \; > /dev/null
    
    # 运行基本功能测试
    php bin/scramble --version > /dev/null
    
    log_success "Tests passed"
}

# 清理构建目录
clean_build() {
    log_info "Cleaning build directory..."
    
    rm -rf dist
    mkdir -p dist
    
    log_success "Build directory cleaned"
}

# 构建 PHAR
build_phar() {
    log_info "Building PHAR file..."
    
    # 使用 Box 构建
    php build/tools/box.phar compile --config=box.json
    
    if [ ! -f "dist/scramble.phar" ]; then
        log_error "PHAR build failed"
        exit 1
    fi
    
    # 设置执行权限
    chmod +x dist/scramble.phar
    
    # 验证 PHAR
    php dist/scramble.phar --version
    
    log_success "PHAR built successfully"
}

# 创建跨平台可执行文件
create_executables() {
    log_info "Creating cross-platform executables..."
    
    # Linux/macOS 可执行文件
    cp dist/scramble.phar dist/scramble-linux
    chmod +x dist/scramble-linux
    
    # Windows 批处理文件
    cat > dist/scramble.bat << 'EOF'
@echo off
php "%~dp0scramble.phar" %*
EOF
    
    # Windows PowerShell 脚本
    cat > dist/scramble.ps1 << 'EOF'
#!/usr/bin/env pwsh
php "$PSScriptRoot/scramble.phar" @args
EOF
    
    log_success "Cross-platform executables created"
}

# 生成安装脚本
generate_install_scripts() {
    log_info "Generating install scripts..."
    
    # Linux/macOS 安装脚本
    cat > dist/install.sh << 'EOF'
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
EOF
    
    chmod +x dist/install.sh
    
    # Windows 安装脚本
    cat > dist/install.bat << 'EOF'
@echo off
echo Installing ThinkScramble CLI...

set INSTALL_DIR=%USERPROFILE%\bin
if not exist "%INSTALL_DIR%" mkdir "%INSTALL_DIR%"

copy scramble.phar "%INSTALL_DIR%\"
copy scramble.bat "%INSTALL_DIR%\"

echo ThinkScramble installed successfully!
echo Add %INSTALL_DIR% to your PATH if not already added
echo Usage: scramble --help
EOF
    
    log_success "Install scripts generated"
}

# 生成文档
generate_docs() {
    log_info "Generating documentation..."
    
    # 生成使用说明
    cat > dist/README.txt << 'EOF'
ThinkScramble CLI - ThinkPHP OpenAPI Documentation Generator

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
EOF
    
    log_success "Documentation generated"
}

# 创建发布包
create_release() {
    log_info "Creating release package..."
    
    # 获取版本信息
    VERSION=$(php -r "echo json_decode(file_get_contents('composer.json'))->version ?? '1.4.0';")
    
    # 创建发布目录
    RELEASE_DIR="dist/think-scramble-$VERSION"
    mkdir -p "$RELEASE_DIR"
    
    # 复制文件
    cp dist/scramble.phar "$RELEASE_DIR/"
    cp dist/scramble-linux "$RELEASE_DIR/"
    cp dist/scramble.bat "$RELEASE_DIR/"
    cp dist/scramble.ps1 "$RELEASE_DIR/"
    cp dist/install.sh "$RELEASE_DIR/"
    cp dist/install.bat "$RELEASE_DIR/"
    cp dist/README.txt "$RELEASE_DIR/"
    cp LICENSE "$RELEASE_DIR/" 2>/dev/null || echo "MIT License" > "$RELEASE_DIR/LICENSE"
    
    # 创建压缩包
    cd dist
    tar -czf "think-scramble-$VERSION.tar.gz" "think-scramble-$VERSION"
    zip -r "think-scramble-$VERSION.zip" "think-scramble-$VERSION"
    cd ..
    
    log_success "Release package created: think-scramble-$VERSION"
}

# 显示构建信息
show_build_info() {
    log_info "Build Information:"
    echo "  Version: $(php -r "echo json_decode(file_get_contents('composer.json'))->version ?? '1.4.0';")"
    echo "  PHP Version: $(php -r "echo PHP_VERSION;")"
    echo "  Build Date: $(date)"
    echo "  PHAR Size: $(ls -lh dist/scramble.phar | awk '{print $5}')"
    echo ""
    echo "Files created:"
    ls -la dist/
}

# 主函数
main() {
    echo "🚀 ThinkScramble Build Script"
    echo "=============================="
    
    check_dependencies
    install_dependencies
    run_tests
    clean_build
    build_phar
    create_executables
    generate_install_scripts
    generate_docs
    create_release
    show_build_info
    
    log_success "Build completed successfully! 🎉"
    echo ""
    echo "To test the build:"
    echo "  ./dist/scramble.phar --version"
    echo ""
    echo "To install globally:"
    echo "  sudo ./dist/install.sh"
}

# 运行主函数
main "$@"
