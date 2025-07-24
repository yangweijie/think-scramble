<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Service;

use think\Service;
use think\App;
use think\Console;
use Yangweijie\ThinkScramble\Config\ScrambleConfig;
use Yangweijie\ThinkScramble\Config\ConfigPublisher;
use Yangweijie\ThinkScramble\Scramble;
use Yangweijie\ThinkScramble\Service\AssetPublisher;

/**
 * Scramble 服务类
 *
 * 负责注册 Scramble 扩展包的服务和组件
 */
class ScrambleServiceProvider extends Service
{
    /**
     * 注册服务
     *
     * @return void
     */
    public function register(): void
    {
        // 注册配置
        $this->registerConfig();

        // 注册核心服务
        $this->registerCoreServices();

        // 注册别名
        $this->registerAliases();
    }

    /**
     * 启动服务
     *
     * @return void
     */
    public function boot(): void
    {
        // 注册命令
        $this->registerCommands();

        // 注册中间件
        $this->registerMiddleware();

        // 发布配置文件
        $this->publishConfig();

        // 发布静态资源文件
        $this->publishAssets();

        // 初始化 Scramble
        $this->initializeScramble();
    }

    /**
     * 注册配置
     *
     * @return void
     */
    protected function registerConfig(): void
    {
        // 绑定配置接口
        $this->app->bind(
            \Yangweijie\ThinkScramble\Contracts\ConfigInterface::class,
            function (App $app) {
                return ScrambleConfig::fromThinkPHP('scramble');
            }
        );

        // 绑定配置实例
        $this->app->bind('scramble.config', function (App $app) {
            return $app->make(\Yangweijie\ThinkScramble\Contracts\ConfigInterface::class);
        });
    }

    /**
     * 注册核心服务
     *
     * @return void
     */
    protected function registerCoreServices(): void
    {
        // 注册核心服务
        $this->app->bind(ScrambleService::class, function (App $app) {
            $config = $app->make('scramble.config');
            return new ScrambleService($config);
        });

        // 注册服务别名
        $this->app->bind('scramble', ScrambleService::class);

        // TODO: 在后续任务中注册分析器和生成器
        // $this->registerAnalyzers();
        // $this->registerGenerators();
    }

    /**
     * 注册别名
     *
     * @return void
     */
    protected function registerAliases(): void
    {
        $this->app->bind('scramble.service', ScrambleService::class);
    }

    /**
     * 注册命令
     *
     * @return void
     */
    protected function registerCommands(): void
    {
        $this->commands([
            \Yangweijie\ThinkScramble\Command\GenerateCommand::class,
            \Yangweijie\ThinkScramble\Command\ExportCommand::class,
            \Yangweijie\ThinkScramble\Command\PublishCommand::class,
        ]);
    }

    /**
     * 注册中间件
     *
     * @return void
     */
    protected function registerMiddleware(): void
    {
        // TODO: 在后续任务中注册中间件
        // 注册全局中间件
        // $this->app->middleware->add(\Yangweijie\ThinkScramble\Middleware\DocsAccessMiddleware::class);

        // 或者注册路由中间件
        // $this->app->middleware->alias([
        //     'scramble.docs' => \Yangweijie\ThinkScramble\Middleware\DocsAccessMiddleware::class,
        // ]);
    }

    /**
     * 发布配置文件
     *
     * @return void
     */
    protected function publishConfig(): void
    {
        // 检查是否在控制台环境中
        if (!$this->app->runningInConsole()) {
            return;
        }

        // 获取应用配置目录
        $configPath = $this->app->getConfigPath() . 'scramble.php';
        if (file_exists($configPath)) {
            return;
        }

        // 自动发布配置文件
        try {
            $publisher = new ConfigPublisher();
            $publisher->setTargetPath($configPath);

            if (!$publisher->isPublished()) {
                $publisher->publish();
            }
        } catch (\Exception $e) {
            // 静默处理发布失败，避免影响应用启动
            // 可以在调试模式下记录错误
            if ($this->app->isDebug()) {
                error_log('Scramble config publish failed: ' . $e->getMessage());
            }
        }
    }

    /**
     * 初始化 Scramble
     *
     * @return void
     */
    protected function initializeScramble(): void
    {
        try {
            $config = $this->app->make('scramble.config');
            $service = $this->app->make(ScrambleService::class);
            
            // 设置 Scramble 门面的依赖
            Scramble::setConfig($config);
            
            // 初始化服务
            $service->initialize();
            
        } catch (\Exception $e) {
            // 静默处理初始化失败
            if ($this->app->isDebug()) {
                error_log('Scramble initialization failed: ' . $e->getMessage());
            }
        }
    }

    /**
     * 发布静态资源文件
     *
     * @return void
     */
    protected function publishAssets(): void
    {
        try {
            $publisher = new AssetPublisher($this->app);
            $publisher->publishAssets();
        } catch (\Exception $e) {
            // 静默处理错误，不影响应用启动
            if (function_exists('trace')) {
                trace('Failed to publish Scramble assets: ' . $e->getMessage(), 'error');
            }
        }
    }

    /**
     * 获取提供的服务
     *
     * @return array
     */
    public function provides(): array
    {
        return [
            'scramble',
            'scramble.config',
            'scramble.service',
            ScrambleService::class,
            \Yangweijie\ThinkScramble\Contracts\ConfigInterface::class,
        ];
    }
}
