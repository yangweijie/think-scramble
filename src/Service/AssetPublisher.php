<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Service;

use think\App;

/**
 * 资源发布服务
 * 
 * 负责将扩展包中的静态资源文件复制到应用的 public 目录
 */
class AssetPublisher
{
    /**
     * ThinkPHP 应用实例
     */
    protected App $app;

    /**
     * 扩展包根目录
     */
    protected string $packagePath;

    /**
     * 构造函数
     *
     * @param App $app ThinkPHP 应用实例
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->packagePath = dirname(__DIR__, 2);
    }

    /**
     * 发布所有资源文件
     *
     * @return bool
     */
    public function publishAssets(): bool
    {
        try {
            $this->publishSwaggerUIAssets();
            return true;
        } catch (\Exception $e) {
            // 记录错误但不阻止应用启动
            if (function_exists('trace')) {
                trace('Failed to publish Scramble assets: ' . $e->getMessage(), 'error');
            }
            return false;
        }
    }

    /**
     * 发布 Swagger UI 资源文件
     *
     * @return void
     */
    protected function publishSwaggerUIAssets(): void
    {
        $sourceDir = $this->packagePath . '/assets/swagger-ui';
        $targetDir = $this->app->getRootPath() . 'public/swagger-ui';

        // 检查源目录是否存在
        if (!is_dir($sourceDir)) {
            return;
        }

        // 创建目标目录
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // 复制文件 - 包含 Swagger UI 和 Stoplight Elements 资源
        $files = [
            // Swagger UI 文件
            'swagger-ui.css',
            'swagger-ui-bundle.js',
            // Stoplight Elements 文件
            'elements-styles.min.css',
            'elements-web-components.min.js'
        ];

        foreach ($files as $file) {
            $sourcePath = $sourceDir . '/' . $file;
            $targetPath = $targetDir . '/' . $file;

            if (file_exists($sourcePath)) {
                // 检查目标文件是否已存在且是最新的
                if (!file_exists($targetPath) ||
                    filemtime($sourcePath) > filemtime($targetPath)) {
                    copy($sourcePath, $targetPath);
                }
            }
        }
    }

    /**
     * 检查资源文件是否已发布
     *
     * @return bool
     */
    public function areAssetsPublished(): bool
    {
        $targetDir = $this->app->getRootPath() . 'public/swagger-ui';

        $requiredFiles = [
            // Swagger UI 文件
            'swagger-ui.css',
            'swagger-ui-bundle.js',
            // Stoplight Elements 文件
            'elements-styles.min.css',
            'elements-web-components.min.js'
        ];

        foreach ($requiredFiles as $file) {
            if (!file_exists($targetDir . '/' . $file)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 强制重新发布资源文件
     *
     * @return bool
     */
    public function forcePublishAssets(): bool
    {
        try {
            $targetDir = $this->app->getRootPath() . 'public/swagger-ui';
            
            // 删除现有文件
            if (is_dir($targetDir)) {
                $this->removeDirectory($targetDir);
            }

            // 重新发布
            return $this->publishAssets();
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * 递归删除目录
     *
     * @param string $dir 目录路径
     * @return void
     */
    protected function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        
        rmdir($dir);
    }

    /**
     * 获取 Stoplight Elements 的 HTML 模板
     *
     * @param string $apiDescriptionUrl OpenAPI 规范的 URL
     * @param array $options 配置选项
     * @return string
     */
    public function getStoplightElementsHtml(string $apiDescriptionUrl, array $options = []): string
    {
        $layout = $options['layout'] ?? 'sidebar';
        $router = $options['router'] ?? 'hash';
        $tryItCredentialsPolicy = $options['tryItCredentialsPolicy'] ?? 'same-origin';
        $title = $options['title'] ?? 'API Documentation';

        return <<<HTML
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="referrer" content="same-origin" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>{$title}</title>
    <!-- Embed Stoplight Elements via Web Component -->
    <link href="/swagger-ui/elements-styles.min.css" rel="stylesheet" />
    <script src="/swagger-ui/elements-web-components.min.js" crossorigin="anonymous"></script>
  </head>
  <body style="height: 100vh;">
    <elements-api
      apiDescriptionUrl="{$apiDescriptionUrl}"
      router="{$router}"
      layout="{$layout}"
      tryItCredentialsPolicy="{$tryItCredentialsPolicy}"
    />
  </body>
</html>
HTML;
    }

    /**
     * 获取 Swagger UI 的 HTML 模板
     *
     * @param string $apiDescriptionUrl OpenAPI 规范的 URL
     * @param array $options 配置选项
     * @return string
     */
    public function getSwaggerUIHtml(string $apiDescriptionUrl, array $options = []): string
    {
        $title = $options['title'] ?? 'API Documentation';

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="description" content="SwaggerUI" />
  <title>{$title}</title>
  <link rel="stylesheet" href="/swagger-ui/swagger-ui.css" />
</head>
<body>
<div id="swagger-ui"></div>
<script src="/swagger-ui/swagger-ui-bundle.js" crossorigin></script>
<script>
  window.onload = () => {
    window.ui = SwaggerUIBundle({
      url: '{$apiDescriptionUrl}',
      dom_id: '#swagger-ui',
    });
  };
</script>
</body>
</html>
HTML;
    }

    /**
     * 获取可用的文档渲染器列表
     *
     * @return array
     */
    public function getAvailableRenderers(): array
    {
        return [
            'stoplight-elements' => [
                'name' => 'Stoplight Elements',
                'description' => '现代化的 API 文档渲染器，支持多种布局',
                'files' => ['elements-styles.min.css', 'elements-web-components.min.js']
            ],
            'swagger-ui' => [
                'name' => 'Swagger UI',
                'description' => '经典的 API 文档渲染器',
                'files' => ['swagger-ui.css', 'swagger-ui-bundle.js']
            ]
        ];
    }

    /**
     * 检查特定渲染器的资源是否可用
     *
     * @param string $renderer 渲染器名称
     * @return bool
     */
    public function isRendererAvailable(string $renderer): bool
    {
        $renderers = $this->getAvailableRenderers();

        if (!isset($renderers[$renderer])) {
            return false;
        }

        $targetDir = $this->app->getRootPath() . 'public/swagger-ui';

        foreach ($renderers[$renderer]['files'] as $file) {
            if (!file_exists($targetDir . '/' . $file)) {
                return false;
            }
        }

        return true;
    }
}
