# ThinkScramble

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-blue.svg)](https://php.net/)
[![ThinkPHP Version](https://img.shields.io/badge/thinkphp-%3E%3D8.0-green.svg)](https://www.thinkphp.cn/)
[![License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE)
[![Tests](https://img.shields.io/badge/tests-passing-brightgreen.svg)](#testing)

ThinkScramble 是一个为 ThinkPHP 6/8 框架设计的自动 API 文档生成扩展包，移植自 Laravel Scramble。它能够自动分析你的控制器代码，无需手动编写 PHPDoc 注解，即可生成符合 OpenAPI 3.0 规范的 API 文档。

## ✨ 特性

- 🚀 **零配置启动** - 安装即用，无需复杂配置
- 📝 **自动文档生成** - 无需手动编写 PHPDoc 注解
- 🎯 **ThinkPHP 原生支持** - 完全适配 ThinkPHP 8.0 架构
- 📊 **OpenAPI 3.0 标准** - 生成标准的 OpenAPI 文档
- 🎨 **Swagger UI 集成** - 提供美观的 Web 界面
- ⚡ **高性能缓存** - 智能缓存机制，支持增量解析
- 🔒 **访问控制** - 灵活的文档访问权限控制
- 📤 **多格式导出** - 支持 JSON、YAML、HTML、Postman 等格式
- 🛠️ **命令行工具** - 丰富的 CLI 命令支持
- 🔍 **类型推断** - 智能的 PHP 类型分析引擎

## 📋 系统要求

- PHP >= 8.1
- ThinkPHP >= 8.0
- Composer

## 🚀 安装

使用 Composer 安装扩展包：

```bash
composer require yangweijie/think-scramble
```

### 自动发现

ThinkPHP 8.0 支持自动服务发现，安装后会自动注册服务。如果需要手动注册，请在 `config/service.php` 中添加：

```php
return [
    \Yangweijie\ThinkScramble\Service\ScrambleServiceProvider::class,
];
```

## 快速开始

### 1. 服务注册

安装后，运行服务发现命令：

```bash
php think service:discover
```

### 2. 发布配置文件

```bash
php think vendor:publish --provider="Yangweijie\ThinkScramble\Service\ServiceProvider"
```

### 3. 生成文档

```bash
php think scramble:generate
```

### 4. 访问文档

访问 `/docs/api` 查看生成的 API 文档。

## 配置

配置文件位于 `config/scramble.php`：

```php
<?php

return [
    // API 路径前缀
    'api_path' => 'api',
    
    // API 域名
    'api_domain' => null,
    
    // 文档信息
    'info' => [
        'version' => '1.0.0',
        'title' => 'API Documentation',
        'description' => '',
    ],
    
    // 服务器配置
    'servers' => [],
    
    // 中间件
    'middleware' => ['web'],
    
    // 缓存配置
    'cache' => [
        'enable' => true,
        'ttl' => 3600,
    ],
];
```

## 使用示例

### 控制器示例

```php
<?php

namespace app\controller;

use think\Request;
use think\Response;

class UserController
{
    /**
     * 获取用户列表
     */
    public function index(Request $request): Response
    {
        // Scramble 会自动分析这个方法并生成文档
        return json([
            'users' => [
                ['id' => 1, 'name' => 'John'],
                ['id' => 2, 'name' => 'Jane'],
            ]
        ]);
    }
    
    /**
     * 创建用户
     */
    public function save(Request $request): Response
    {
        // 自动识别请求参数和响应格式
        $data = $request->post();
        
        return json([
            'id' => 3,
            'name' => $data['name'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
```

## 🧪 测试

运行测试套件：

```bash
# 运行所有测试
composer test

# 运行单元测试
composer test:unit

# 运行集成测试
composer test:integration

# 生成测试覆盖率报告
composer test:coverage
```

## 📚 文档

- [安装指南](docs/installation.md)
- [配置说明](docs/configuration.md)
- [使用教程](docs/usage.md)
- [API 参考](docs/api-reference.md)
- [故障排除](docs/troubleshooting.md)

## 🤝 贡献

欢迎贡献代码！请查看 [贡献指南](CONTRIBUTING.md) 了解详细信息。

### 开发环境设置

```bash
# 克隆仓库
git clone https://github.com/yangweijie/think-scramble.git
cd think-scramble

# 安装依赖
composer install

# 运行测试
composer test
```

## 贡献

欢迎提交 Issue 和 Pull Request！

## 许可证

MIT License. 详见 [LICENSE](LICENSE) 文件。

## 致谢

本项目移植自 [dedoc/scramble](https://github.com/dedoc/scramble)，感谢原作者的优秀工作。
