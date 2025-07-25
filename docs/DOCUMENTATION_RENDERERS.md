# API 文档渲染器

本文档介绍如何使用 AssetPublisher 类来美化 API 文档渲染页面，支持多种现代化的文档渲染器。

## 支持的渲染器

### 1. Stoplight Elements

Stoplight Elements 是一个现代化的 API 文档渲染器，提供了优雅的界面和丰富的功能。

**特性：**
- 现代化的设计
- 支持多种布局（侧边栏、堆叠）
- 响应式设计
- 内置 API 测试功能
- 支持深色模式

**CDN 资源：**
- `elements-styles.min.css` - 样式文件
- `elements-web-components.min.js` - JavaScript 组件

### 2. Swagger UI

经典的 API 文档渲染器，广泛使用且功能稳定。

**特性：**
- 成熟稳定
- 广泛支持
- 内置 API 测试
- 可自定义主题

**CDN 资源：**
- `swagger-ui.css` - 样式文件
- `swagger-ui-bundle.js` - JavaScript 文件

## 安装和配置

### 1. 资源文件管理

AssetPublisher 类会自动管理所需的 CDN 资源文件：

```php
use Yangweijie\ThinkScramble\Service\AssetPublisher;

$assetPublisher = new AssetPublisher($app);

// 发布所有资源文件
$assetPublisher->publishAssets();

// 检查资源是否已发布
if ($assetPublisher->areAssetsPublished()) {
    echo "资源文件已就绪";
}

// 强制重新发布
$assetPublisher->forcePublishAssets();
```

### 2. 检查渲染器可用性

```php
// 检查 Stoplight Elements 是否可用
if ($assetPublisher->isRendererAvailable('stoplight-elements')) {
    // 可以使用 Stoplight Elements
}

// 检查 Swagger UI 是否可用
if ($assetPublisher->isRendererAvailable('swagger-ui')) {
    // 可以使用 Swagger UI
}

// 获取所有可用渲染器
$renderers = $assetPublisher->getAvailableRenderers();
```

## 使用方法

### 1. Stoplight Elements

#### 基本用法

```php
$html = $assetPublisher->getStoplightElementsHtml('/api/openapi.json');
return response($html)->header('Content-Type', 'text/html');
```

#### 自定义配置

```php
$html = $assetPublisher->getStoplightElementsHtml('/api/openapi.json', [
    'title' => 'My API Documentation',
    'layout' => 'sidebar',  // 或 'stacked'
    'router' => 'hash',     // 或 'memory', 'history'
    'tryItCredentialsPolicy' => 'same-origin'
]);
```

#### 布局选项

- **sidebar**: 侧边栏布局（默认），适合桌面端
- **stacked**: 堆叠布局，适合移动端和窄屏幕

### 2. Swagger UI

```php
$html = $assetPublisher->getSwaggerUIHtml('/api/openapi.json', [
    'title' => 'API Documentation'
]);
return response($html)->header('Content-Type', 'text/html');
```

## 路由配置示例

```php
// 在路由文件中
Route::group('docs', function () {
    // Stoplight Elements 文档页面
    Route::get('elements', function() {
        $assetPublisher = new AssetPublisher(app());
        
        if (!$assetPublisher->isRendererAvailable('stoplight-elements')) {
            return response('文档渲染器不可用', 404);
        }
        
        $html = $assetPublisher->getStoplightElementsHtml('/api/openapi.json', [
            'title' => 'API 文档',
            'layout' => 'sidebar'
        ]);
        
        return response($html)->header('Content-Type', 'text/html');
    });
    
    // Swagger UI 文档页面
    Route::get('swagger', function() {
        $assetPublisher = new AssetPublisher(app());
        
        if (!$assetPublisher->isRendererAvailable('swagger-ui')) {
            return response('文档渲染器不可用', 404);
        }
        
        $html = $assetPublisher->getSwaggerUIHtml('/api/openapi.json');
        return response($html)->header('Content-Type', 'text/html');
    });
});
```

## 自定义样式

你可以创建自定义的 HTML 模板来进一步美化文档页面：

```php
public function getCustomStoplightElementsHtml(string $apiUrl): string
{
    return <<<HTML
<!doctype html>
<html lang="zh-CN">
  <head>
    <meta charset="utf-8" />
    <title>自定义 API 文档</title>
    <link href="/swagger-ui/elements-styles.min.css" rel="stylesheet" />
    <style>
      /* 自定义样式 */
      body {
        font-family: 'PingFang SC', sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      }
      .docs-container {
        max-width: 1200px;
        margin: 20px auto;
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      }
    </style>
    <script src="/swagger-ui/elements-web-components.min.js"></script>
  </head>
  <body>
    <div class="docs-container">
      <elements-api
        apiDescriptionUrl="{$apiUrl}"
        router="hash"
        layout="sidebar"
      />
    </div>
  </body>
</html>
HTML;
}
```

## 最佳实践

1. **性能优化**: 资源文件会被缓存，只有在源文件更新时才会重新复制
2. **错误处理**: 始终检查渲染器是否可用，提供降级方案
3. **响应式设计**: 根据目标设备选择合适的布局
4. **自定义主题**: 使用 CSS 变量和自定义样式来匹配你的品牌

## 故障排除

### 资源文件未找到

```php
// 检查资源文件状态
$renderers = $assetPublisher->getAvailableRenderers();
foreach ($renderers as $name => $info) {
    $available = $assetPublisher->isRendererAvailable($name);
    echo "{$name}: " . ($available ? '可用' : '不可用') . "\n";
}

// 重新发布资源文件
$assetPublisher->forcePublishAssets();
```

### 文档页面无法加载

1. 确保 OpenAPI 规范文件可访问
2. 检查资源文件路径是否正确
3. 验证 Web 服务器配置允许访问静态文件

## 参考资源

- [Stoplight Elements 官方文档](https://github.com/stoplightio/elements)
- [Swagger UI 官方文档](https://swagger.io/tools/swagger-ui/)
- [OpenAPI 规范](https://spec.openapis.org/oas/v3.1.0/)
