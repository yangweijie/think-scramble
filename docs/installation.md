# 安装指南

本指南将帮助您在 ThinkPHP 项目中安装和配置 ThinkScramble。

## 系统要求

在开始安装之前，请确保您的系统满足以下要求：

### 必需要求

- **PHP**: >= 8.1
- **ThinkPHP**: >= 8.0
- **Composer**: 最新版本
- **扩展**: 
  - `json` (通常已内置)
  - `mbstring` (通常已内置)
  - `openssl` (通常已内置)

### 可选要求

- **Xdebug** 或 **PCOV**: 用于代码覆盖率分析
- **YAML 扩展**: 用于 YAML 格式导出

## 安装步骤

### 1. 使用 Composer 安装

在您的 ThinkPHP 项目根目录中运行：

```bash
composer require yangweijie/think-scramble
```

### 2. 验证安装

安装完成后，验证扩展包是否正确安装：

```bash
# 检查是否有 scramble 命令
php think list | grep scramble
```

您应该看到以下命令：
- `scramble:generate` - 生成 API 文档
- `scramble:export` - 导出 API 文档

### 3. 服务注册

ThinkPHP 8.0 支持自动服务发现，通常无需手动配置。如果自动发现失败，请手动注册服务：

#### 方法 1: 自动发现（推荐）

```bash
php think service:discover
```

#### 方法 2: 手动注册

在 `config/service.php` 中添加：

```php
<?php

return [
    \Yangweijie\ThinkScramble\Service\ScrambleServiceProvider::class,
];
```

### 4. 发布配置文件（可选）

如果需要自定义配置，可以发布配置文件：

```bash
php think vendor:publish --provider="Yangweijie\ThinkScramble\Service\ServiceProvider"
```

这将在 `config/` 目录下创建 `scramble.php` 配置文件。

## 验证安装

### 1. 生成测试文档

```bash
php think scramble:generate
```

如果安装成功，您应该看到类似以下的输出：

```
Starting API documentation generation...
Analyzing routes and controllers...
Generated documentation in 0.03ms
Found 0 API endpoints and 0 schemas
Documentation generated successfully: /path/to/your/project/public/docs/api-docs.json
```

### 2. 启动开发服务器

```bash
php think run
```

### 3. 访问文档界面

在浏览器中访问：`http://localhost:8000/docs/api`

您应该看到 Swagger UI 界面显示基本的 API 文档结构。

## 常见安装问题

### 问题 1: Composer 安装失败

**错误信息**: `Package yangweijie/think-scramble not found`

**解决方案**:
1. 确保您的 Composer 是最新版本：`composer self-update`
2. 清除 Composer 缓存：`composer clear-cache`
3. 检查网络连接和 Packagist 访问

### 问题 2: 服务注册失败

**错误信息**: `Class 'Yangweijie\ThinkScramble\Service\ScrambleServiceProvider' not found`

**解决方案**:
1. 运行 `composer dump-autoload`
2. 检查 `vendor/` 目录是否存在
3. 确认扩展包已正确安装

### 问题 3: 命令不可用

**错误信息**: `Command "scramble:generate" is not defined`

**解决方案**:
1. 运行 `php think service:discover`
2. 检查服务提供者是否正确注册
3. 清除应用缓存：`php think clear`

### 问题 4: 权限问题

**错误信息**: `Permission denied` 或文件写入失败

**解决方案**:
1. 确保 `public/docs/` 目录可写
2. 设置正确的文件权限：`chmod 755 public/docs/`
3. 检查 Web 服务器用户权限

## 开发环境配置

### 1. 启用调试模式

在 `.env` 文件中添加：

```env
SCRAMBLE_DEBUG=true
SCRAMBLE_LOG_ANALYSIS=true
SCRAMBLE_VERBOSE_ERRORS=true
```

### 2. 配置缓存

对于开发环境，建议禁用缓存以便实时查看更改：

```php
// config/scramble.php
return [
    'cache' => [
        'enabled' => false, // 开发环境禁用缓存
    ],
];
```

### 3. 设置输出路径

```php
// config/scramble.php
return [
    'output' => [
        'default_path' => 'public/docs',
        'auto_create_directory' => true,
    ],
];
```

## 生产环境配置

### 1. 启用缓存

```php
// config/scramble.php
return [
    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // 1小时
    ],
];
```

### 2. 访问控制

```php
// config/scramble.php
return [
    'middleware' => ['auth', 'admin'], // 添加认证中间件
];
```

### 3. 性能优化

```env
SCRAMBLE_DEBUG=false
SCRAMBLE_LOG_ANALYSIS=false
```

## 下一步

安装完成后，您可以：

1. 阅读 [配置说明](configuration.md) 了解详细配置选项
2. 查看 [使用教程](usage.md) 学习如何使用 ThinkScramble
3. 参考 [API 参考](api-reference.md) 了解所有可用功能

## 获取帮助

如果在安装过程中遇到问题：

1. 查看 [故障排除](troubleshooting.md) 文档
2. 在 GitHub 上提交 Issue
3. 查看项目文档和示例代码

---

**提示**: 建议在开发环境中先熟悉 ThinkScramble 的功能，然后再部署到生产环境。
