<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Generator;

use think\App;
use Yangweijie\ThinkScramble\Contracts\ConfigInterface;
use Yangweijie\ThinkScramble\Adapter\RouteAnalyzer;
use Yangweijie\ThinkScramble\Adapter\ControllerParser;
use Yangweijie\ThinkScramble\Adapter\MiddlewareHandler;
use Yangweijie\ThinkScramble\Adapter\ValidatorIntegration;
use Yangweijie\ThinkScramble\Exception\GenerationException;
use Yangweijie\ThinkScramble\Utils\YamlGenerator;

/**
 * OpenAPI 文档生成器
 * 
 * 整合所有组件，生成完整的 OpenAPI 文档
 */
class OpenApiGenerator
{
    /**
     * ThinkPHP 应用实例
     */
    protected App $app;

    /**
     * 配置接口
     */
    protected ConfigInterface $config;

    /**
     * 文档构建器
     */
    protected DocumentBuilder $documentBuilder;

    /**
     * 路由分析器
     */
    protected RouteAnalyzer $routeAnalyzer;

    /**
     * 控制器解析器
     */
    protected ControllerParser $controllerParser;

    /**
     * 中间件处理器
     */
    protected MiddlewareHandler $middlewareHandler;

    /**
     * 验证器集成
     */
    protected ValidatorIntegration $validatorIntegration;

    /**
     * 响应生成器
     */
    protected ResponseGenerator $responseGenerator;

    /**
     * 构造函数
     *
     * @param App $app ThinkPHP 应用实例
     * @param ConfigInterface $config 配置接口
     */
    public function __construct(App $app, ConfigInterface $config)
    {
        $this->app = $app;
        $this->config = $config;
        
        $this->initializeComponents();
    }

    /**
     * 初始化组件
     *
     * @return void
     */
    protected function initializeComponents(): void
    {
        $this->documentBuilder = new DocumentBuilder($this->config);
        $this->routeAnalyzer = new RouteAnalyzer($this->app);
        $this->controllerParser = new ControllerParser($this->app);
        $this->middlewareHandler = new MiddlewareHandler($this->app);
        $this->validatorIntegration = new ValidatorIntegration($this->app);
        $this->responseGenerator = new ResponseGenerator($this->config);
    }

    /**
     * 生成完整的 OpenAPI 文档
     *
     * @return array
     * @throws GenerationException
     */
    public function generate(): array
    {
        try {
            // 分析所有路由
            $routes = $this->routeAnalyzer->analyzeRoutes();
            
            // 处理每个路由
            foreach ($routes as $route) {
                $this->processRoute($route);
            }

            // 添加全局组件
            $this->addGlobalComponents();

            return $this->documentBuilder->getDocument();

        } catch (\Exception $e) {
            throw new GenerationException("Failed to generate OpenAPI documentation: " . $e->getMessage());
        }
    }

    /**
     * 处理单个路由
     *
     * @param array $route 路由信息
     * @return void
     * @throws GenerationException
     */
    protected function processRoute(array $route): void
    {
        try {
            // 检查是否为 API 路由
            if (!$this->routeAnalyzer->isApiRoute($route)) {
                return;
            }

            // 解析控制器信息
            $controllerInfo = $this->parseControllerInfo($route);
            if (!$controllerInfo) {
                return;
            }

            // 分析中间件
            $middlewareInfo = $this->analyzeMiddleware($route);

            // 构建操作定义
            $operation = $this->documentBuilder->buildOperation($route, $controllerInfo, $middlewareInfo);

            // 添加验证器参数
            $this->addValidatorParameters($operation, $route, $controllerInfo);

            // 生成响应
            $responses = $this->responseGenerator->generateResponses($route, $controllerInfo, $middlewareInfo);
            $operation['responses'] = $responses;

            // 添加到文档
            $path = $this->normalizePath($route['rule']);
            $method = strtolower($route['method']);
            $this->documentBuilder->addPath($path, $method, $operation);

            // 添加标签
            $this->addControllerTag($route, $controllerInfo);

        } catch (\Exception $e) {
            // 记录错误但继续处理其他路由
            error_log("Failed to process route {$route['rule']}: " . $e->getMessage());
        }
    }

    /**
     * 解析控制器信息
     *
     * @param array $route 路由信息
     * @return array|null
     */
    protected function parseControllerInfo(array $route): ?array
    {
        $controller = $route['controller'] ?? '';
        $module = $route['module'] ?? null;

        if (empty($controller)) {
            return null;
        }

        try {
            return $this->controllerParser->parseController($controller, $module);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 分析中间件
     *
     * @param array $route 路由信息
     * @return array
     */
    protected function analyzeMiddleware(array $route): array
    {
        $middleware = $route['middleware'] ?? [];
        
        if (empty($middleware)) {
            return [];
        }

        return $this->middlewareHandler->analyzeMiddleware($middleware);
    }

    /**
     * 添加验证器参数
     *
     * @param array &$operation 操作定义
     * @param array $route 路由信息
     * @param array $controllerInfo 控制器信息
     * @return void
     */
    protected function addValidatorParameters(array &$operation, array $route, array $controllerInfo): void
    {
        // 这里可以扩展为从控制器方法中检测验证器使用
        // 目前暂时跳过
    }

    /**
     * 添加控制器标签
     *
     * @param array $route 路由信息
     * @param array $controllerInfo 控制器信息
     * @return void
     */
    protected function addControllerTag(array $route, array $controllerInfo): void
    {
        $controller = $route['controller'] ?? '';

        if (empty($controller)) {
            return;
        }

        // 提取控制器简名（去掉命名空间）
        $controllerParts = explode('\\', $controller);
        $controllerName = end($controllerParts);

        // 生成友好的标签名称
        $tagMap = [
            'Api' => 'API 接口',
            'User' => '用户管理',
            'Auth' => '认证授权',
            'Admin' => '管理后台',
            'Product' => '产品管理',
            'Order' => '订单管理',
        ];

        $friendlyTag = $tagMap[$controllerName] ?? $controllerName;

        // 提取简洁的描述
        $description = $friendlyTag;
        if (isset($controllerInfo['doc_comment'])) {
            $docComment = $controllerInfo['doc_comment'];
            // 提取第一行有意义的注释
            if (preg_match('/\/\*\*\s*\n\s*\*\s*(.+?)\s*\n/', $docComment, $matches)) {
                $firstLine = trim($matches[1]);
                if (!empty($firstLine) && !str_contains($firstLine, '@')) {
                    $description = $firstLine;
                }
            }
        }

        $tag = [
            'name' => $friendlyTag,
            'description' => $description,
        ];

        $this->documentBuilder->addTag($tag);
    }

    /**
     * 添加全局组件
     *
     * @return void
     */
    protected function addGlobalComponents(): void
    {
        // 添加安全方案
        $this->addSecuritySchemes();

        // 添加通用响应
        $this->addCommonResponses();

        // 添加通用参数
        $this->addCommonParameters();
    }

    /**
     * 添加安全方案
     *
     * @return void
     */
    protected function addSecuritySchemes(): void
    {
        $schemes = $this->config->get('security.schemes', []);

        // 默认添加 Bearer Token 认证
        if (empty($schemes)) {
            $schemes = [
                'bearerAuth' => [
                    'type' => 'http',
                    'scheme' => 'bearer',
                    'bearerFormat' => 'JWT',
                    'description' => 'JWT Bearer token authentication',
                ],
            ];
        }

        foreach ($schemes as $name => $scheme) {
            $this->documentBuilder->addSecurityScheme($name, $scheme);
        }
    }

    /**
     * 添加通用响应
     *
     * @return void
     */
    protected function addCommonResponses(): void
    {
        $commonResponses = [
            'BadRequest' => $this->responseGenerator->getStandardResponse('400'),
            'Unauthorized' => $this->responseGenerator->getStandardResponse('401'),
            'Forbidden' => $this->responseGenerator->getStandardResponse('403'),
            'NotFound' => $this->responseGenerator->getStandardResponse('404'),
            'ValidationError' => $this->responseGenerator->getStandardResponse('422'),
            'TooManyRequests' => $this->responseGenerator->getStandardResponse('429'),
            'InternalServerError' => $this->responseGenerator->getStandardResponse('500'),
        ];

        foreach ($commonResponses as $name => $response) {
            if ($response) {
                $this->documentBuilder->addResponse($name, $response);
            }
        }
    }

    /**
     * 添加通用参数
     *
     * @return void
     */
    protected function addCommonParameters(): void
    {
        $commonParameters = [
            'Page' => [
                'name' => 'page',
                'in' => 'query',
                'required' => false,
                'description' => 'Page number for pagination',
                'schema' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'default' => 1,
                ],
            ],
            'Limit' => [
                'name' => 'limit',
                'in' => 'query',
                'required' => false,
                'description' => 'Number of items per page',
                'schema' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 100,
                    'default' => 20,
                ],
            ],
            'Sort' => [
                'name' => 'sort',
                'in' => 'query',
                'required' => false,
                'description' => 'Sort field',
                'schema' => [
                    'type' => 'string',
                ],
            ],
            'Order' => [
                'name' => 'order',
                'in' => 'query',
                'required' => false,
                'description' => 'Sort order',
                'schema' => [
                    'type' => 'string',
                    'enum' => ['asc', 'desc'],
                    'default' => 'asc',
                ],
            ],
        ];

        foreach ($commonParameters as $name => $parameter) {
            $this->documentBuilder->addParameter($name, $parameter);
        }
    }

    /**
     * 规范化路径
     *
     * @param string $path 路径
     * @return string
     */
    protected function normalizePath(string $path): string
    {
        // 转换 ThinkPHP 路径参数格式为 OpenAPI 格式
        $path = preg_replace('/<(\w+)>/', '{$1}', $path);
        
        // 确保以 / 开头
        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }

        return $path;
    }

    /**
     * 生成 JSON 格式文档
     *
     * @param bool $pretty 是否格式化
     * @return string
     * @throws GenerationException
     */
    public function generateJson(bool $pretty = true): string
    {
        $document = $this->generate();

        // 直接使用生成的文档，不需要重新构建
        $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        if ($pretty) {
            $flags |= JSON_PRETTY_PRINT;
        }

        return json_encode($document, $flags);
    }

    /**
     * 生成 YAML 格式文档
     *
     * @return string
     * @throws GenerationException
     */
    public function generateYaml(): string
    {
        $document = $this->generate();

        try {
            return YamlGenerator::dump($document);
        } catch (\Exception $e) {
            throw new GenerationException('Failed to generate YAML: ' . $e->getMessage());
        }
    }

    /**
     * 清除所有缓存
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->routeAnalyzer->clearCache();
        $this->controllerParser->clearCache();
        $this->middlewareHandler->clearCache();
        $this->validatorIntegration->clearCache();
    }
}
