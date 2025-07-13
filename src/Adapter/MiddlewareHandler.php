<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Adapter;

use think\App;
use think\Middleware;
use ReflectionClass;
use Yangweijie\ThinkScramble\Exception\AnalysisException;

/**
 * ThinkPHP 中间件处理器
 * 
 * 分析和处理 ThinkPHP 中间件对 API 的影响
 */
class MiddlewareHandler
{
    /**
     * ThinkPHP 应用实例
     */
    protected App $app;

    /**
     * 中间件缓存
     */
    protected array $middlewareCache = [];

    /**
     * 已知的认证中间件
     */
    protected array $authMiddleware = [
        'auth',
        'auth.session',
        'auth.basic',
        'jwt',
        'jwt.auth',
        'api.auth',
    ];

    /**
     * 已知的限流中间件
     */
    protected array $throttleMiddleware = [
        'throttle',
        'rate.limit',
        'api.throttle',
    ];

    /**
     * 已知的 CORS 中间件
     */
    protected array $corsMiddleware = [
        'cors',
        'api.cors',
        'cross.origin',
    ];

    /**
     * 构造函数
     *
     * @param App|null $app ThinkPHP 应用实例
     */
    public function __construct(?App $app = null)
    {
        $this->app = $app ?: new App();
    }

    /**
     * 分析中间件列表
     *
     * @param array $middleware 中间件列表
     * @return array
     */
    public function analyzeMiddleware(array $middleware): array
    {
        $result = [
            'middleware' => [],
            'security' => [
                'authentication' => [],
                'authorization' => [],
                'rate_limiting' => [],
                'cors' => [],
            ],
            'features' => [
                'requires_auth' => false,
                'has_rate_limit' => false,
                'supports_cors' => false,
            ],
        ];

        foreach ($middleware as $mw) {
            $middlewareInfo = $this->analyzeMiddlewareItem($mw);
            $result['middleware'][] = $middlewareInfo;

            // 分析安全特性
            $this->analyzeSecurity($middlewareInfo, $result);
        }

        return $result;
    }

    /**
     * 分析单个中间件
     *
     * @param string|array $middleware 中间件定义
     * @return array
     */
    protected function analyzeMiddlewareItem($middleware): array
    {
        $info = [
            'name' => '',
            'class' => '',
            'parameters' => [],
            'type' => 'custom',
            'description' => '',
        ];

        if (is_string($middleware)) {
            $info['name'] = $middleware;
            $info['class'] = $this->resolveMiddlewareClass($middleware);
        } elseif (is_array($middleware)) {
            $info['name'] = $middleware[0] ?? '';
            $info['class'] = $this->resolveMiddlewareClass($info['name']);
            $info['parameters'] = array_slice($middleware, 1);
        }

        // 确定中间件类型
        $info['type'] = $this->determineMiddlewareType($info['name']);
        
        // 获取中间件描述
        $info['description'] = $this->getMiddlewareDescription($info);

        return $info;
    }

    /**
     * 解析中间件类名
     *
     * @param string $name 中间件名称
     * @return string
     */
    protected function resolveMiddlewareClass(string $name): string
    {
        // 检查是否为完整类名
        if (class_exists($name)) {
            return $name;
        }

        // 尝试从中间件别名解析
        try {
            $middlewareConfig = $this->app->config->get('middleware.alias', []);
            if (isset($middlewareConfig[$name])) {
                return $middlewareConfig[$name];
            }
        } catch (\Exception $e) {
            // 忽略配置获取失败
        }

        // 尝试标准命名空间
        $namespace = $this->app->config->get('app.app_namespace', 'app') . '\\middleware\\';
        $className = $namespace . ucfirst($name);
        
        if (class_exists($className)) {
            return $className;
        }

        // 返回原名称
        return $name;
    }

    /**
     * 确定中间件类型
     *
     * @param string $name 中间件名称
     * @return string
     */
    protected function determineMiddlewareType(string $name): string
    {
        $lowerName = strtolower($name);

        if (in_array($lowerName, $this->authMiddleware) || str_contains($lowerName, 'auth')) {
            return 'authentication';
        }

        if (in_array($lowerName, $this->throttleMiddleware) || str_contains($lowerName, 'throttle') || str_contains($lowerName, 'limit')) {
            return 'rate_limiting';
        }

        if (in_array($lowerName, $this->corsMiddleware) || str_contains($lowerName, 'cors')) {
            return 'cors';
        }

        if (str_contains($lowerName, 'permission') || str_contains($lowerName, 'role') || str_contains($lowerName, 'acl')) {
            return 'authorization';
        }

        if (str_contains($lowerName, 'log') || str_contains($lowerName, 'trace')) {
            return 'logging';
        }

        if (str_contains($lowerName, 'cache')) {
            return 'caching';
        }

        if (str_contains($lowerName, 'validate') || str_contains($lowerName, 'check')) {
            return 'validation';
        }

        return 'custom';
    }

    /**
     * 获取中间件描述
     *
     * @param array $middlewareInfo 中间件信息
     * @return string
     */
    protected function getMiddlewareDescription(array $middlewareInfo): string
    {
        $type = $middlewareInfo['type'];
        $name = $middlewareInfo['name'];

        return match ($type) {
            'authentication' => "Authentication middleware: {$name}",
            'authorization' => "Authorization middleware: {$name}",
            'rate_limiting' => "Rate limiting middleware: {$name}",
            'cors' => "CORS middleware: {$name}",
            'logging' => "Logging middleware: {$name}",
            'caching' => "Caching middleware: {$name}",
            'validation' => "Validation middleware: {$name}",
            default => "Custom middleware: {$name}",
        };
    }

    /**
     * 分析安全特性
     *
     * @param array $middlewareInfo 中间件信息
     * @param array &$result 结果数组
     * @return void
     */
    protected function analyzeSecurity(array $middlewareInfo, array &$result): void
    {
        $type = $middlewareInfo['type'];
        $name = $middlewareInfo['name'];

        switch ($type) {
            case 'authentication':
                $result['security']['authentication'][] = $middlewareInfo;
                $result['features']['requires_auth'] = true;
                break;

            case 'authorization':
                $result['security']['authorization'][] = $middlewareInfo;
                break;

            case 'rate_limiting':
                $result['security']['rate_limiting'][] = $middlewareInfo;
                $result['features']['has_rate_limit'] = true;
                break;

            case 'cors':
                $result['security']['cors'][] = $middlewareInfo;
                $result['features']['supports_cors'] = true;
                break;
        }
    }

    /**
     * 分析中间件对 API 文档的影响
     *
     * @param array $middlewareAnalysis 中间件分析结果
     * @return array
     */
    public function analyzeApiDocumentationImpact(array $middlewareAnalysis): array
    {
        $impact = [
            'security_schemes' => [],
            'global_parameters' => [],
            'global_headers' => [],
            'responses' => [],
        ];

        // 分析认证方案
        foreach ($middlewareAnalysis['security']['authentication'] as $auth) {
            $scheme = $this->generateSecurityScheme($auth);
            if ($scheme) {
                $impact['security_schemes'][] = $scheme;
            }
        }

        // 分析全局参数
        if ($middlewareAnalysis['features']['requires_auth']) {
            $impact['global_headers'][] = [
                'name' => 'Authorization',
                'description' => 'Bearer token for authentication',
                'required' => true,
                'schema' => ['type' => 'string'],
            ];
        }

        // 分析限流响应
        if ($middlewareAnalysis['features']['has_rate_limit']) {
            $impact['responses']['429'] = [
                'description' => 'Too Many Requests',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'message' => ['type' => 'string'],
                                'retry_after' => ['type' => 'integer'],
                            ],
                        ],
                    ],
                ],
            ];
        }

        // 分析 CORS 头
        if ($middlewareAnalysis['features']['supports_cors']) {
            $corsHeaders = [
                'Access-Control-Allow-Origin',
                'Access-Control-Allow-Methods',
                'Access-Control-Allow-Headers',
            ];

            foreach ($corsHeaders as $header) {
                $impact['global_headers'][] = [
                    'name' => $header,
                    'description' => 'CORS header',
                    'required' => false,
                    'schema' => ['type' => 'string'],
                ];
            }
        }

        return $impact;
    }

    /**
     * 生成安全方案
     *
     * @param array $authMiddleware 认证中间件信息
     * @return array|null
     */
    protected function generateSecurityScheme(array $authMiddleware): ?array
    {
        $name = strtolower($authMiddleware['name']);

        if (str_contains($name, 'jwt')) {
            return [
                'type' => 'http',
                'scheme' => 'bearer',
                'bearerFormat' => 'JWT',
                'description' => 'JWT Bearer token authentication',
            ];
        }

        if (str_contains($name, 'basic')) {
            return [
                'type' => 'http',
                'scheme' => 'basic',
                'description' => 'HTTP Basic authentication',
            ];
        }

        if (str_contains($name, 'api') || str_contains($name, 'token')) {
            return [
                'type' => 'apiKey',
                'in' => 'header',
                'name' => 'Authorization',
                'description' => 'API key authentication',
            ];
        }

        return null;
    }

    /**
     * 清除中间件缓存
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->middlewareCache = [];
    }
}
