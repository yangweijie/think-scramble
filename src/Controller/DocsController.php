<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Controller;

use think\App;
use think\Response;
use Yangweijie\ThinkScramble\Config\ScrambleConfig;
use Yangweijie\ThinkScramble\Generator\OpenApiGenerator;
use Yangweijie\ThinkScramble\Exception\GenerationException;

/**
 * API 文档控制器
 * 
 * 提供 API 文档的 Web 访问接口
 */
class DocsController
{
    /**
     * 应用实例
     */
    protected App $app;

    /**
     * 配置实例
     */
    protected ScrambleConfig $config;

    /**
     * 构造函数
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->config = new ScrambleConfig();
    }

    /**
     * 测试端点 - 验证代码是否更新
     *
     * @return Response
     */
    public function test(): Response
    {
        return Response::create([
            'message' => 'DocsController 代码已更新',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => 'v2.0'
        ], 'json');
    }

    /**
     * 显示 API 文档 UI
     *
     * @return Response
     */
    public function ui(): Response
    {
        try {
            // 生成 OpenAPI 文档
            $generator = new OpenApiGenerator($this->app, $this->config);
            $document = $generator->generate();

            // 生成 HTML 页面
            $html = $this->generateHtml($document);

            return Response::create($html, 'html');

        } catch (GenerationException $e) {
            return Response::create(
                $this->generateErrorPage('Documentation Generation Error', $e->getMessage()),
                'html',
                500
            );
        } catch (\Exception $e) {
            return Response::create(
                $this->generateErrorPage('Unexpected Error', $e->getMessage()),
                'html',
                500
            );
        }
    }

    /**
     * 返回 JSON 格式的 API 文档
     * 
     * @return Response
     */
    public function json(): Response
    {
        try {
            $generator = new OpenApiGenerator($this->app, $this->config);
            $document = $generator->generate();

            return Response::create($document, 'json')->header([
                'Content-Type' => 'application/json',
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
            ]);

        } catch (GenerationException $e) {
            return Response::create([
                'error' => 'Documentation generation failed',
                'message' => $e->getMessage()
            ], 'json', 500);
        } catch (\Exception $e) {
            return Response::create([
                'error' => 'Unexpected error',
                'message' => $e->getMessage()
            ], 'json', 500);
        }
    }

    /**
     * 返回 YAML 格式的 API 文档
     * 
     * @return Response
     */
    public function yaml(): Response
    {
        try {
            $generator = new OpenApiGenerator($this->app, $this->config);
            $document = $generator->generate();

            // 检查 YAML 扩展
            if (!extension_loaded('yaml')) {
                return Response::create([
                    'error' => 'YAML extension not available',
                    'message' => 'Please install the YAML PHP extension to use this feature'
                ], 'json', 500);
            }

            $yaml = yaml_emit($document);

            return Response::create($yaml)->header([
                'Content-Type' => 'application/x-yaml',
                'Access-Control-Allow-Origin' => '*',
            ]);

        } catch (GenerationException $e) {
            return Response::create([
                'error' => 'Documentation generation failed',
                'message' => $e->getMessage()
            ], 'json', 500);
        } catch (\Exception $e) {
            return Response::create([
                'error' => 'Unexpected error',
                'message' => $e->getMessage()
            ], 'json', 500);
        }
    }

    /**
     * 生成 HTML 页面
     * 
     * @param array $document OpenAPI 文档
     * @return string
     */
    protected function generateHtml(array $document): string
    {
        $title = $document['info']['title'] ?? 'API Documentation';
        $jsonData = json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    <link rel="stylesheet" type="text/css" href="/swagger-ui/swagger-ui.css" />
    <style>
        html { box-sizing: border-box; overflow: -moz-scrollbars-vertical; overflow-y: scroll; }
        *, *:before, *:after { box-sizing: inherit; }
        body { margin:0; background: #fafafa; }
        .swagger-ui .topbar { display: none; }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="/swagger-ui/swagger-ui-bundle.js" charset="UTF-8"></script>
    <script>
        window.onload = function() {
            // 加载 API 规范并初始化 Swagger UI
            fetch('/docs/api.json')
                .then(response => response.json())
                .then(spec => {
                    const ui = SwaggerUIBundle({
                        spec: spec,
                        dom_id: '#swagger-ui',
                        deepLinking: true,
                        presets: [
                            SwaggerUIBundle.presets.apis
                        ],
                        plugins: [
                            SwaggerUIBundle.plugins.DownloadUrl
                        ]
                    });
                })
                .catch(error => {
                    console.error('Error loading API spec:', error);
                    document.getElementById('swagger-ui').innerHTML =
                        '<div style="padding: 20px; color: red;">Error loading API documentation: ' + error.message + '</div>';
                });
        };
    </script>
</body>
</html>
HTML;
    }

    /**
     * 生成错误页面
     * 
     * @param string $title 错误标题
     * @param string $message 错误消息
     * @return string
     */
    protected function generateErrorPage(string $title, string $message): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>{$title}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .error-container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .error-title { color: #d32f2f; font-size: 24px; margin-bottom: 16px; }
        .error-message { color: #666; font-size: 16px; line-height: 1.5; }
        .back-link { margin-top: 20px; }
        .back-link a { color: #1976d2; text-decoration: none; }
        .back-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="error-container">
        <h1 class="error-title">{$title}</h1>
        <p class="error-message">{$message}</p>
        <div class="back-link">
            <a href="javascript:history.back()">← Go Back</a>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
