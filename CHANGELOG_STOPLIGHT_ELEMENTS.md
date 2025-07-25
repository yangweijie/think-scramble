# Stoplight Elements 集成更新日志

## 版本 2.1.0 - 2025-01-25

### 🎉 新增功能

#### 1. Stoplight Elements 支持
- **新增 Stoplight Elements 渲染器**：现代化的 API 文档界面
- **多布局支持**：侧边栏布局（`sidebar`）和堆叠布局（`stacked`）
- **响应式设计**：自动适配桌面端和移动端
- **内置 API 测试**：直接在文档中测试 API 接口

#### 2. 增强的 AssetPublisher 类
- **多渲染器支持**：同时支持 Stoplight Elements 和 Swagger UI
- **资源自动管理**：自动下载和管理 CDN 资源文件
- **渲染器检测**：智能检测可用的渲染器
- **HTML 模板生成**：提供预配置的 HTML 模板

#### 3. 改进的 DocsController
- **智能渲染器选择**：自动选择最佳可用渲染器
- **多种访问方式**：支持不同的文档访问路由
- **参数化配置**：通过 URL 参数控制渲染器和布局
- **错误处理增强**：更好的错误提示和降级处理

### 📁 新增文件

```
assets/swagger-ui/
├── elements-styles.min.css          # Stoplight Elements 样式文件 (301KB)
├── elements-web-components.min.js   # Stoplight Elements 组件 (2MB)
├── swagger-ui.css                   # Swagger UI 样式文件 (144KB)
└── swagger-ui-bundle.js             # Swagger UI 脚本文件 (1MB)

examples/
├── documentation_renderers.php      # 使用示例和自定义模板
└── routes_example.php              # 路由配置示例

docs/
└── DOCUMENTATION_RENDERERS.md      # 详细使用文档

tests/
└── AssetPublisherTest.php          # 单元测试和集成测试
```

### 🔧 API 变更

#### AssetPublisher 类新增方法

```php
// 获取 Stoplight Elements HTML 模板
public function getStoplightElementsHtml(string $apiDescriptionUrl, array $options = []): string

// 获取 Swagger UI HTML 模板  
public function getSwaggerUIHtml(string $apiDescriptionUrl, array $options = []): string

// 获取可用渲染器列表
public function getAvailableRenderers(): array

// 检查特定渲染器是否可用
public function isRendererAvailable(string $renderer): bool
```

#### DocsController 类新增方法

```php
// 使用 Stoplight Elements 显示文档
public function elements(): Response

// 使用 Swagger UI 显示文档
public function swagger(): Response

// 获取可用渲染器信息
public function renderers(): Response
```

### 🌐 新增路由

```php
// 基础路由
GET /docs/                    # 自动选择渲染器
GET /docs/elements           # Stoplight Elements
GET /docs/swagger            # Swagger UI
GET /docs/renderers          # 渲染器信息

// 参数化路由
GET /docs/ui?renderer=stoplight-elements&layout=sidebar
GET /docs/ui?renderer=stoplight-elements&layout=stacked
GET /docs/ui?renderer=swagger-ui
```

### 🎨 使用示例

#### 基本使用

```php
use Yangweijie\ThinkScramble\Service\AssetPublisher;

$assetPublisher = new AssetPublisher($app);

// 生成 Stoplight Elements 页面
$html = $assetPublisher->getStoplightElementsHtml('/docs/api.json', [
    'title' => 'My API Documentation',
    'layout' => 'sidebar',
    'router' => 'hash'
]);
```

#### 自定义样式

```php
// 创建带有自定义样式的文档页面
$html = <<<HTML
<!doctype html>
<html lang="zh-CN">
  <head>
    <title>自定义 API 文档</title>
    <link href="/swagger-ui/elements-styles.min.css" rel="stylesheet" />
    <style>
      body { 
        font-family: 'PingFang SC', sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      }
    </style>
    <script src="/swagger-ui/elements-web-components.min.js"></script>
  </head>
  <body>
    <elements-api
      apiDescriptionUrl="/docs/api.json"
      router="hash"
      layout="sidebar"
    />
  </body>
</html>
HTML;
```

### 🔄 向后兼容性

- **完全向后兼容**：现有的 Swagger UI 功能保持不变
- **自动降级**：如果 Stoplight Elements 不可用，自动使用 Swagger UI
- **配置保持**：现有配置和路由继续有效

### 📊 性能优化

- **资源缓存**：资源文件只在更新时重新复制
- **智能检测**：避免重复的文件系统检查
- **按需加载**：只加载所需的渲染器资源

### 🛠️ 开发体验改进

- **类型提示**：完整的 PHP 类型声明
- **错误处理**：详细的错误信息和建议
- **文档完善**：详细的使用文档和示例
- **测试覆盖**：完整的单元测试和集成测试

### 🔍 故障排除

#### 常见问题

1. **资源文件未找到**
   ```php
   // 检查并重新发布资源
   $assetPublisher->forcePublishAssets();
   ```

2. **渲染器不可用**
   ```php
   // 检查可用性
   $available = $assetPublisher->isRendererAvailable('stoplight-elements');
   ```

3. **文档页面无法加载**
   - 确保 `/docs/api.json` 可访问
   - 检查静态文件服务配置
   - 验证资源文件权限

### 📈 未来计划

- [ ] 支持更多渲染器（Redoc、RapiDoc 等）
- [ ] 主题自定义功能
- [ ] 文档版本管理
- [ ] 多语言支持
- [ ] 性能监控和分析

### 🙏 致谢

感谢 [Stoplight Elements](https://github.com/stoplightio/elements) 项目提供的优秀开源工具。

---

## 升级指南

### 从 v2.0.x 升级到 v2.1.0

1. **更新代码**：拉取最新代码
2. **发布资源**：运行资源发布命令或访问任意文档页面自动发布
3. **测试功能**：访问 `/docs/renderers` 检查渲染器状态
4. **可选配置**：根据需要调整路由和中间件配置

### 配置建议

```php
// 在应用启动时确保资源已发布
$assetPublisher = new AssetPublisher($app);
if (!$assetPublisher->areAssetsPublished()) {
    $assetPublisher->publishAssets();
}
```

### 性能建议

- 在生产环境中预先发布资源文件
- 配置 Web 服务器缓存静态资源
- 考虑使用 CDN 加速资源加载
