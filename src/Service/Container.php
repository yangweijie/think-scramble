<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Service;

use think\App;
use think\Container as ThinkContainer;
use Yangweijie\ThinkScramble\Contracts\ConfigInterface;
use Yangweijie\ThinkScramble\Contracts\AnalyzerInterface;
use Yangweijie\ThinkScramble\Contracts\GeneratorInterface;
use Yangweijie\ThinkScramble\Config\ScrambleConfig;

/**
 * Scramble 容器配置
 * 
 * 管理依赖注入和服务绑定
 */
class Container
{
    /**
     * 应用实例
     */
    protected App $app;

    /**
     * 构造函数
     *
     * @param App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * 注册所有服务绑定
     *
     * @return void
     */
    public function registerBindings(): void
    {
        $this->registerConfigBindings();
        $this->registerServiceBindings();
        $this->registerInterfaceBindings();
    }

    /**
     * 注册配置相关绑定
     *
     * @return void
     */
    protected function registerConfigBindings(): void
    {
        // 绑定配置接口
        $this->app->bind(ConfigInterface::class, function (App $app) {
            return ScrambleConfig::fromThinkPHP('scramble');
        });

        // 绑定配置实例
        $this->app->bind('scramble.config', ConfigInterface::class);
    }

    /**
     * 注册服务相关绑定
     *
     * @return void
     */
    protected function registerServiceBindings(): void
    {
        // 绑定核心服务
        $this->app->bind(ScrambleService::class, function (App $app) {
            $config = $app->make(ConfigInterface::class);
            return new ScrambleService($config);
        });

        // 绑定服务别名
        $this->app->bind('scramble', ScrambleService::class);
        $this->app->bind('scramble.service', ScrambleService::class);
    }

    /**
     * 注册接口绑定
     *
     * @return void
     */
    protected function registerInterfaceBindings(): void
    {
        // TODO: 在后续任务中绑定分析器和生成器接口
        // $this->app->bind(AnalyzerInterface::class, function (App $app) {
        //     // 返回具体的分析器实现
        // });

        // $this->app->bind(GeneratorInterface::class, function (App $app) {
        //     // 返回具体的生成器实现
        // });
    }

    /**
     * 获取服务实例
     *
     * @param string $abstract
     * @return mixed
     */
    public function make(string $abstract): mixed
    {
        return $this->app->make($abstract);
    }

    /**
     * 检查服务是否已绑定
     *
     * @param string $abstract
     * @return bool
     */
    public function bound(string $abstract): bool
    {
        return $this->app->bound($abstract);
    }

    /**
     * 获取所有已注册的服务
     *
     * @return array
     */
    public function getRegisteredServices(): array
    {
        return [
            'config' => [
                ConfigInterface::class,
                'scramble.config',
            ],
            'services' => [
                ScrambleService::class,
                'scramble',
                'scramble.service',
            ],
            'interfaces' => [
                // TODO: 在后续任务中添加
                // AnalyzerInterface::class,
                // GeneratorInterface::class,
            ],
        ];
    }

    /**
     * 验证所有服务绑定
     *
     * @return array
     */
    public function validateBindings(): array
    {
        $results = [];
        $services = $this->getRegisteredServices();

        foreach ($services as $category => $serviceList) {
            $results[$category] = [];
            
            foreach ($serviceList as $service) {
                try {
                    $instance = $this->app->make($service);
                    $results[$category][$service] = [
                        'bound' => true,
                        'resolvable' => true,
                        'instance_type' => get_class($instance),
                    ];
                } catch (\Exception $e) {
                    $results[$category][$service] = [
                        'bound' => $this->app->bound($service),
                        'resolvable' => false,
                        'error' => $e->getMessage(),
                    ];
                }
            }
        }

        return $results;
    }

    /**
     * 创建容器实例
     *
     * @param App $app
     * @return static
     */
    public static function create(App $app): static
    {
        return new static($app);
    }
}
