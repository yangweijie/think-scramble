<?php

declare(strict_types=1);

/**
 * 文档渲染器使用示例
 * 
 * 展示如何使用 AssetPublisher 类来生成不同风格的 API 文档页面
 */

use think\App;
use Yangweijie\ThinkScramble\Service\AssetPublisher;

// 假设在 ThinkPHP 控制器中使用
class DocumentationController
{
    protected AssetPublisher $assetPublisher;

    public function __construct(App $app)
    {
        $this->assetPublisher = new AssetPublisher($app);
        
        // 确保资源文件已发布
        if (!$this->assetPublisher->areAssetsPublished()) {
            $this->assetPublisher->publishAssets();
        }
    }

    /**
     * 使用 Stoplight Elements 渲染 API 文档
     */
    public function stoplightElements()
    {
        // 检查 Stoplight Elements 是否可用
        if (!$this->assetPublisher->isRendererAvailable('stoplight-elements')) {
            return response('Stoplight Elements 资源文件未找到', 404);
        }

        $html = $this->assetPublisher->getStoplightElementsHtml('/api/openapi.json', [
            'title' => 'My API Documentation',
            'layout' => 'sidebar', // 可选: sidebar, stacked
            'router' => 'hash',    // 可选: hash, memory, history
            'tryItCredentialsPolicy' => 'same-origin'
        ]);

        return response($html)->header('Content-Type', 'text/html');
    }

    /**
     * 使用 Stoplight Elements 的堆叠布局
     */
    public function stoplightElementsStacked()
    {
        if (!$this->assetPublisher->isRendererAvailable('stoplight-elements')) {
            return response('Stoplight Elements 资源文件未找到', 404);
        }

        $html = $this->assetPublisher->getStoplightElementsHtml('/api/openapi.json', [
            'title' => 'My API Documentation - Stacked Layout',
            'layout' => 'stacked', // 堆叠布局，更适合移动设备
            'router' => 'hash'
        ]);

        return response($html)->header('Content-Type', 'text/html');
    }

    /**
     * 使用传统的 Swagger UI 渲染 API 文档
     */
    public function swaggerUI()
    {
        if (!$this->assetPublisher->isRendererAvailable('swagger-ui')) {
            return response('Swagger UI 资源文件未找到', 404);
        }

        $html = $this->assetPublisher->getSwaggerUIHtml('/api/openapi.json', [
            'title' => 'My API Documentation - Swagger UI'
        ]);

        return response($html)->header('Content-Type', 'text/html');
    }

    /**
     * 获取可用的文档渲染器信息
     */
    public function availableRenderers()
    {
        $renderers = $this->assetPublisher->getAvailableRenderers();
        $status = [];

        foreach ($renderers as $key => $renderer) {
            $status[$key] = [
                'name' => $renderer['name'],
                'description' => $renderer['description'],
                'available' => $this->assetPublisher->isRendererAvailable($key),
                'files' => $renderer['files']
            ];
        }

        return json($status);
    }

    /**
     * 强制重新发布资源文件
     */
    public function republishAssets()
    {
        $success = $this->assetPublisher->forcePublishAssets();
        
        return json([
            'success' => $success,
            'message' => $success ? '资源文件重新发布成功' : '资源文件重新发布失败'
        ]);
    }
}

/**
 * 路由配置示例
 */
/*
Route::group('docs', function () {
    // Stoplight Elements 文档页面
    Route::get('elements', 'DocumentationController@stoplightElements');
    Route::get('elements-stacked', 'DocumentationController@stoplightElementsStacked');
    
    // Swagger UI 文档页面
    Route::get('swagger', 'DocumentationController@swaggerUI');
    
    // 管理接口
    Route::get('renderers', 'DocumentationController@availableRenderers');
    Route::post('republish', 'DocumentationController@republishAssets');
});
*/

/**
 * 自定义 HTML 模板示例
 */
class CustomDocumentationRenderer
{
    /**
     * 创建带有自定义样式的 Stoplight Elements 页面
     */
    public static function getCustomStoplightElementsHtml(string $apiUrl): string
    {
        return <<<HTML
<!doctype html>
<html lang="zh-CN">
  <head>
    <meta charset="utf-8" />
    <meta name="referrer" content="same-origin" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>API 文档 - 自定义样式</title>
    
    <!-- Stoplight Elements 样式 -->
    <link href="/swagger-ui/elements-styles.min.css" rel="stylesheet" />
    
    <!-- 自定义样式 -->
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
      
      .header p {
        margin: 10px 0 0 0;
        opacity: 0.8;
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
        <h1>🚀 API 文档</h1>
        <p>基于 Stoplight Elements 的美化版本</p>
      </div>
      <elements-api
        apiDescriptionUrl="{$apiUrl}"
        router="hash"
        layout="sidebar"
        tryItCredentialsPolicy="same-origin"
        hideInternal="true"
      />
    </div>
  </body>
</html>
HTML;
    }
}
