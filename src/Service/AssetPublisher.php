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

        // 复制文件
        $files = [
            'swagger-ui.css',
            'swagger-ui-bundle.js'
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
            'swagger-ui.css',
            'swagger-ui-bundle.js'
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
        } catch (\Exception $e) {
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
}
