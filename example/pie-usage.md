# ThinkScramble PIE 使用示例

本文档展示如何使用 PIE 安装和管理 ThinkScramble。

## 🎯 完整使用流程

### 1. 安装 PIE

```bash
# 方法 1: 通过 Composer 全局安装
composer global require pie/pie

# 方法 2: 下载 PHAR 文件
curl -L https://github.com/pie-framework/pie/releases/latest/download/pie.phar -o pie.phar
chmod +x pie.phar
sudo mv pie.phar /usr/local/bin/pie

# 验证 PIE 安装
pie --version
```

### 2. 安装 ThinkScramble

```bash
# 安装最新版本
pie install yangweijie/think-scramble

# 安装过程输出示例：
# 🥧 Installing ThinkScramble via PIE...
# 📋 Detected OS: Linux
# 📁 Install path: /usr/local/bin
# 📁 Created install directory: /usr/local/bin
# ✅ ThinkScramble installed successfully!
# 🚀 Usage: scramble --help
# 🧪 Testing installation...
# ✅ Installation test passed!
# 📋 Version: ThinkScramble v1.4.0
# 
# 🎉 Installation complete!
```

### 3. 安装后配置

安装完成后，PIE 会自动执行配置：

```bash
# 自动创建的文件和目录：
~/.think-scramble/                    # 配置目录
~/.think-scramble/config.php          # 默认配置
~/.think-scramble/example-project.php # 示例项目配置
/tmp/think-scramble-cache/            # 缓存目录

# Shell 补全（自动添加到 ~/.bashrc 或 ~/.zshrc）
complete -W "--help --version --output --config --format --controllers --models --middleware --validate --stats --watch" scramble
```

### 4. 验证安装

```bash
# 检查版本
scramble --version
# 输出: ThinkScramble v1.4.0

# 查看帮助
scramble --help

# 检查安装状态
pie status yangweijie/think-scramble
```

## 📋 PIE 命令示例

### 安装管理

```bash
# 安装指定版本
pie install yangweijie/think-scramble:1.4.0

# 强制重新安装
pie install --force yangweijie/think-scramble

# 全局安装
pie install --global yangweijie/think-scramble

# 本地安装到项目
pie install --local yangweijie/think-scramble

# 详细安装信息
pie install --verbose yangweijie/think-scramble
```

### 更新管理

```bash
# 检查更新
pie outdated yangweijie/think-scramble

# 更新到最新版本
pie update yangweijie/think-scramble
# 输出示例：
# 🔄 Updating ThinkScramble...
# 📋 Detected OS: Linux
# 📋 Checking current version...
#    Current version: 1.3.0
# 📋 Latest version: 1.4.0
# 🔄 Update available: 1.3.0 → 1.4.0
# 💾 Created backup: /usr/local/bin/scramble.backup
# 📥 Downloading latest version...
# 🧪 Verifying update...
# ✅ Update successful!
# 📋 New version: ThinkScramble v1.4.0

# 更新到指定版本
pie update yangweijie/think-scramble:1.5.0

# 回滚版本
pie rollback yangweijie/think-scramble
```

### 状态检查

```bash
# 详细状态信息
pie status yangweijie/think-scramble

# 输出示例：
# 📊 ThinkScramble Installation Status
# ====================================
# 
# 🖥️ Operating System: Linux
# 🐘 PHP Version: 8.1.0
# 
# 📁 Expected install path: /usr/local/bin
# 📄 Binary name: scramble
# 
# ✅ ThinkScramble is installed: /usr/local/bin/scramble
# 
# 📋 Installation Details:
#    Path: /usr/local/bin/scramble
#    Size: 628.56 KB
#    Modified: 2024-01-26 12:00:00
#    Permissions: 0755
#    Executable: ✅ Yes
# 
# 🏷️ Version Information:
#    ThinkScramble v1.4.0
# 
# 🧪 Functionality Test:
#    ✅ Help command works
# 
# 💾 Cache Information:
#    Cache directory: /tmp/think-scramble-cache
#    Cache files: 5
#    Cache size: 2.34 MB
#    Last modified: 2024-01-26 11:30:00
# 
# 🛤️ PATH Information:
#    ✅ Install directory is in PATH
# 
# ⚙️ System Requirements:
#    ✅ PHP version: 8.1.0 (>= 8.0.0)
#    ✅ Extension json: loaded
#    ✅ Extension mbstring: loaded
#    ✅ Extension yaml: loaded (optional)
#    ⚠️ Extension zip: not loaded (optional)
# 
# 📝 Summary:
#    Status: ✅ Installed and functional
#    Location: /usr/local/bin/scramble
#    Ready to use: ✅ Yes
```

### 卸载

```bash
# 卸载 ThinkScramble
pie uninstall yangweijie/think-scramble

# 输出示例：
# 🗑️ Uninstalling ThinkScramble...
# 📋 Detected OS: Linux
# 📁 Looking for installation in: /usr/local/bin
# ✅ ThinkScramble is installed: /usr/local/bin/scramble
# 
# 📋 Current installation info:
#    Version: ThinkScramble v1.4.0
# 
# ✅ Removed: /usr/local/bin/scramble
# 🧹 Cleaning cache directory: /tmp/think-scramble-cache
# ✅ Cache directory cleaned
# ✅ ThinkScramble uninstalled successfully!
# 💡 Thank you for using ThinkScramble!
```

## 🎯 实际项目使用

### 1. 在 ThinkPHP 项目中使用

```bash
# 进入 ThinkPHP 项目目录
cd /path/to/your/thinkphp/project

# 复制示例配置
cp ~/.think-scramble/example-project.php ./scramble.php

# 编辑配置文件
nano scramble.php

# 生成 API 文档
scramble --output=public/api.json --middleware

# 实时监控文件变化
scramble --watch --output=public/api.json
```

### 2. 配置文件示例

项目根目录的 `scramble.php`：

```php
<?php

return [
    'info' => [
        'title' => 'My ThinkPHP API',
        'version' => '1.0.0',
        'description' => 'A comprehensive API for my application',
    ],
    
    'servers' => [
        [
            'url' => 'http://localhost:8000',
            'description' => 'Development server',
        ],
        [
            'url' => 'https://api.example.com',
            'description' => 'Production server',
        ],
    ],
    
    'paths' => [
        'controllers' => 'app/controller',
        'models' => 'app/model',
    ],
    
    'security' => [
        'enabled_schemes' => ['BearerAuth', 'ApiKeyAuth'],
    ],
    
    'cache' => [
        'driver' => 'file',
        'file' => [
            'path' => './runtime/scramble-cache',
        ],
    ],
];
```

### 3. 多格式导出

```bash
# JSON 格式（默认）
scramble --output=api.json

# YAML 格式
scramble --output=api.yaml

# Postman Collection
scramble --format=postman --output=api.postman.json

# Insomnia Workspace
scramble --format=insomnia --output=api.insomnia.json

# 批量导出
scramble --output=api.json
scramble --format=postman --output=api.postman.json
scramble --format=insomnia --output=api.insomnia.json
```

## 🔧 高级用法

### 1. 性能监控

```bash
# 启用性能监控
scramble --output=api.json --stats

# 查看详细统计
scramble --stats

# 输出示例：
# ThinkScramble Statistics
# ========================
# Cache Statistics:
#   Hits: 45
#   Misses: 12
#   Total Files: 23
#   Total Size: 2.34 MB
# 
# Controller Statistics:
#   Total Controllers: 8
# 
# Model Statistics:
#   Total Models: 15
```

### 2. 配置验证

```bash
# 验证配置文件
scramble --validate --config=scramble.php

# 输出示例：
# Validating configuration...
# ✅ Configuration is valid
# 
# Warnings:
#   - Consider adding more security schemes
#   - Cache TTL could be optimized
```

### 3. 自定义配置

```bash
# 使用自定义配置文件
scramble --config=custom-config.php --output=api.json

# 指定特定目录
scramble --controllers=app/api --models=app/entity --output=api.json

# 组合使用
scramble --config=production.php --middleware --output=prod-api.json
```

## 🚨 故障排除

### 常见问题和解决方案

```bash
# 1. PIE 命令未找到
which pie
# 如果没有输出，重新安装 PIE

# 2. 权限问题
sudo pie install yangweijie/think-scramble

# 3. 版本冲突
pie uninstall yangweijie/think-scramble
pie install --force yangweijie/think-scramble

# 4. 缓存问题
scramble --stats  # 查看缓存状态
rm -rf /tmp/think-scramble-cache  # 清理缓存

# 5. 配置问题
scramble --validate  # 验证配置
```

## 📚 更多资源

- [PIE 官方文档](https://pie-framework.org)
- [ThinkScramble GitHub](https://github.com/yangweijie/think-scramble)
- [PIE 安装指南](../docs/pie-installation.md)
- [配置参考](../docs/configuration.md)

---

🎉 **通过 PIE 享受 ThinkScramble 的现代化安装和管理体验！**
