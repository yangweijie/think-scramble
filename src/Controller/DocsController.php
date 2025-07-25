<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Controller;

use think\App;
use think\Response;
use Yangweijie\ThinkScramble\Config\ScrambleConfig;
use Yangweijie\ThinkScramble\Generator\OpenApiGenerator;
use Yangweijie\ThinkScramble\Exception\GenerationException;
use Yangweijie\ThinkScramble\Service\AssetPublisher;

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
     * 资源发布服务
     */
    protected AssetPublisher $assetPublisher;

    /**
     * 构造函数
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->config = new ScrambleConfig();
        $this->assetPublisher = new AssetPublisher($app);

        // 确保资源文件已发布
        if (!$this->assetPublisher->areAssetsPublished()) {
            $this->assetPublisher->publishAssets();
        }
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

            // 获取渲染器参数
            $renderer = $this->app->request->param('renderer', 'auto');
            $layout = $this->app->request->param('layout', 'sidebar');

            // 生成 HTML 页面
            $html = $this->generateHtml($document, $renderer, $layout);

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
     * 使用 Stoplight Elements 显示文档
     *
     * @return Response
     */
    public function elements(): Response
    {
        try {
            $generator = new OpenApiGenerator($this->app, $this->config);
            $document = $generator->generate();

            $layout = $this->app->request->param('layout', 'sidebar');
            $html = $this->generateHtml($document, 'stoplight-elements', $layout);

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
     * 使用 Swagger UI 显示文档
     *
     * @return Response
     */
    public function swagger(): Response
    {
        try {
            $generator = new OpenApiGenerator($this->app, $this->config);
            $document = $generator->generate();

            $html = $this->generateHtml($document, 'swagger-ui');

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
     * 获取可用的渲染器信息
     *
     * @return Response
     */
    public function renderers(): Response
    {
        $renderers = $this->assetPublisher->getAvailableRenderers();
        $status = [];

        foreach ($renderers as $key => $renderer) {
            $status[$key] = [
                'name' => $renderer['name'],
                'description' => $renderer['description'],
                'available' => $this->assetPublisher->isRendererAvailable($key),
                'files' => $renderer['files'],
                'urls' => [
                    'direct' => "/docs/{$key}",
                    'with_layout' => "/docs/ui?renderer={$key}&layout=sidebar"
                ]
            ];
        }

        return Response::create([
            'renderers' => $status,
            'assets_published' => $this->assetPublisher->areAssetsPublished()
        ], 'json');
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
     * @param string $renderer 渲染器类型 (auto|stoplight-elements|swagger-ui)
     * @param string $layout 布局类型 (sidebar|stacked)
     * @return string
     */
    protected function generateHtml(array $document, string $renderer = 'auto', string $layout = 'sidebar'): string
    {
        $title = $document['info']['title'] ?? 'API Documentation';

        // 根据指定的渲染器生成 HTML
        switch ($renderer) {
            case 'stoplight-elements':
                if ($this->assetPublisher->isRendererAvailable('stoplight-elements')) {
                    return $this->assetPublisher->getStoplightElementsHtml('/docs/api.json', [
                        'title' => $title,
                        'layout' => $layout,
                        'router' => 'hash',
                        'tryItCredentialsPolicy' => 'same-origin'
                    ]);
                }
                break;

            case 'swagger-ui':
                if ($this->assetPublisher->isRendererAvailable('swagger-ui')) {
                    return $this->assetPublisher->getSwaggerUIHtml('/docs/api.json', [
                        'title' => $title
                    ]);
                }
                break;

            case 'auto':
            default:
                // 自动选择：优先使用 Stoplight Elements，回退到 Swagger UI
                if ($this->assetPublisher->isRendererAvailable('stoplight-elements')) {
                    return $this->assetPublisher->getStoplightElementsHtml('/docs/api.json', [
                        'title' => $title,
                        'layout' => $layout,
                        'router' => 'hash',
                        'tryItCredentialsPolicy' => 'same-origin'
                    ]);
                } elseif ($this->assetPublisher->isRendererAvailable('swagger-ui')) {
                    return $this->assetPublisher->getSwaggerUIHtml('/docs/api.json', [
                        'title' => $title
                    ]);
                }
                break;
        }

        // 如果都不可用，返回错误页面
        return $this->generateErrorPage(
            'Documentation Renderer Not Available',
            "The requested renderer '{$renderer}' is not available. Please ensure assets are properly published."
        );
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
