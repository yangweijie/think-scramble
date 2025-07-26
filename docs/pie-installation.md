# ThinkScramble PIE 安装指南

PIE (PHP Installer for Extensions) 是一个现代化的 PHP 包管理工具，ThinkScramble 现在完全支持通过 PIE 进行安装和管理。

## 🥧 什么是 PIE？

PIE 是一个专门为 PHP 扩展和工具设计的安装器，提供：

- 🚀 快速安装和卸载
- 🔄 自动更新管理
- 📊 安装状态检查
- 🛠️ 依赖管理
- 🌍 跨平台支持

## 📦 安装 PIE

首先需要安装 PIE 工具：

### 通过 Composer 全局安装

```bash
composer global require pie/pie
```

### 通过 PHAR 安装

```bash
# 下载 PIE PHAR
curl -L https://github.com/pie-framework/pie/releases/latest/download/pie.phar -o pie.phar
chmod +x pie.phar
sudo mv pie.phar /usr/local/bin/pie

# 验证安装
pie --version
```

## 🚀 使用 PIE 安装 ThinkScramble

### 基本安装

```bash
# 安装最新版本
pie install yangweijie/think-scramble

# 安装指定版本
pie install yangweijie/think-scramble:1.4.0

# 从 GitHub 安装开发版本
pie install yangweijie/think-scramble:dev-main
```

### 安装选项

```bash
# 全局安装（推荐）
pie install --global yangweijie/think-scramble

# 本地安装到项目
pie install --local yangweijie/think-scramble

# 强制重新安装
pie install --force yangweijie/think-scramble

# 安装时显示详细信息
pie install --verbose yangweijie/think-scramble
```

## 🔧 PIE 命令

### 安装管理

```bash
# 安装
pie install yangweijie/think-scramble

# 卸载
pie uninstall yangweijie/think-scramble

# 更新到最新版本
pie update yangweijie/think-scramble

# 检查安装状态
pie status yangweijie/think-scramble
```

### 信息查询

```bash
# 列出已安装的包
pie list

# 搜索包
pie search think-scramble

# 显示包信息
pie info yangweijie/think-scramble

# 显示包的依赖
pie depends yangweijie/think-scramble
```

### 配置管理

```bash
# 显示 PIE 配置
pie config

# 设置配置项
pie config set install-path /usr/local/bin

# 显示安装路径
pie config get install-path
```

## 📋 安装后配置

### 自动配置

PIE 安装后会自动执行以下配置：

1. **创建配置目录**: `~/.think-scramble/`
2. **生成默认配置**: `~/.think-scramble/config.php`
3. **创建缓存目录**: `/tmp/think-scramble-cache/`
4. **添加 Shell 补全**: 支持 bash 和 zsh
5. **创建示例配置**: `~/.think-scramble/example-project.php`

### 手动配置

如果需要自定义配置：

```bash
# 编辑全局配置
nano ~/.think-scramble/config.php

# 复制示例配置到项目
cp ~/.think-scramble/example-project.php /path/to/project/scramble.php
```

## 🎯 使用示例

### 基本用法

```bash
# 安装完成后直接使用
scramble --version
scramble --help

# 生成 API 文档
cd /path/to/thinkphp/project
scramble --output=api.json

# 包含中间件分析
scramble --output=api.json --middleware
```

### 高级功能

```bash
# 导出多种格式
scramble --format=postman --output=api.postman.json
scramble --format=insomnia --output=api.insomnia.json

# 实时监控
scramble --watch --output=api.json

# 性能统计
scramble --stats

# 配置验证
scramble --validate
```

## 🔄 更新和维护

### 检查更新

```bash
# 检查是否有新版本
pie outdated yangweijie/think-scramble

# 查看更新日志
pie changelog yangweijie/think-scramble
```

### 更新操作

```bash
# 更新到最新版本
pie update yangweijie/think-scramble

# 更新到指定版本
pie update yangweijie/think-scramble:1.5.0

# 更新所有已安装的包
pie update-all
```

### 回滚版本

```bash
# 回滚到之前版本
pie rollback yangweijie/think-scramble

# 安装特定版本
pie install yangweijie/think-scramble:1.3.0 --force
```

## 🛠️ 故障排除

### 常见问题

#### 1. PIE 命令未找到

```bash
# 检查 PIE 是否正确安装
which pie

# 重新安装 PIE
composer global require pie/pie

# 确保 Composer 全局 bin 目录在 PATH 中
echo $PATH | grep composer
```

#### 2. 权限问题

```bash
# Linux/macOS - 使用 sudo
sudo pie install yangweijie/think-scramble

# 或者安装到用户目录
pie install --user yangweijie/think-scramble
```

#### 3. 网络问题

```bash
# 使用代理
pie install --proxy=http://proxy.example.com:8080 yangweijie/think-scramble

# 使用镜像源
pie config set repository https://mirrors.example.com/pie
```

#### 4. 依赖冲突

```bash
# 强制安装
pie install --force yangweijie/think-scramble

# 忽略依赖检查
pie install --no-deps yangweijie/think-scramble
```

### 调试模式

```bash
# 启用详细输出
pie install --verbose yangweijie/think-scramble

# 启用调试模式
pie install --debug yangweijie/think-scramble

# 查看安装日志
pie log yangweijie/think-scramble
```

## 📊 PIE vs 其他安装方式

| 特性 | PIE | Composer | 手动安装 | PHAR |
|------|-----|----------|----------|------|
| 安装速度 | ⚡ 快 | 🐌 慢 | 🔧 手动 | ⚡ 快 |
| 依赖管理 | ✅ 自动 | ✅ 自动 | ❌ 手动 | ❌ 无 |
| 更新管理 | ✅ 自动 | ✅ 自动 | ❌ 手动 | ❌ 手动 |
| 全局安装 | ✅ 支持 | ✅ 支持 | ✅ 支持 | ✅ 支持 |
| 版本管理 | ✅ 完整 | ✅ 完整 | ❌ 有限 | ❌ 无 |
| 配置管理 | ✅ 自动 | ❌ 手动 | ❌ 手动 | ❌ 手动 |

## 🌟 PIE 的优势

### 1. 简化的安装流程

```bash
# 一条命令完成所有配置
pie install yangweijie/think-scramble
```

### 2. 智能依赖管理

- 自动解析 PHP 版本要求
- 检查扩展依赖
- 处理版本冲突

### 3. 完整的生命周期管理

- 安装前检查
- 安装后配置
- 卸载前清理
- 自动更新

### 4. 跨平台兼容

- Linux/macOS/Windows 统一体验
- 自动适配系统环境
- 智能路径处理

## 📚 更多资源

### 官方文档

- [PIE 官方网站](https://pie-framework.org)
- [PIE GitHub](https://github.com/pie-framework/pie)
- [ThinkScramble GitHub](https://github.com/yangweijie/think-scramble)

### 社区支持

- [PIE 讨论区](https://github.com/pie-framework/pie/discussions)
- [ThinkScramble 讨论区](https://github.com/yangweijie/think-scramble/discussions)
- [问题反馈](https://github.com/yangweijie/think-scramble/issues)

### 贡献指南

- [如何贡献代码](../CONTRIBUTING.md)
- [开发环境搭建](../docs/development.md)
- [测试指南](../docs/testing.md)

---

🎉 **开始使用 PIE 安装 ThinkScramble，享受现代化的包管理体验！**
