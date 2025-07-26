# ThinkScramble

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-blue.svg)](https://php.net/)
[![ThinkPHP Version](https://img.shields.io/badge/thinkphp-%3E%3D8.0-green.svg)](https://www.thinkphp.cn/)
[![License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE)
[![Tests](https://img.shields.io/badge/tests-passing-brightgreen.svg)](#testing)
[![Export Formats](https://img.shields.io/badge/export%20formats-15-orange.svg)](#导出格式详解)
[![UI Renderers](https://img.shields.io/badge/ui%20renderers-2-purple.svg)](#特色功能)

ThinkScramble 是一个为 ThinkPHP 6/8 框架设计的自动 API 文档生成扩展包，移植自 Laravel Scramble。它能够自动分析你的控制器代码，无需手动编写 PHPDoc 注解，即可生成符合 OpenAPI 3.0 规范的 API 文档。

## ✨ 特性

- 🚀 **零配置启动** - 安装即用，无需复杂配置
- 📝 **自动文档生成** - 无需手动编写 PHPDoc 注解
- 🎯 **ThinkPHP 原生支持** - 完全适配 ThinkPHP 8.0 架构
- 📊 **OpenAPI 3.0 标准** - 生成标准的 OpenAPI 文档
- 🎨 **现代化 UI** - 支持 Swagger UI 和 Stoplight Elements 双重界面
- ⚡ **高性能缓存** - 智能缓存机制，支持增量解析
- 🔒 **访问控制** - 灵活的文档访问权限控制
- 📤 **多格式导出** - 支持 15 种导出格式，覆盖主流 API 管理平台
- 🛠️ **命令行工具** - 丰富的 CLI 命令支持
- 🔍 **类型推断** - 智能的 PHP 类型分析引擎

## 📋 系统要求

- PHP >= 8.1
- ThinkPHP >= 8.0
- Composer

## 🚀 安装

### PIE 安装（推荐）

使用 PIE (PHP Installer for Extensions) 安装，享受现代化的包管理体验：

```bash
# 安装 PIE（如果尚未安装）
composer global require pie/pie

# 使用 PIE 安装 ThinkScramble
pie install yangweijie/think-scramble

# 验证安装
scramble --version
```

### Composer 安装

使用 Composer 安装扩展包：

```bash
composer require yangweijie/think-scramble
```

### PHAR 安装

下载预构建的 PHAR 文件：

```bash
# 下载最新版本
curl -L https://github.com/yangweijie/think-scramble/releases/latest/download/scramble.phar -o scramble.phar
chmod +x scramble.phar

# 全局安装
sudo mv scramble.phar /usr/local/bin/scramble

# 验证安装
scramble --version
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

访问以下地址查看生成的 API 文档：

#### 📱 Web 界面
- **默认界面**: `http://localhost:8000/docs/` (自动选择最佳渲染器)
- **Stoplight Elements**: `http://localhost:8000/docs/elements` (现代化界面，推荐)
- **Swagger UI**: `http://localhost:8000/docs/swagger` (经典界面)

#### 📄 API 规范文件
- **JSON 格式**: `http://localhost:8000/docs/api.json`
- **YAML 格式**: `http://localhost:8000/docs/api.yaml`

#### 🔧 管理接口
- **渲染器状态**: `http://localhost:8000/docs/renderers`

## ✅ 功能状态

### 已完成功能

- ✅ **命令行工具** - 完整的文档生成和导出命令
- ✅ **现代化 UI** - 支持 Swagger UI 和 Stoplight Elements 双重界面
- ✅ **多格式支持** - 15 种导出格式，覆盖主流 API 管理平台和测试工具
- ✅ **YAML 导出** - 内置 YAML 生成器，无需额外扩展
- ✅ **自动路由检测** - 智能分析 ThinkPHP 路由
- ✅ **资源管理** - 自动发布和管理静态资源文件
- ✅ **配置系统** - 灵活的配置选项
- ✅ **缓存支持** - 提高文档生成性能
- ✅ **错误处理** - 完善的异常处理机制

### 已完成功能

- ✅ **文件上传支持** - 自动识别和文档化文件上传参数
- ✅ **注解支持** - 完整的 think-annotation 兼容性
- ✅ **验证器集成** - 自动提取验证规则生成 OpenAPI 参数
- ✅ **模型分析** - 自动分析数据模型生成 Schema
- ✅ **中间件分析** - 安全方案自动检测和生成

### 高级功能

- ✅ **缓存优化** - 智能缓存机制提升分析性能
- ✅ **性能监控** - 文档生成性能分析和优化
- ✅ **插件系统** - 可扩展的插件架构支持自定义扩展
- ✅ **CLI 工具** - 命令行文档生成工具
- ✅ **实时更新** - 代码变更时自动更新文档
- ✅ **多格式导出** - 支持 Postman、Insomnia 等格式导出

## ⚙️ 配置

### 基本配置

ThinkScramble 使用内置的默认配置，开箱即用。如需自定义配置，可以创建 `config/scramble.php` 文件：

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

    // UI 配置
    'ui' => [
        'default_renderer' => 'auto', // auto, stoplight-elements, swagger-ui
        'layout' => 'sidebar',        // sidebar, stacked (仅 Stoplight Elements)
    ],

    // 导出配置
    'export' => [
        'default_format' => 'json',
        'include_examples' => true,
        'compress_output' => false,
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

ThinkScramble 支持 **15 种不同的导出格式**，覆盖主流 API 管理平台、测试工具和文档系统：

#### 标准格式
```bash
# OpenAPI 标准格式
php think scramble:export -f json
php think scramble:export -f yaml
php think scramble:export -f html
```

#### API 管理平台
```bash
# 主流 API 管理平台
php think scramble:export -f postman      # Postman 集合
php think scramble:export -f insomnia     # Insomnia 工作空间
php think scramble:export -f eolink       # Eolink 平台
php think scramble:export -f yapi         # YApi 平台
php think scramble:export -f apifox       # ApiFox 集合
php think scramble:export -f apipost      # ApiPost 集合
php think scramble:export -f rap          # RAP 平台
php think scramble:export -f showdoc      # ShowDoc 文档
```

#### 测试工具
```bash
# 性能测试和网络分析
php think scramble:export -f jmeter       # JMeter 测试计划
php think scramble:export -f har          # HTTP Archive
```

#### 文档和服务
```bash
# 文档生成和 Web 服务
php think scramble:export -f apidoc       # ApiDoc 格式
php think scramble:export -f wsdl         # WSDL 服务描述
```

#### 指定输出路径
```bash
# 自定义输出路径
php think scramble:export -f postman -o collections/api.json
php think scramble:export -f jmeter -o tests/testplan.jmx
php think scramble:export -f wsdl -o services/api.wsdl
```

## 📤 导出格式详解

ThinkScramble 支持 15 种不同的导出格式，满足各种使用场景：

### 🎯 使用场景对照表

| 使用场景 | 推荐格式 | 说明 |
|----------|----------|------|
| **开发阶段** | JSON, YAML, HTML | 标准格式，版本控制友好 |
| **API 测试** | Postman, Insomnia, ApiPost | 功能测试和调试 |
| **性能测试** | JMeter, HAR | 负载测试和网络分析 |
| **团队协作** | Eolink, YApi, RAP, ApiFox | 企业级 API 管理 |
| **文档发布** | HTML, ApiDoc, ShowDoc | 对外文档展示 |
| **企业集成** | WSDL, JSON | SOA 架构和系统集成 |

### 📋 格式特性对比

| 格式 | 文件类型 | 特点 | 适用工具/平台 |
|------|----------|------|---------------|
| **JSON** | .json | OpenAPI 标准，通用性强 | 各种 API 工具 |
| **YAML** | .yaml | 人类可读，配置友好 | 文档编写，CI/CD |
| **HTML** | .html | 可视化，交互式文档 | 浏览器查看 |
| **Postman** | .json | 支持测试脚本和环境变量 | Postman 客户端 |
| **Insomnia** | .json | 现代化界面，插件丰富 | Insomnia 客户端 |
| **Eolink** | .json | 企业级 API 管理 | Eolink 平台 |
| **JMeter** | .jmx | 性能测试，负载测试 | Apache JMeter |
| **YApi** | .json | 接口管理，Mock 数据 | YApi 平台 |
| **ApiDoc** | .json | 静态文档生成 | ApiDoc 工具 |
| **ApiPost** | .json | 国产工具，中文友好 | ApiPost 客户端 |
| **ApiFox** | .json | 设计优先，协作开发 | ApiFox 平台 |
| **HAR** | .har | 网络请求记录分析 | 浏览器开发者工具 |
| **RAP** | .json | 阿里开源，Mock 支持 | RAP 平台 |
| **WSDL** | .wsdl | SOAP 服务描述 | 企业 SOA 架构 |
| **ShowDoc** | .json | 简单易用，快速部署 | ShowDoc 平台 |

### 🚀 批量导出示例

```bash
#!/bin/bash
# 批量导出脚本

# 创建输出目录
mkdir -p exports/{collections,tests,docs,services}

# 导出标准格式
php think scramble:export -f json -o exports/api.json
php think scramble:export -f yaml -o exports/api.yaml
php think scramble:export -f html -o exports/docs/

# 导出 API 管理平台格式
php think scramble:export -f postman -o exports/collections/postman.json
php think scramble:export -f apifox -o exports/collections/apifox.json
php think scramble:export -f eolink -o exports/collections/eolink.json

# 导出测试工具格式
php think scramble:export -f jmeter -o exports/tests/testplan.jmx
php think scramble:export -f har -o exports/tests/requests.har

echo "批量导出完成！"
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

### 🔄 文件上传支持

支持多种文件上传注释格式和自动代码分析：

```php
/**
 * 上传用户头像
 *
 * @upload avatar required jpg,png,gif max:2MB 用户头像文件
 * @param string user_id 用户ID
 */
public function uploadAvatar(Request $request): Response
{
    $avatar = $request->file('avatar');  // 自动识别为文件上传参数
    $userId = $request->param('user_id');

    return json([
        'avatar_url' => '/uploads/avatar.jpg',
        'user_id' => $userId
    ]);
}

/**
 * 批量文件上传
 *
 * @file documents pdf,doc,docx max:50MB 文档文件
 * @param {file} images 图片文件
 */
public function batchUpload(Request $request): Response
{
    // 这些调用会被自动识别为文件上传参数
    $documents = $request->file('documents');
    $images = $request->file('images');

    return json(['success' => true]);
}
```

### 🏷️ 注解支持

完整支持 think-annotation 的所有注解类型：

```php
/**
 * 用户管理控制器
 *
 * @Route("/api/v1/users")
 * @Middleware("auth")
 */
class UserController
{
    /**
     * 获取用户列表
     *
     * @Get("")
     * @Middleware("throttle:60,1")
     * @Validate("UserValidate", scene="list")
     *
     * @Api {get} /api/v1/users 获取用户列表
     * @ApiParam {Number} page 页码
     * @ApiParam {String} keyword 搜索关键词
     * @ApiSuccess {Array} data.list 用户列表
     */
    public function index(Request $request): Response
    {
        // 自动应用中间件、验证规则，生成 OpenAPI 文档
        return json(['data' => ['list' => []]]);
    }

    /**
     * 创建用户
     *
     * @Post("")
     * @Validate("UserValidate", scene="create")
     *
     * @upload avatar jpg,png max:2MB 用户头像
     * @ApiParam {String} name 用户名
     * @ApiParam {String} email 邮箱
     */
    public function create(Request $request): Response
    {
        // 验证规则自动提取，文件上传自动识别
        return json(['message' => 'created'], 201);
    }
}
```

### 🏗️ 模型分析

自动分析 ThinkPHP 模型，生成精确的 OpenAPI Schema：

```php
/**
 * 用户模型
 *
 * @property int $id 用户ID
 * @property string $username 用户名
 * @property string $email 邮箱地址
 */
class UserModel extends Model
{
    protected $type = [
        'id' => 'integer',
        'username' => 'string',
        'email' => 'string',
        'age' => 'integer',
    ];

    protected $rule = [
        'username' => 'require|length:3,50',
        'email' => 'require|email',
        'age' => 'number|between:1,120',
    ];

    /**
     * 获取用户文章
     * @hasMany ArticleModel
     */
    public function articles()
    {
        return $this->hasMany(ArticleModel::class);
    }
}

// 控制器中使用
/**
 * @Get("/users/{id}")
 * @return UserModel 用户信息
 */
public function show(int $id): Response
{
    // 自动生成包含关联关系的完整 Schema
    return json(UserModel::with('articles')->find($id));
}
```

### 🛡️ 中间件分析

自动分析中间件配置，生成 OpenAPI 安全方案：

```php
/**
 * 安全控制器
 *
 * @middleware auth
 * @middleware throttle:60,1
 */
class SecureController
{
    /**
     * 获取用户信息
     * @Route("users/profile", method="GET")
     * @return Response
     */
    public function profile(): Response
    {
        // 自动生成安全要求：Bearer Token
        return json(['user' => 'data']);
    }

    /**
     * 管理员接口
     * @Route("admin/users", method="GET")
     * @middleware admin
     * @middleware audit:admin_access
     * @return Response
     */
    public function adminUsers(): Response
    {
        // 自动生成安全要求：Bearer Token + 管理员权限
        return json(['admin' => 'data']);
    }

    /**
     * API Key 保护的接口
     * @Route("api/data", method="GET")
     * @middleware api_key
     * @return Response
     */
    public function apiData(): Response
    {
        // 自动生成安全要求：API Key
        return json(['data' => 'protected']);
    }
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

### 核心文档
- [📦 安装指南](docs/installation.md) - 详细的安装步骤和系统要求
- [⚙️ 配置说明](docs/configuration.md) - 完整的配置选项参考
- [📖 使用教程](docs/usage.md) - 从入门到高级的使用指南
- [🔧 API 参考](docs/api-reference.md) - 完整的 API 和类参考
- [🚨 故障排除](docs/troubleshooting.md) - 常见问题和解决方案

### 功能文档
- [📤 导出格式指南](docs/EXPORT_FORMATS.md) - 15 种导出格式详细说明
- [🎨 文档渲染器](docs/DOCUMENTATION_RENDERERS.md) - Stoplight Elements 使用指南
- [📝 YAML 导出修复](docs/YAML_EXPORT_FIX.md) - YAML 导出功能说明

### 更新日志
- [🔄 Stoplight Elements 集成](CHANGELOG_STOPLIGHT_ELEMENTS.md)
- [📤 导出格式扩展](CHANGELOG_EXPORT_FORMATS.md)

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
