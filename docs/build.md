# ThinkScramble 构建指南

本文档介绍如何构建 ThinkScramble 的跨平台 CLI 可执行文件。

## 🎯 构建目标

- 生成单文件 PHAR 可执行文件
- 支持 Linux、macOS、Windows 平台
- 包含所有依赖，无需额外安装
- 提供安装脚本和使用说明

## 📋 系统要求

- PHP 8.0+
- Composer
- curl (用于下载 Box)
- 足够的磁盘空间 (~50MB)

## 🚀 快速构建

### 方法 1: 使用 PHP 脚本（推荐）

```bash
# 克隆项目
git clone https://github.com/yangweijie/think-scramble.git
cd think-scramble

# 运行构建脚本
php build.php
```

### 方法 2: 使用 Makefile

```bash
# 构建所有文件
make build

# 仅构建 PHAR
make phar

# 安装到系统
sudo make install
```

### 方法 3: 使用 Bash 脚本

```bash
# 给脚本执行权限
chmod +x build/build.sh

# 运行构建
./build/build.sh
```

## 🔧 手动构建步骤

### 1. 准备环境

```bash
# 安装依赖
composer install --no-dev --optimize-autoloader

# 创建构建目录
mkdir -p dist build/tools
```

### 2. 下载 Box (PHPacker)

```bash
curl -L https://github.com/box-project/box/releases/latest/download/box.phar -o build/tools/box.phar
chmod +x build/tools/box.phar
```

### 3. 构建 PHAR

```bash
php build/tools/box.phar compile --config=box.json
chmod +x dist/scramble.phar
```

### 4. 测试构建

```bash
php dist/scramble.phar --version
php dist/scramble.phar --help
```

### 5. 创建跨平台文件

```bash
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
```

## 🐳 Docker 构建

### 构建 Docker 镜像

```bash
docker build -f Dockerfile.build -t think-scramble-builder .
```

### 从容器中提取文件

```bash
# 运行容器并复制文件
docker run --name temp-container think-scramble-builder --version
docker cp temp-container:/app/dist ./
docker rm temp-container
```

## 📦 构建输出

构建完成后，`dist/` 目录包含以下文件：

```
dist/
├── scramble.phar              # 主要 PHAR 文件
├── scramble-linux             # Linux/macOS 可执行文件
├── scramble.bat               # Windows 批处理文件
├── scramble.ps1               # Windows PowerShell 脚本
├── install.sh                 # Linux/macOS 安装脚本
├── install.bat                # Windows 安装脚本
├── README.txt                 # 使用说明
└── think-scramble-1.4.0/      # 发布包目录
    ├── scramble.phar
    ├── scramble-linux
    ├── scramble.bat
    ├── scramble.ps1
    ├── install.sh
    ├── install.bat
    ├── README.txt
    └── LICENSE
```

## 🔍 构建验证

### 基本功能测试

```bash
# 版本检查
php dist/scramble.phar --version

# 帮助信息
php dist/scramble.phar --help

# 生成文档测试
cd /path/to/thinkphp/project
php /path/to/dist/scramble.phar --output=test-api.json
```

### 跨平台测试

```bash
# Linux/macOS
./dist/scramble-linux --version

# Windows (在 Windows 系统中)
dist\scramble.bat --version
```

## 📋 构建配置

### Box 配置 (box.json)

主要配置项：

- `main`: 入口文件 (`bin/scramble`)
- `output`: 输出文件 (`dist/scramble.phar`)
- `directories`: 包含的目录 (`src`)
- `finder`: 包含的 vendor 文件
- `compression`: 压缩方式 (`GZ`)

### 包含的依赖

构建会自动包含以下依赖：

- Composer autoloader
- PSR 标准库
- Symfony Console 组件
- 项目源代码

### 排除的文件

以下文件/目录会被排除：

- 测试文件 (`tests/`)
- 文档文件 (`docs/`)
- 示例文件 (`example/`)
- 开发配置文件
- Git 相关文件

## 🚨 常见问题

### 1. Box 下载失败

```bash
# 手动下载
wget https://github.com/box-project/box/releases/latest/download/box.phar
# 或使用镜像
wget https://github.com/box-project/box/releases/download/3.16.0/box.phar
```

### 2. 权限问题

```bash
# 确保脚本有执行权限
chmod +x build/build.sh
chmod +x build/tools/box.phar
```

### 3. 内存不足

```bash
# 增加 PHP 内存限制
php -d memory_limit=512M build/tools/box.phar compile
```

### 4. 依赖冲突

```bash
# 清理并重新安装依赖
rm -rf vendor composer.lock
composer install --no-dev --optimize-autoloader
```

## 🔧 自定义构建

### 修改包含的文件

编辑 `box.json` 中的 `finder` 配置：

```json
{
    "finder": [
        {
            "name": "*.php",
            "in": ["vendor/your-package"]
        }
    ]
}
```

### 添加自定义脚本

在 `build.php` 中添加自定义逻辑：

```php
// 添加自定义文件处理
echo "📝 Processing custom files...\n";
// 你的自定义代码
```

### 修改压缩设置

在 `box.json` 中修改压缩配置：

```json
{
    "compression": "BZ2",  // 或 "NONE"
    "compactors": [
        "KevinGH\\Box\\Compactor\\Php"
    ]
}
```

## 📈 性能优化

### 1. 启用 OPcache

```bash
# 构建时启用 OPcache
php -d opcache.enable_cli=1 build/tools/box.phar compile
```

### 2. 优化 Autoloader

```bash
# 使用优化的 autoloader
composer dump-autoload --optimize --classmap-authoritative
```

### 3. 减少文件大小

- 移除不必要的依赖
- 使用更强的压缩
- 排除开发文件

## 🚀 CI/CD 集成

项目包含 GitHub Actions 配置，支持：

- 多 PHP 版本测试
- 自动构建 PHAR
- 创建 GitHub Releases
- 上传构建产物

查看 `.github/workflows/build.yml` 了解详细配置。

## 📞 获取帮助

如果遇到构建问题：

1. 检查 PHP 版本和扩展
2. 确认网络连接（下载 Box）
3. 查看错误日志
4. 提交 Issue 到 GitHub

---

更多信息请访问：https://github.com/yangweijie/think-scramble
