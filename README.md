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

## 🚀 快速开始

### 1. 创建 API 控制器

```php
<?php
// app/controller/Api.php

namespace app\controller;

use think\Response;

class Api
{
    /**
     * 获取用户列表
     */
    public function users(): Response
    {
        $users = [
            ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
            ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com'],
        ];

        return json([
            'code' => 200,
            'message' => 'success',
            'data' => $users
        ]);
    }
}
```

### 2. 配置路由

```php
<?php
// route/app.php

use think\facade\Route;

Route::group('api', function () {
    Route::get('users', 'Api/users');
    Route::get('users/<id>', 'Api/user');
    Route::post('users', 'Api/createUser');
});
```

### 3. 生成文档

```bash
php think scramble:generate
```

### 4. 访问文档

启动开发服务器并访问文档：

```bash
php think run
```

访问 `http://localhost:8000/docs/api` 查看生成的 API 文档。

## ✅ 功能状态

### 已完成功能

- ✅ **命令行工具** - 完整的文档生成和导出命令
- ✅ **Web 界面** - 基于 Swagger UI 的文档界面
- ✅ **多格式支持** - JSON, YAML, HTML, Postman, Insomnia
- ✅ **自动路由检测** - 智能分析 ThinkPHP 路由
- ✅ **配置系统** - 灵活的配置选项
- ✅ **缓存支持** - 提高文档生成性能
- ✅ **错误处理** - 完善的异常处理机制

### 开发中功能

- 🚧 **注解支持** - 基于注释的文档增强
- 🚧 **模型分析** - 自动分析数据模型
- 🚧 **验证器集成** - 自动提取验证规则
- 🚧 **中间件分析** - 安全方案自动检测

### 计划功能

- 📋 **API 版本控制** - 多版本 API 文档支持
- 📋 **自定义模板** - 可定制的文档模板
- 📋 **实时预览** - 开发时实时文档更新
- 📋 **测试集成** - 自动生成 API 测试用例

## ⚙️ 配置

### 发布配置文件（可选）

```bash
php think vendor:publish --provider="Yangweijie\ThinkScramble\Service\ServiceProvider"
```

### 基本配置

配置文件位于 `config/scramble.php`：

```php
<?php

return [
    // API 路径前缀
    'api_path' => 'api',

    // 文档信息
    'info' => [
        'version' => '1.0.0',
        'title' => 'API Documentation',
        'description' => '自动生成的 API 文档',
    ],

    // 输出配置
    'output' => [
        'default_path' => 'public/docs',
        'auto_create_directory' => true,
    ],

    // 缓存配置
    'cache' => [
        'enabled' => true,
        'ttl' => 3600,
    ],
];
```

## 📖 命令行工具

### 生成文档

```bash
# 基本生成
php think scramble:generate

# 生成到指定位置
php think scramble:generate --output=public/api-docs.json

# 生成 YAML 格式
php think scramble:generate --format=yaml --pretty

# 强制覆盖现有文件
php think scramble:generate --force
```

### 导出文档

```bash
# 导出为 HTML
php think scramble:export --format=html

# 导出为 Postman 集合
php think scramble:export --format=postman

# 导出为 Insomnia 工作空间
php think scramble:export --format=insomnia
```

## 🌟 特色功能

### 自动类型推断

ThinkScramble 能够自动分析您的代码并推断参数类型和响应格式：

```php
public function createUser(Request $request): Response
{
    // 自动检测 POST 参数
    $name = $request->post('name');     // string
    $age = $request->post('age/d');     // integer
    $email = $request->post('email');   // string

    // 自动分析响应结构
    return json([
        'id' => 123,                    // integer
        'name' => $name,                // string
        'age' => $age,                  // integer
        'email' => $email,              // string
        'created_at' => date('c'),      // datetime
    ]);
}
```

### 支持验证器

```php
public function store(Request $request): Response
{
    // ThinkScramble 会分析验证规则并生成参数文档
    $validate = \think\facade\Validate::make([
        'name' => 'require|max:50',
        'email' => 'require|email|unique:user',
        'age' => 'integer|between:1,120',
    ]);

    if (!$validate->check($request->post())) {
        return json(['error' => $validate->getError()], 422);
    }

    // 处理逻辑...
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

- [📦 安装指南](docs/installation.md) - 详细的安装步骤和系统要求
- [⚙️ 配置说明](docs/configuration.md) - 完整的配置选项参考
- [📖 使用教程](docs/usage.md) - 从入门到高级的使用指南
- [🔧 API 参考](docs/api-reference.md) - 完整的 API 和类参考
- [🚨 故障排除](docs/troubleshooting.md) - 常见问题和解决方案

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
