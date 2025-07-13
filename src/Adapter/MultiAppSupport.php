<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Adapter;

use think\App;
use Yangweijie\ThinkScramble\Exception\AnalysisException;

/**
 * ThinkPHP 多应用模式支持
 * 
 * 处理 ThinkPHP 多应用模式的路由和控制器分析
 */
class MultiAppSupport
{
    /**
     * ThinkPHP 应用实例
     */
    protected App $app;

    /**
     * 路由分析器
     */
    protected RouteAnalyzer $routeAnalyzer;

    /**
     * 控制器解析器
     */
    protected ControllerParser $controllerParser;

    /**
     * 应用缓存
     */
    protected array $appCache = [];

    /**
     * 构造函数
     *
     * @param App|null $app ThinkPHP 应用实例
     */
    public function __construct(?App $app = null)
    {
        $this->app = $app ?: new App();
        $this->routeAnalyzer = new RouteAnalyzer($this->app);
        $this->controllerParser = new ControllerParser($this->app);
    }

    /**
     * 检查是否为多应用模式
     *
     * @return bool
     */
    public function isMultiApp(): bool
    {
        return $this->app->config->get('app.multi_app', false);
    }

    /**
     * 获取所有应用
     *
     * @return array
     * @throws AnalysisException
     */
    public function getApplications(): array
    {
        if (!$this->isMultiApp()) {
            return ['default']; // 单应用模式
        }

        $cacheKey = 'applications';
        if (isset($this->appCache[$cacheKey])) {
            return $this->appCache[$cacheKey];
        }

        try {
            $applications = [];
            $appPath = $this->app->getAppPath();

            if (!is_dir($appPath)) {
                throw new AnalysisException("Application path not found: {$appPath}");
            }

            $dirs = scandir($appPath);
            foreach ($dirs as $dir) {
                if ($dir === '.' || $dir === '..') {
                    continue;
                }

                $fullPath = $appPath . $dir;
                if (is_dir($fullPath) && $this->isValidApplication($fullPath)) {
                    $applications[] = $dir;
                }
            }

            $this->appCache[$cacheKey] = $applications;
            return $applications;

        } catch (\Exception $e) {
            throw new AnalysisException("Failed to get applications: " . $e->getMessage());
        }
    }

    /**
     * 检查是否为有效的应用目录
     *
     * @param string $path 应用路径
     * @return bool
     */
    protected function isValidApplication(string $path): bool
    {
        // 检查是否有控制器目录
        $controllerPath = $path . DIRECTORY_SEPARATOR . 'controller';
        if (is_dir($controllerPath)) {
            return true;
        }

        // 检查是否有其他标识文件
        $identifiers = ['common.php', 'provider.php', 'middleware.php'];
        foreach ($identifiers as $file) {
            if (file_exists($path . DIRECTORY_SEPARATOR . $file)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 分析应用信息
     *
     * @param string $appName 应用名称
     * @return array
     * @throws AnalysisException
     */
    public function analyzeApplication(string $appName): array
    {
        $cacheKey = "app_{$appName}";
        if (isset($this->appCache[$cacheKey])) {
            return $this->appCache[$cacheKey];
        }

        try {
            $appInfo = [
                'name' => $appName,
                'path' => $this->getApplicationPath($appName),
                'namespace' => $this->getApplicationNamespace($appName),
                'controllers' => [],
                'middleware' => [],
                'config' => [],
                'routes' => [],
            ];

            // 分析控制器
            $appInfo['controllers'] = $this->analyzeApplicationControllers($appName);

            // 分析中间件
            $appInfo['middleware'] = $this->analyzeApplicationMiddleware($appName);

            // 分析配置
            $appInfo['config'] = $this->analyzeApplicationConfig($appName);

            // 分析路由
            $appInfo['routes'] = $this->analyzeApplicationRoutes($appName);

            $this->appCache[$cacheKey] = $appInfo;
            return $appInfo;

        } catch (\Exception $e) {
            throw new AnalysisException("Failed to analyze application {$appName}: " . $e->getMessage());
        }
    }

    /**
     * 获取应用路径
     *
     * @param string $appName 应用名称
     * @return string
     */
    protected function getApplicationPath(string $appName): string
    {
        if (!$this->isMultiApp()) {
            return $this->app->getAppPath();
        }

        return $this->app->getAppPath() . $appName . DIRECTORY_SEPARATOR;
    }

    /**
     * 获取应用命名空间
     *
     * @param string $appName 应用名称
     * @return string
     */
    protected function getApplicationNamespace(string $appName): string
    {
        $baseNamespace = $this->app->config->get('app.app_namespace', 'app');
        
        if (!$this->isMultiApp()) {
            return $baseNamespace;
        }

        return $baseNamespace . '\\' . $appName;
    }

    /**
     * 分析应用控制器
     *
     * @param string $appName 应用名称
     * @return array
     */
    protected function analyzeApplicationControllers(string $appName): array
    {
        $controllers = [];
        $controllerPath = $this->getApplicationPath($appName) . 'controller';

        if (!is_dir($controllerPath)) {
            return $controllers;
        }

        $files = $this->scanControllerFiles($controllerPath);
        
        foreach ($files as $file) {
            $controllerName = pathinfo($file, PATHINFO_FILENAME);
            
            try {
                $controllerInfo = $this->controllerParser->parseController($controllerName, $appName);
                $controllers[$controllerName] = $controllerInfo;
            } catch (\Exception $e) {
                // 忽略解析失败的控制器
            }
        }

        return $controllers;
    }

    /**
     * 扫描控制器文件
     *
     * @param string $path 控制器路径
     * @return array
     */
    protected function scanControllerFiles(string $path): array
    {
        $files = [];
        
        if (!is_dir($path)) {
            return $files;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * 分析应用中间件
     *
     * @param string $appName 应用名称
     * @return array
     */
    protected function analyzeApplicationMiddleware(string $appName): array
    {
        $middleware = [];
        $middlewarePath = $this->getApplicationPath($appName) . 'middleware.php';

        if (file_exists($middlewarePath)) {
            try {
                $config = include $middlewarePath;
                if (is_array($config)) {
                    $middleware = $config;
                }
            } catch (\Exception $e) {
                // 忽略配置文件错误
            }
        }

        return $middleware;
    }

    /**
     * 分析应用配置
     *
     * @param string $appName 应用名称
     * @return array
     */
    protected function analyzeApplicationConfig(string $appName): array
    {
        $config = [];
        $configFiles = ['app.php', 'route.php', 'middleware.php'];
        $appPath = $this->getApplicationPath($appName);

        foreach ($configFiles as $file) {
            $filePath = $appPath . $file;
            if (file_exists($filePath)) {
                try {
                    $fileConfig = include $filePath;
                    if (is_array($fileConfig)) {
                        $config[pathinfo($file, PATHINFO_FILENAME)] = $fileConfig;
                    }
                } catch (\Exception $e) {
                    // 忽略配置文件错误
                }
            }
        }

        return $config;
    }

    /**
     * 分析应用路由
     *
     * @param string $appName 应用名称
     * @return array
     */
    protected function analyzeApplicationRoutes(string $appName): array
    {
        // 这里可以扩展为分析应用特定的路由文件
        // 目前返回空数组，因为 ThinkPHP 的路由通常是全局的
        return [];
    }

    /**
     * 获取应用的 API 控制器
     *
     * @param string $appName 应用名称
     * @return array
     */
    public function getApiControllers(string $appName): array
    {
        $appInfo = $this->analyzeApplication($appName);
        $apiControllers = [];

        foreach ($appInfo['controllers'] as $name => $controller) {
            if (isset($controller['features']['is_api_controller']) && 
                $controller['features']['is_api_controller']) {
                $apiControllers[$name] = $controller;
            }
        }

        return $apiControllers;
    }

    /**
     * 生成应用文档配置
     *
     * @param string $appName 应用名称
     * @return array
     */
    public function generateAppDocumentationConfig(string $appName): array
    {
        $appInfo = $this->analyzeApplication($appName);
        
        return [
            'info' => [
                'title' => ucfirst($appName) . ' API Documentation',
                'version' => '1.0.0',
                'description' => "API documentation for {$appName} application",
            ],
            'servers' => [
                [
                    'url' => "/{$appName}",
                    'description' => ucfirst($appName) . ' application server',
                ],
            ],
            'tags' => $this->generateAppTags($appInfo),
        ];
    }

    /**
     * 生成应用标签
     *
     * @param array $appInfo 应用信息
     * @return array
     */
    protected function generateAppTags(array $appInfo): array
    {
        $tags = [];
        
        foreach ($appInfo['controllers'] as $name => $controller) {
            if (isset($controller['features']['is_api_controller']) && 
                $controller['features']['is_api_controller']) {
                $tags[] = [
                    'name' => $name,
                    'description' => $controller['doc_comment'] ?? ucfirst($name) . ' controller',
                ];
            }
        }

        return $tags;
    }

    /**
     * 清除应用缓存
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->appCache = [];
    }
}
