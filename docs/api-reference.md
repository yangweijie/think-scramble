# API 参考

本文档提供 ThinkScramble 的完整 API 参考，包括所有类、方法和配置选项。

## 命令行接口

### scramble:generate

生成 OpenAPI 文档。

```bash
php think scramble:generate [options]
```

#### 选项

| 选项 | 简写 | 类型 | 默认值 | 说明 |
|------|------|------|--------|------|
| `--output` | `-o` | string | - | 输出文件路径 |
| `--format` | `-f` | string | `json` | 输出格式 (json\|yaml) |
| `--pretty` | `-p` | flag | false | 美化 JSON 输出 |
| `--config` | `-c` | string | - | 自定义配置文件路径 |
| `--force` | - | flag | false | 强制覆盖现有文件 |
| `--validate` | - | flag | false | 验证生成的文档 |
| `--quiet` | `-q` | flag | false | 静默模式 |

#### 示例

```bash
# 基本用法
php think scramble:generate

# 指定输出文件和格式
php think scramble:generate --output=api.yaml --format=yaml --pretty

# 强制覆盖并验证
php think scramble:generate --force --validate
```

### scramble:export

导出 API 文档到各种格式。

```bash
php think scramble:export --format=<format> [options]
```

#### 选项

| 选项 | 简写 | 类型 | 默认值 | 说明 |
|------|------|------|--------|------|
| `--format` | `-f` | string | **必需** | 导出格式 |
| `--output` | `-o` | string | `exports` | 输出目录或文件路径 |
| `--title` | `-t` | string | - | 文档标题 |
| `--api-version` | - | string | - | API 版本 |
| `--template` | - | string | - | HTML 导出的自定义模板路径 |
| `--include-examples` | `-e` | flag | false | 包含请求/响应示例 |
| `--compress` | `-z` | flag | false | 压缩输出 |

#### 支持的格式

| 格式 | 说明 | 输出 |
|------|------|------|
| `json` | JSON 格式 | `.json` 文件 |
| `yaml` | YAML 格式 | `.yaml` 文件 |
| `html` | HTML 文档 | HTML 文件和资源 |
| `postman` | Postman 集合 | `.json` 文件 |
| `insomnia` | Insomnia 工作空间 | `.json` 文件 |

#### 示例

```bash
# 导出为 HTML
php think scramble:export --format=html --output=public/docs

# 导出为 Postman 集合
php think scramble:export --format=postman --title="My API" --include-examples

# 导出为压缩的 HTML
php think scramble:export --format=html --compress
```

## 核心类

### ScrambleServiceProvider

服务提供者类，负责注册 ThinkScramble 服务。

```php
namespace Yangweijie\ThinkScramble\Service;

class ScrambleServiceProvider extends \think\Service
{
    public function register(): void
    public function boot(): void
}
```

### ScrambleConfig

配置管理类。

```php
namespace Yangweijie\ThinkScramble\Config;

class ScrambleConfig
{
    public function __construct(array $config = [])
    public function get(string $key, $default = null)
    public function set(string $key, $value): void
    public function all(): array
    public function merge(array $config): void
}
```

#### 方法

##### `get(string $key, $default = null)`

获取配置值。

```php
$config = new ScrambleConfig();
$apiPath = $config->get('api_path', 'api');
$cacheEnabled = $config->get('cache.enabled', true);
```

##### `set(string $key, $value): void`

设置配置值。

```php
$config->set('api_path', 'v1/api');
$config->set('cache.enabled', false);
```

### RouteAnalyzer

路由分析器，负责分析 ThinkPHP 路由并提取 API 信息。

```php
namespace Yangweijie\ThinkScramble\Adapter;

class RouteAnalyzer
{
    public function __construct(?App $app = null)
    public function analyzeRoutes(): array
    public function analyzeResourceRoute(string $resource): array
    public function getRouteMiddleware(string $route): array
    public function isApiRoute(array $routeInfo): bool
    public function getApplications(): array
    public function clearCache(): void
}
```

#### 方法

##### `analyzeRoutes(): array`

分析所有路由并返回 API 路由信息。

```php
$analyzer = new RouteAnalyzer();
$routes = $analyzer->analyzeRoutes();

// 返回格式
[
    [
        'method' => 'GET',
        'uri' => '/api/users',
        'controller' => 'app\\controller\\UserController',
        'action' => 'index',
        'middleware' => ['web'],
        'domain' => '',
    ],
    // ...
]
```

##### `isApiRoute(array $routeInfo): bool`

检查路由是否为 API 路由。

```php
$isApi = $analyzer->isApiRoute([
    'rule' => '/api/users',
    'middleware' => ['api'],
]);
```

### OpenApiGenerator

OpenAPI 文档生成器。

```php
namespace Yangweijie\ThinkScramble\Generator;

class OpenApiGenerator
{
    public function __construct(App $app, ScrambleConfig $config)
    public function generate(): array
    public function generateJson(bool $pretty = false): string
    public function generateYaml(): string
    public function getInfo(): array
    public function getServers(): array
    public function getPaths(): array
    public function getComponents(): array
}
```

#### 方法

##### `generate(): array`

生成完整的 OpenAPI 文档数组。

```php
$generator = new OpenApiGenerator($app, $config);
$document = $generator->generate();

// 返回 OpenAPI 3.0 格式的数组
[
    'openapi' => '3.0.3',
    'info' => [...],
    'servers' => [...],
    'paths' => [...],
    'components' => [...],
]
```

##### `generateJson(bool $pretty = false): string`

生成 JSON 格式的文档。

```php
$json = $generator->generateJson(true); // 美化输出
```

##### `generateYaml(): string`

生成 YAML 格式的文档。

```php
$yaml = $generator->generateYaml();
```

### ControllerParser

控制器解析器，负责分析控制器方法并提取 API 信息。

```php
namespace Yangweijie\ThinkScramble\Adapter;

class ControllerParser
{
    public function __construct(ScrambleConfig $config)
    public function parseController(string $controller): array
    public function parseMethod(\ReflectionMethod $method): array
    public function extractParameters(\ReflectionMethod $method): array
    public function extractResponses(\ReflectionMethod $method): array
    public function extractSecurity(\ReflectionMethod $method): array
}
```

#### 方法

##### `parseController(string $controller): array`

解析控制器并返回所有方法的 API 信息。

```php
$parser = new ControllerParser($config);
$methods = $parser->parseController('app\\controller\\UserController');
```

##### `parseMethod(\ReflectionMethod $method): array`

解析单个方法。

```php
$reflection = new \ReflectionMethod('app\\controller\\UserController', 'index');
$methodInfo = $parser->parseMethod($reflection);
```

## 配置接口

### 配置键参考

#### 基本配置

| 键 | 类型 | 默认值 | 说明 |
|---|------|--------|------|
| `api_path` | string | `'api'` | API 路径前缀 |
| `api_domain` | string\|null | `null` | API 域名 |

#### 信息配置

| 键 | 类型 | 默认值 | 说明 |
|---|------|--------|------|
| `info.version` | string | `'1.0.0'` | API 版本 |
| `info.title` | string | `'API Documentation'` | API 标题 |
| `info.description` | string | `''` | API 描述 |
| `info.contact.name` | string | `''` | 联系人姓名 |
| `info.contact.email` | string | `''` | 联系人邮箱 |
| `info.contact.url` | string | `''` | 联系人 URL |
| `info.license.name` | string | `''` | 许可证名称 |
| `info.license.url` | string | `''` | 许可证 URL |

#### 服务器配置

| 键 | 类型 | 默认值 | 说明 |
|---|------|--------|------|
| `servers` | array | `[['url' => '/', 'description' => 'Development server']]` | 服务器列表 |

#### 路由配置

| 键 | 类型 | 默认值 | 说明 |
|---|------|--------|------|
| `routes.prefix` | string | `'docs'` | 文档路由前缀 |
| `routes.middleware` | array | `['web']` | 文档路由中间件 |
| `routes.domain` | string\|null | `null` | 文档路由域名 |

#### 输出配置

| 键 | 类型 | 默认值 | 说明 |
|---|------|--------|------|
| `output.default_path` | string | `'public/docs'` | 默认输出路径 |
| `output.default_filename` | string | `'api-docs.json'` | 默认文件名 |
| `output.html_path` | string | `'public/docs'` | HTML 输出路径 |
| `output.auto_create_directory` | boolean | `true` | 自动创建目录 |

#### 缓存配置

| 键 | 类型 | 默认值 | 说明 |
|---|------|--------|------|
| `cache.enabled` | boolean | `true` | 启用缓存 |
| `cache.ttl` | integer | `3600` | 缓存时间（秒） |
| `cache.key_prefix` | string | `'scramble_'` | 缓存键前缀 |

#### 安全配置

| 键 | 类型 | 默认值 | 说明 |
|---|------|--------|------|
| `security.default_schemes` | array | `[]` | 默认安全方案 |
| `security.schemes` | array | `[...]` | 安全方案定义 |

#### 调试配置

| 键 | 类型 | 默认值 | 说明 |
|---|------|--------|------|
| `debug.enabled` | boolean | `false` | 启用调试 |
| `debug.log_analysis` | boolean | `false` | 记录分析过程 |
| `debug.verbose_errors` | boolean | `false` | 详细错误信息 |

## 事件系统

### 可用事件

#### DocumentGenerating

文档生成开始时触发。

```php
// 监听事件
\think\facade\Event::listen('scramble.document.generating', function ($config) {
    // 修改配置或执行其他操作
});
```

#### DocumentGenerated

文档生成完成时触发。

```php
\think\facade\Event::listen('scramble.document.generated', function ($document) {
    // 处理生成的文档
});
```

#### RouteAnalyzing

路由分析开始时触发。

```php
\think\facade\Event::listen('scramble.route.analyzing', function ($routes) {
    // 处理路由信息
});
```

## 异常类

### GenerationException

文档生成异常。

```php
namespace Yangweijie\ThinkScramble\Exception;

class GenerationException extends \Exception
{
    // 文档生成过程中的异常
}
```

### AnalysisException

分析异常。

```php
namespace Yangweijie\ThinkScramble\Exception;

class AnalysisException extends \Exception
{
    // 代码分析过程中的异常
}
```

### ConfigurationException

配置异常。

```php
namespace Yangweijie\ThinkScramble\Exception;

class ConfigurationException extends \Exception
{
    // 配置错误异常
}
```

## 扩展接口

### AnalyzerInterface

分析器接口，用于扩展代码分析功能。

```php
namespace Yangweijie\ThinkScramble\Contracts;

interface AnalyzerInterface
{
    public function analyze($target): array;
    public function supports($target): bool;
}
```

### TransformerInterface

转换器接口，用于扩展文档转换功能。

```php
namespace Yangweijie\ThinkScramble\Contracts;

interface TransformerInterface
{
    public function transform(array $document): array;
    public function supports(string $format): bool;
}
```

## 使用示例

### 自定义配置

```php
// config/scramble.php
return [
    'api_path' => 'api/v1',
    'info' => [
        'title' => '我的 API',
        'version' => '2.0.0',
        'description' => '这是我的 API 文档',
    ],
    'servers' => [
        [
            'url' => 'https://api.example.com',
            'description' => '生产服务器',
        ],
    ],
    'security' => [
        'default_schemes' => ['bearerAuth'],
        'schemes' => [
            'bearerAuth' => [
                'type' => 'http',
                'scheme' => 'bearer',
                'bearerFormat' => 'JWT',
            ],
        ],
    ],
];
```

### 编程式使用

```php
use Yangweijie\ThinkScramble\Generator\OpenApiGenerator;
use Yangweijie\ThinkScramble\Config\ScrambleConfig;

// 创建配置
$config = new ScrambleConfig([
    'api_path' => 'api',
    'info' => [
        'title' => 'My API',
        'version' => '1.0.0',
    ],
]);

// 生成文档
$generator = new OpenApiGenerator(app(), $config);
$document = $generator->generate();

// 输出 JSON
echo $generator->generateJson(true);
```

---

**相关文档**: 
- [配置说明](configuration.md) - 详细的配置选项
- [使用教程](usage.md) - 实际使用示例
- [故障排除](troubleshooting.md) - 常见问题解决方案
