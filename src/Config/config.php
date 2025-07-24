<?php

declare(strict_types=1);

/**
 * Scramble 默认配置文件
 *
 * 定义 API 文档生成的各种配置选项
 */

// 简单的 env 函数实现
if (!function_exists('env')) {
    function env($key, $default = null) {
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }
}

return [
    /*
    |--------------------------------------------------------------------------
    | API 路径配置
    |--------------------------------------------------------------------------
    |
    | 默认情况下，所有以此路径开头的路由都会被添加到文档中。
    | 如果需要更改此行为，可以通过自定义路由解析器来实现。
    |
    */
    'api_path' => env('SCRAMBLE_API_PATH', 'api'),

    /*
    |--------------------------------------------------------------------------
    | API 域名配置
    |--------------------------------------------------------------------------
    |
    | API 的域名。默认情况下使用应用域名。
    | 这也是默认 API 路由匹配器的一部分。
    |
    */
    'api_domain' => env('SCRAMBLE_API_DOMAIN', null),

    /*
    |--------------------------------------------------------------------------
    | API 信息配置
    |--------------------------------------------------------------------------
    |
    | 定义 API 文档的基本信息，包括版本、标题、描述等。
    |
    */
    'info' => [
        /*
        | API 版本号
        */
        'version' => env('SCRAMBLE_API_VERSION', '1.0.0'),

        /*
        | API 标题
        */
        'title' => env('SCRAMBLE_API_TITLE', 'API Documentation'),

        /*
        | API 描述，支持 Markdown 格式
        */
        'description' => env('SCRAMBLE_API_DESCRIPTION', ''),

        /*
        | 联系信息
        */
        'contact' => [
            'name' => env('SCRAMBLE_CONTACT_NAME', ''),
            'email' => env('SCRAMBLE_CONTACT_EMAIL', ''),
            'url' => env('SCRAMBLE_CONTACT_URL', ''),
        ],

        /*
        | 许可证信息
        */
        'license' => [
            'name' => env('SCRAMBLE_LICENSE_NAME', ''),
            'url' => env('SCRAMBLE_LICENSE_URL', ''),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 服务器配置
    |--------------------------------------------------------------------------
    |
    | API 服务器列表。当为 null 时，服务器 URL 将从
    | scramble.api_path 和 scramble.api_domain 配置变量创建。
    |
    | 示例配置：
    | 'servers' => [
    |     'Local' => 'api',
    |     'Production' => 'https://api.example.com',
    | ],
    |
    */
    'servers' => null,

    /*
    |--------------------------------------------------------------------------
    | 中间件配置
    |--------------------------------------------------------------------------
    |
    | 访问 API 文档时应用的中间件列表。
    |
    */
    'middleware' => [
        'web',
    ],

    /*
    |--------------------------------------------------------------------------
    | 路由配置
    |--------------------------------------------------------------------------
    |
    | 文档路由的配置选项。
    |
    */
    'routes' => [
        /*
        | 文档 UI 路由路径
        */
        'ui' => env('SCRAMBLE_DOCS_UI_PATH', 'docs/api'),

        /*
        | OpenAPI JSON 文档路由路径
        */
        'json' => env('SCRAMBLE_DOCS_JSON_PATH', 'docs/api.json'),

        /*
        | 是否启用文档路由
        */
        'enabled' => env('SCRAMBLE_DOCS_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | 缓存配置
    |--------------------------------------------------------------------------
    |
    | 文档生成缓存的配置选项。
    |
    */
    'cache' => [
        /*
        | 是否启用缓存
        */
        'enabled' => env('SCRAMBLE_CACHE_ENABLED', true),

        /*
        | 缓存 TTL（秒）
        */
        'ttl' => env('SCRAMBLE_CACHE_TTL', 3600),

        /*
        | 缓存键前缀
        */
        'prefix' => env('SCRAMBLE_CACHE_PREFIX', 'scramble'),

        /*
        | 缓存存储驱动
        */
        'store' => env('SCRAMBLE_CACHE_STORE', 'default'),
    ],

    /*
    |--------------------------------------------------------------------------
    | 分析配置
    |--------------------------------------------------------------------------
    |
    | 代码分析相关的配置选项。
    |
    */
    'analysis' => [
        /*
        | 是否启用类型推断
        */
        'type_inference' => env('SCRAMBLE_TYPE_INFERENCE', true),

        /*
        | 是否分析注释
        */
        'parse_docblocks' => env('SCRAMBLE_PARSE_DOCBLOCKS', true),

        /*
        | 排除的路径模式
        */
        'exclude_paths' => [
            'vendor/*',
            'node_modules/*',
            'storage/*',
            'runtime/*',
        ],

        /*
        | 包含的文件扩展名
        */
        'include_extensions' => [
            'php',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 安全配置
    |--------------------------------------------------------------------------
    |
    | API 安全相关的配置。
    |
    */
    'security' => [
        /*
        | 默认安全方案
        */
        'default_schemes' => [],

        /*
        | 安全方案定义
        */
        'schemes' => [
            // 示例：Bearer Token
            // 'bearerAuth' => [
            //     'type' => 'http',
            //     'scheme' => 'bearer',
            //     'bearerFormat' => 'JWT',
            // ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 扩展配置
    |--------------------------------------------------------------------------
    |
    | 自定义扩展和转换器的配置。
    |
    */
    'extensions' => [
        /*
        | 文档转换器
        */
        'document_transformers' => [],

        /*
        | 操作转换器
        */
        'operation_transformers' => [],

        /*
        | 类型推断扩展
        */
        'type_inference_extensions' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | 输出配置
    |--------------------------------------------------------------------------
    |
    | 文档生成和输出相关的配置选项。
    |
    */
    'output' => [
        /*
        | 默认输出目录
        | 相对于项目根目录的路径
        */
        'default_path' => env('SCRAMBLE_OUTPUT_PATH', 'public/docs'),

        /*
        | 默认文件名
        */
        'default_filename' => env('SCRAMBLE_OUTPUT_FILENAME', 'api-docs.json'),

        /*
        | HTML 输出目录
        */
        'html_path' => env('SCRAMBLE_HTML_PATH', 'public/docs'),

        /*
        | 是否自动创建目录
        */
        'auto_create_directory' => env('SCRAMBLE_AUTO_CREATE_DIR', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | 调试配置
    |--------------------------------------------------------------------------
    |
    | 调试和开发相关的配置选项。
    |
    */
    'debug' => [
        /*
        | 是否启用调试模式
        */
        'enabled' => env('SCRAMBLE_DEBUG', false),

        /*
        | 是否记录分析过程
        */
        'log_analysis' => env('SCRAMBLE_LOG_ANALYSIS', false),

        /*
        | 是否显示详细错误信息
        */
        'verbose_errors' => env('SCRAMBLE_VERBOSE_ERRORS', false),
    ],
];
