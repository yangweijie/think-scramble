# ThinkScramble

> 🚀 ThinkPHP OpenAPI 文档生成器

ThinkScramble 是一个为 ThinkPHP 框架设计的自动 API 文档生成扩展包，支持 OpenAPI 3.0 规范。

## ✨ 特性

- 🎯 **零配置启动** - 安装即用，无需复杂配置
- 📝 **自动文档生成** - 无需手动编写 PHPDoc 注解
- ⚡ **高性能缓存** - 智能缓存机制，支持增量解析
- 🎨 **现代化 UI** - 支持 Swagger UI 和 Stoplight Elements
- 🛡️ **安全分析** - 自动识别中间件和安全方案
- 📊 **OpenAPI 3.0** - 完全符合 OpenAPI 3.0 规范
- 🔌 **插件系统** - 可扩展的插件架构
- 💻 **CLI 工具** - 强大的命令行工具
- 🥧 **PIE 支持** - 现代化包管理体验

## 🚀 安装

### PIE 安装（推荐）

```bash
# 安装 PIE
composer global require pie/pie

# 安装 ThinkScramble
pie install yangweijie/think-scramble

# 验证安装
scramble --version
```

### Composer 安装

```bash
composer require yangweijie/think-scramble
```

### PHAR 安装

```bash
# 下载 PHAR 文件
curl -L https://github.com/yangweijie/think-scramble/releases/latest/download/scramble.phar -o scramble.phar
chmod +x scramble.phar
sudo mv scramble.phar /usr/local/bin/scramble
```

## ⚡ 快速开始

### 1. 生成文档

```bash
# 基本用法
scramble --output=api.json

# 包含中间件分析
scramble --output=api.json --middleware

# 导出不同格式
scramble --format=postman --output=api.postman.json
scramble --format=insomnia --output=api.insomnia.json
```

### 2. 实时监控

```bash
# 监控文件变化
scramble --watch --output=api.json
```

### 3. 查看统计

```bash
# 显示统计信息
scramble --stats
```

## 📚 文档

- [📦 安装指南](installation.md) - 详细安装步骤
- [⚡ 快速开始](quickstart.md) - 5分钟上手指南
- [🥧 PIE 安装](pie-installation.md) - 现代化包管理
- [❓ 常见问题](faq.md) - 问题快速解答
- [📝 更新日志](changelog.md) - 版本更新记录

## 🎯 示例

### 控制器注解

```php
<?php

namespace app\controller;

/**
 * 用户管理
 * @tag Users
 */
class User
{
    /**
     * 获取用户列表
     * @summary 用户列表
     * @description 获取所有用户的分页列表
     */
    public function index()
    {
        return json(['code' => 200, 'data' => []]);
    }

    /**
     * 创建用户
     * @summary 创建新用户
     * @requestBody {
     *   "name": "string|required",
     *   "email": "string|required|email"
     * }
     */
    public function create()
    {
        return json(['code' => 201, 'message' => 'Created']);
    }
}
```

### 配置文件

```php
<?php
// scramble.php

return [
    'info' => [
        'title' => 'My API',
        'version' => '1.0.0',
        'description' => 'API documentation',
    ],
    
    'servers' => [
        ['url' => 'http://localhost:8000', 'description' => 'Development'],
        ['url' => 'https://api.example.com', 'description' => 'Production'],
    ],
    
    'security' => [
        'enabled_schemes' => ['BearerAuth', 'ApiKeyAuth'],
    ],
];
```

## 🔗 链接

- [GitHub 仓库](https://github.com/yangweijie/think-scramble)
- [Packagist](https://packagist.org/packages/yangweijie/think-scramble)
- [问题反馈](https://github.com/yangweijie/think-scramble/issues)
- [讨论区](https://github.com/yangweijie/think-scramble/discussions)

## 📄 许可证

MIT License

## 🙏 致谢

感谢所有为 ThinkScramble 做出贡献的开发者们！
