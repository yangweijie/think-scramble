# 配置说明

本文档详细介绍 ThinkScramble 的所有配置选项。

## 配置文件位置

ThinkScramble 的配置文件位于：
- **主配置**: `config/scramble.php`
- **环境配置**: `.env` 文件中的 `SCRAMBLE_*` 变量

## 发布配置文件

```bash
php think vendor:publish --provider="Yangweijie\ThinkScramble\Service\ServiceProvider"
```

## 完整配置示例

```php
<?php
// config/scramble.php

return [
    /*
    |--------------------------------------------------------------------------
    | API 路径配置
    |--------------------------------------------------------------------------
    */
    'api_path' => env('SCRAMBLE_API_PATH', 'api'),
    'api_domain' => env('SCRAMBLE_API_DOMAIN', null),

    /*
    |--------------------------------------------------------------------------
    | API 信息配置
    |--------------------------------------------------------------------------
    */
    'info' => [
        'version' => env('SCRAMBLE_API_VERSION', '1.0.0'),
        'title' => env('SCRAMBLE_API_TITLE', 'API Documentation'),
        'description' => env('SCRAMBLE_API_DESCRIPTION', ''),
        'contact' => [
            'name' => env('SCRAMBLE_CONTACT_NAME', ''),
            'email' => env('SCRAMBLE_CONTACT_EMAIL', ''),
            'url' => env('SCRAMBLE_CONTACT_URL', ''),
        ],
        'license' => [
            'name' => env('SCRAMBLE_LICENSE_NAME', ''),
            'url' => env('SCRAMBLE_LICENSE_URL', ''),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 服务器配置
    |--------------------------------------------------------------------------
    */
    'servers' => [
        [
            'url' => env('SCRAMBLE_SERVER_URL', '/'),
            'description' => env('SCRAMBLE_SERVER_DESC', 'Development server'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 路由配置
    |--------------------------------------------------------------------------
    */
    'routes' => [
        'prefix' => env('SCRAMBLE_ROUTE_PREFIX', 'docs'),
        'middleware' => ['web'],
        'domain' => env('SCRAMBLE_ROUTE_DOMAIN', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | 输出配置
    |--------------------------------------------------------------------------
    */
    'output' => [
        'default_path' => env('SCRAMBLE_OUTPUT_PATH', 'public/docs'),
        'default_filename' => env('SCRAMBLE_OUTPUT_FILENAME', 'api-docs.json'),
        'html_path' => env('SCRAMBLE_HTML_PATH', 'public/docs'),
        'auto_create_directory' => env('SCRAMBLE_AUTO_CREATE_DIR', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | 缓存配置
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => env('SCRAMBLE_CACHE_ENABLED', true),
        'ttl' => env('SCRAMBLE_CACHE_TTL', 3600),
        'key_prefix' => env('SCRAMBLE_CACHE_PREFIX', 'scramble_'),
    ],

    /*
    |--------------------------------------------------------------------------
    | 安全配置
    |--------------------------------------------------------------------------
    */
    'security' => [
        'default_schemes' => [],
        'schemes' => [
            'bearerAuth' => [
                'type' => 'http',
                'scheme' => 'bearer',
                'bearerFormat' => 'JWT',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 调试配置
    |--------------------------------------------------------------------------
    */
    'debug' => [
        'enabled' => env('SCRAMBLE_DEBUG', false),
        'log_analysis' => env('SCRAMBLE_LOG_ANALYSIS', false),
        'verbose_errors' => env('SCRAMBLE_VERBOSE_ERRORS', false),
    ],
];
```

## 配置选项详解

### API 路径配置

#### `api_path`
- **类型**: `string`
- **默认值**: `'api'`
- **说明**: API 路由的前缀路径，只有匹配此前缀的路由才会被包含在文档中

```php
'api_path' => 'api', // 匹配 /api/* 路由
```

#### `api_domain`
- **类型**: `string|null`
- **默认值**: `null`
- **说明**: API 的域名限制，为 null 时不限制域名

```php
'api_domain' => 'api.example.com', // 只处理此域名的路由
```

### API 信息配置

#### `info.version`
- **类型**: `string`
- **默认值**: `'1.0.0'`
- **说明**: API 版本号

#### `info.title`
- **类型**: `string`
- **默认值**: `'API Documentation'`
- **说明**: API 文档标题

#### `info.description`
- **类型**: `string`
- **默认值**: `''`
- **说明**: API 文档描述

#### `info.contact`
- **类型**: `array`
- **说明**: 联系信息

```php
'contact' => [
    'name' => 'API Support',
    'email' => 'support@example.com',
    'url' => 'https://example.com/support',
],
```

#### `info.license`
- **类型**: `array`
- **说明**: 许可证信息

```php
'license' => [
    'name' => 'MIT',
    'url' => 'https://opensource.org/licenses/MIT',
],
```

### 服务器配置

#### `servers`
- **类型**: `array`
- **说明**: API 服务器列表

```php
'servers' => [
    [
        'url' => 'https://api.example.com',
        'description' => 'Production server',
    ],
    [
        'url' => 'https://staging-api.example.com',
        'description' => 'Staging server',
    ],
],
```

### 路由配置

#### `routes.prefix`
- **类型**: `string`
- **默认值**: `'docs'`
- **说明**: 文档路由的前缀

#### `routes.middleware`
- **类型**: `array`
- **默认值**: `['web']`
- **说明**: 文档路由使用的中间件

```php
'middleware' => ['web', 'auth'], // 需要认证才能访问文档
```

#### `routes.domain`
- **类型**: `string|null`
- **默认值**: `null`
- **说明**: 文档路由的域名限制

### 输出配置

#### `output.default_path`
- **类型**: `string`
- **默认值**: `'public/docs'`
- **说明**: 默认输出目录

#### `output.default_filename`
- **类型**: `string`
- **默认值**: `'api-docs.json'`
- **说明**: 默认输出文件名

#### `output.auto_create_directory`
- **类型**: `boolean`
- **默认值**: `true`
- **说明**: 是否自动创建输出目录

### 缓存配置

#### `cache.enabled`
- **类型**: `boolean`
- **默认值**: `true`
- **说明**: 是否启用缓存

#### `cache.ttl`
- **类型**: `integer`
- **默认值**: `3600`
- **说明**: 缓存生存时间（秒）

#### `cache.key_prefix`
- **类型**: `string`
- **默认值**: `'scramble_'`
- **说明**: 缓存键前缀

### 安全配置

#### `security.default_schemes`
- **类型**: `array`
- **默认值**: `[]`
- **说明**: 默认安全方案

#### `security.schemes`
- **类型**: `array`
- **说明**: 安全方案定义

```php
'schemes' => [
    'bearerAuth' => [
        'type' => 'http',
        'scheme' => 'bearer',
        'bearerFormat' => 'JWT',
    ],
    'apiKey' => [
        'type' => 'apiKey',
        'in' => 'header',
        'name' => 'X-API-Key',
    ],
],
```

### 调试配置

#### `debug.enabled`
- **类型**: `boolean`
- **默认值**: `false`
- **说明**: 是否启用调试模式

#### `debug.log_analysis`
- **类型**: `boolean`
- **默认值**: `false`
- **说明**: 是否记录分析过程

#### `debug.verbose_errors`
- **类型**: `boolean`
- **默认值**: `false`
- **说明**: 是否显示详细错误信息

## 环境变量配置

在 `.env` 文件中配置：

```env
# API 基本信息
SCRAMBLE_API_TITLE="我的 API 文档"
SCRAMBLE_API_VERSION="2.0.0"
SCRAMBLE_API_DESCRIPTION="这是我的 API 文档描述"

# 服务器配置
SCRAMBLE_SERVER_URL="https://api.example.com"
SCRAMBLE_SERVER_DESC="生产服务器"

# 输出配置
SCRAMBLE_OUTPUT_PATH="public/api-docs"
SCRAMBLE_OUTPUT_FILENAME="openapi.json"

# 缓存配置
SCRAMBLE_CACHE_ENABLED=true
SCRAMBLE_CACHE_TTL=7200

# 调试配置
SCRAMBLE_DEBUG=false
SCRAMBLE_LOG_ANALYSIS=false
```

## 不同环境的配置

### 开发环境

```php
// config/scramble.php
return [
    'cache' => [
        'enabled' => false, // 禁用缓存以便实时更新
    ],
    'debug' => [
        'enabled' => true,
        'log_analysis' => true,
        'verbose_errors' => true,
    ],
    'routes' => [
        'middleware' => ['web'], // 无需认证
    ],
];
```

### 生产环境

```php
// config/scramble.php
return [
    'cache' => [
        'enabled' => true,
        'ttl' => 7200, // 2小时缓存
    ],
    'debug' => [
        'enabled' => false,
        'log_analysis' => false,
        'verbose_errors' => false,
    ],
    'routes' => [
        'middleware' => ['web', 'auth', 'admin'], // 需要管理员权限
    ],
];
```

## 配置验证

验证配置是否正确：

```bash
# 生成文档测试配置
php think scramble:generate --validate

# 检查路由配置
php think route:list | grep docs
```

## 常见配置问题

### 1. 文档无法访问

检查路由中间件配置：
```php
'routes' => [
    'middleware' => ['web'], // 确保中间件正确
],
```

### 2. 缓存问题

清除缓存：
```bash
php think clear
php think scramble:generate --force
```

### 3. 输出路径问题

确保目录权限：
```bash
chmod 755 public/docs/
```

---

**下一步**: 查看 [使用教程](usage.md) 学习如何使用配置好的 ThinkScramble。
