<?php

/**
 * API 文档路由配置示例
 * 
 * 展示如何配置不同的文档访问路由
 */

use think\facade\Route;

// 基础文档路由组
Route::group('docs', function () {
    
    // 默认文档页面 - 自动选择最佳渲染器
    Route::get('/', 'DocsController@ui');
    
    // 指定渲染器的文档页面
    Route::get('ui', 'DocsController@ui'); // 支持 ?renderer=stoplight-elements&layout=sidebar
    
    // 直接访问特定渲染器
    Route::get('elements', 'DocsController@elements'); // Stoplight Elements
    Route::get('swagger', 'DocsController@swagger');   // Swagger UI
    
    // API 规范文件
    Route::get('api.json', 'DocsController@json');     // JSON 格式
    Route::get('api.yaml', 'DocsController@yaml');     // YAML 格式
    
    // 管理和信息接口
    Route::get('renderers', 'DocsController@renderers'); // 获取可用渲染器信息
    Route::get('test', 'DocsController@test');           // 测试接口
    
})->middleware(['api']); // 可选：添加中间件

/**
 * 使用示例：
 * 
 * 1. 默认文档页面（自动选择渲染器）：
 *    GET /docs/
 * 
 * 2. 使用 Stoplight Elements（侧边栏布局）：
 *    GET /docs/elements
 *    GET /docs/ui?renderer=stoplight-elements&layout=sidebar
 * 
 * 3. 使用 Stoplight Elements（堆叠布局）：
 *    GET /docs/elements?layout=stacked
 *    GET /docs/ui?renderer=stoplight-elements&layout=stacked
 * 
 * 4. 使用 Swagger UI：
 *    GET /docs/swagger
 *    GET /docs/ui?renderer=swagger-ui
 * 
 * 5. 获取 API 规范：
 *    GET /docs/api.json
 *    GET /docs/api.yaml
 * 
 * 6. 检查可用渲染器：
 *    GET /docs/renderers
 */

// 高级路由配置示例
Route::group('api-docs', function () {
    
    // 版本化的文档路由
    Route::group('v1', function () {
        Route::get('/', 'V1DocsController@ui');
        Route::get('elements', 'V1DocsController@elements');
        Route::get('swagger', 'V1DocsController@swagger');
        Route::get('spec.json', 'V1DocsController@json');
    });
    
    Route::group('v2', function () {
        Route::get('/', 'V2DocsController@ui');
        Route::get('elements', 'V2DocsController@elements');
        Route::get('swagger', 'V2DocsController@swagger');
        Route::get('spec.json', 'V2DocsController@json');
    });
    
})->middleware(['auth:optional']); // 可选认证

// 自定义文档路由示例
Route::group('custom-docs', function () {
    
    // 自定义样式的文档页面
    Route::get('branded', function () {
        $app = app();
        $assetPublisher = new \Yangweijie\ThinkScramble\Service\AssetPublisher($app);
        
        if (!$assetPublisher->isRendererAvailable('stoplight-elements')) {
            return response('Stoplight Elements not available', 404);
        }
        
        // 自定义 HTML 模板
        $html = <<<HTML
<!doctype html>
<html lang="zh-CN">
  <head>
    <meta charset="utf-8" />
    <title>我的 API 文档</title>
    <link href="/swagger-ui/elements-styles.min.css" rel="stylesheet" />
    <style>
      body {
        font-family: 'PingFang SC', 'Microsoft YaHei', sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        margin: 0;
        padding: 20px;
      }
      .docs-container {
        max-width: 1200px;
        margin: 0 auto;
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        overflow: hidden;
      }
      .header {
        background: #2c3e50;
        color: white;
        padding: 20px;
        text-align: center;
      }
      .header h1 {
        margin: 0;
        font-size: 2em;
      }
      elements-api {
        display: block;
        height: 80vh;
      }
    </style>
    <script src="/swagger-ui/elements-web-components.min.js"></script>
  </head>
  <body>
    <div class="docs-container">
      <div class="header">
        <h1>🚀 我的 API 文档</h1>
      </div>
      <elements-api
        apiDescriptionUrl="/docs/api.json"
        router="hash"
        layout="sidebar"
        tryItCredentialsPolicy="same-origin"
      />
    </div>
  </body>
</html>
HTML;
        
        return response($html)->header('Content-Type', 'text/html');
    });
    
    // 移动端优化的文档页面
    Route::get('mobile', function () {
        $app = app();
        $controller = new \Yangweijie\ThinkScramble\Controller\DocsController($app);
        
        // 强制使用堆叠布局，适合移动端
        $generator = new \Yangweijie\ThinkScramble\Generator\OpenApiGenerator($app, new \Yangweijie\ThinkScramble\Config\ScrambleConfig());
        $document = $generator->generate();
        
        $html = $controller->generateHtml($document, 'stoplight-elements', 'stacked');
        return response($html)->header('Content-Type', 'text/html');
    });
    
});

// 管理员路由示例
Route::group('admin/docs', function () {
    
    // 重新发布资源文件
    Route::post('republish-assets', function () {
        $app = app();
        $assetPublisher = new \Yangweijie\ThinkScramble\Service\AssetPublisher($app);
        
        $success = $assetPublisher->forcePublishAssets();
        
        return json([
            'success' => $success,
            'message' => $success ? '资源文件重新发布成功' : '资源文件重新发布失败',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    });
    
    // 检查资源状态
    Route::get('asset-status', function () {
        $app = app();
        $assetPublisher = new \Yangweijie\ThinkScramble\Service\AssetPublisher($app);
        
        $renderers = $assetPublisher->getAvailableRenderers();
        $status = [];
        
        foreach ($renderers as $key => $renderer) {
            $status[$key] = [
                'name' => $renderer['name'],
                'available' => $assetPublisher->isRendererAvailable($key),
                'files' => $renderer['files']
            ];
        }
        
        return json([
            'assets_published' => $assetPublisher->areAssetsPublished(),
            'renderers' => $status
        ]);
    });
    
})->middleware(['admin']); // 需要管理员权限

/**
 * 中间件示例
 */

// 文档访问日志中间件
class DocsAccessMiddleware
{
    public function handle($request, \Closure $next)
    {
        // 记录文档访问日志
        $userAgent = $request->header('User-Agent');
        $ip = $request->ip();
        $path = $request->pathinfo();
        
        // 记录到日志
        trace("API Docs Access: {$ip} - {$path} - {$userAgent}", 'info');
        
        return $next($request);
    }
}

// 文档缓存中间件
class DocsCacheMiddleware
{
    public function handle($request, \Closure $next)
    {
        $response = $next($request);
        
        // 为静态文档页面添加缓存头
        if ($response->getHeader('Content-Type') === 'text/html') {
            $response->header([
                'Cache-Control' => 'public, max-age=3600', // 缓存1小时
                'Expires' => gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT'
            ]);
        }
        
        return $response;
    }
}
