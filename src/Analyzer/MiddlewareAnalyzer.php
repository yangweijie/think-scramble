<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Analyzer;

use ReflectionClass;
use ReflectionMethod;
use Yangweijie\ThinkScramble\Exception\AnalysisException;

/**
 * ThinkPHP 中间件分析器
 * 
 * 分析中间件配置，提取安全方案和认证信息
 */
class MiddlewareAnalyzer
{
    /**
     * DocBlock 解析器
     */
    protected DocBlockParser $docBlockParser;

    /**
     * 注解解析器
     */
    protected AnnotationParser $annotationParser;

    /**
     * 内置中间件映射
     */
    protected array $builtinMiddleware = [
        'auth' => [
            'type' => 'authentication',
            'scheme' => 'bearer',
            'description' => '用户认证中间件',
            'security' => [
                'type' => 'http',
                'scheme' => 'bearer',
                'bearerFormat' => 'JWT',
            ],
        ],
        'admin' => [
            'type' => 'authorization',
            'description' => '管理员权限中间件',
            'security' => [
                'type' => 'http',
                'scheme' => 'bearer',
                'bearerFormat' => 'JWT',
            ],
        ],
        'throttle' => [
            'type' => 'rate_limiting',
            'description' => '请求频率限制中间件',
            'parameters' => ['requests', 'minutes'],
        ],
        'cors' => [
            'type' => 'cors',
            'description' => '跨域资源共享中间件',
        ],
        'csrf' => [
            'type' => 'csrf',
            'description' => 'CSRF 保护中间件',
        ],
        'session' => [
            'type' => 'session',
            'description' => '会话管理中间件',
        ],
        'cache' => [
            'type' => 'caching',
            'description' => '缓存中间件',
        ],
        'log' => [
            'type' => 'logging',
            'description' => '日志记录中间件',
        ],
    ];

    /**
     * 安全方案映射
     */
    protected array $securitySchemes = [];

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->docBlockParser = new DocBlockParser();
        $this->annotationParser = new AnnotationParser();
    }

    /**
     * 分析控制器中间件
     *
     * @param string $className 控制器类名
     * @return array
     * @throws AnalysisException
     */
    public function analyzeController(string $className): array
    {
        try {
            if (!class_exists($className)) {
                throw new AnalysisException("Controller class {$className} not found");
            }

            $reflection = new ReflectionClass($className);
            
            return [
                'class' => $className,
                'class_middleware' => $this->analyzeClassMiddleware($reflection),
                'method_middleware' => $this->analyzeMethodMiddleware($reflection),
                'security_schemes' => $this->extractSecuritySchemes($reflection),
                'global_middleware' => $this->analyzeGlobalMiddleware(),
            ];

        } catch (\Exception $e) {
            throw new AnalysisException("Failed to analyze middleware for {$className}: " . $e->getMessage());
        }
    }

    /**
     * 分析类级别中间件
     *
     * @param ReflectionClass $reflection
     * @return array
     */
    protected function analyzeClassMiddleware(ReflectionClass $reflection): array
    {
        $middleware = [];

        // 从注解中提取
        $classAnnotations = $this->annotationParser->parseClassAnnotations($reflection);
        foreach ($classAnnotations['middleware'] as $middlewareAnnotation) {
            $parsed = $middlewareAnnotation['parsed'] ?? [];
            if (!empty($parsed['middleware'])) {
                foreach ($parsed['middleware'] as $name) {
                    $middleware[] = $this->analyzeMiddleware($name);
                }
            }
        }

        // 从 DocBlock 中提取
        $docComment = $reflection->getDocComment();
        if ($docComment) {
            $parsed = $this->docBlockParser->parse($docComment);
            foreach ($parsed['tags'] as $tag) {
                if ($tag['name'] === 'middleware') {
                    $middleware[] = $this->analyzeMiddleware($tag['content']);
                }
            }
        }

        return $middleware;
    }

    /**
     * 分析方法级别中间件
     *
     * @param ReflectionClass $reflection
     * @return array
     */
    protected function analyzeMethodMiddleware(ReflectionClass $reflection): array
    {
        $methodMiddleware = [];
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            if ($this->shouldSkipMethod($method)) {
                continue;
            }

            $middleware = $this->analyzeMethodMiddlewareForMethod($method);
            if (!empty($middleware)) {
                $methodMiddleware[$method->getName()] = $middleware;
            }
        }

        return $methodMiddleware;
    }

    /**
     * 分析单个方法的中间件
     *
     * @param ReflectionMethod $method
     * @return array
     */
    protected function analyzeMethodMiddlewareForMethod(ReflectionMethod $method): array
    {
        $middleware = [];

        // 从注解中提取
        $methodAnnotations = $this->annotationParser->parseMethodAnnotations($method);
        foreach ($methodAnnotations['middleware'] as $middlewareAnnotation) {
            $parsed = $middlewareAnnotation['parsed'] ?? [];
            if (!empty($parsed['middleware'])) {
                foreach ($parsed['middleware'] as $name) {
                    $middleware[] = $this->analyzeMiddleware($name);
                }
            }
        }

        // 从 DocBlock 中提取
        $docComment = $method->getDocComment();
        if ($docComment) {
            $parsed = $this->docBlockParser->parse($docComment);
            foreach ($parsed['tags'] as $tag) {
                if ($tag['name'] === 'middleware') {
                    $middleware[] = $this->analyzeMiddleware($tag['content']);
                }
            }
        }

        return $middleware;
    }

    /**
     * 分析单个中间件
     *
     * @param string $middlewareName
     * @return array
     */
    protected function analyzeMiddleware(string $middlewareName): array
    {
        // 解析中间件名称和参数
        $parts = explode(':', $middlewareName, 2);
        $name = trim($parts[0]);
        $parameters = isset($parts[1]) ? explode(',', $parts[1]) : [];

        $middleware = [
            'name' => $name,
            'original' => $middlewareName,
            'parameters' => array_map('trim', $parameters),
            'type' => 'custom',
            'description' => '',
            'security' => null,
        ];

        // 检查是否为内置中间件
        if (isset($this->builtinMiddleware[$name])) {
            $builtin = $this->builtinMiddleware[$name];
            $middleware = array_merge($middleware, $builtin);
            
            // 处理参数化中间件
            if ($name === 'throttle' && !empty($parameters)) {
                $middleware['description'] = sprintf(
                    '请求频率限制：%s 次/%s 分钟',
                    $parameters[0] ?? '60',
                    $parameters[1] ?? '1'
                );
            }
        } else {
            // 尝试分析自定义中间件
            $middleware = array_merge($middleware, $this->analyzeCustomMiddleware($name));
        }

        return $middleware;
    }

    /**
     * 分析自定义中间件
     *
     * @param string $middlewareName
     * @return array
     */
    protected function analyzeCustomMiddleware(string $middlewareName): array
    {
        $info = [
            'type' => 'custom',
            'description' => "自定义中间件: {$middlewareName}",
        ];

        // 尝试根据命名推断中间件类型
        $patterns = [
            '/auth/i' => [
                'type' => 'authentication',
                'description' => '认证中间件',
                'security' => [
                    'type' => 'http',
                    'scheme' => 'bearer',
                ],
            ],
            '/admin|role|permission/i' => [
                'type' => 'authorization',
                'description' => '权限控制中间件',
            ],
            '/throttle|limit|rate/i' => [
                'type' => 'rate_limiting',
                'description' => '频率限制中间件',
            ],
            '/cors/i' => [
                'type' => 'cors',
                'description' => '跨域中间件',
            ],
            '/csrf/i' => [
                'type' => 'csrf',
                'description' => 'CSRF 保护中间件',
            ],
            '/log/i' => [
                'type' => 'logging',
                'description' => '日志中间件',
            ],
            '/cache/i' => [
                'type' => 'caching',
                'description' => '缓存中间件',
            ],
        ];

        foreach ($patterns as $pattern => $config) {
            if (preg_match($pattern, $middlewareName)) {
                $info = array_merge($info, $config);
                break;
            }
        }

        return $info;
    }

    /**
     * 提取安全方案
     *
     * @param ReflectionClass $reflection
     * @return array
     */
    protected function extractSecuritySchemes(ReflectionClass $reflection): array
    {
        $schemes = [];

        // 从类级别中间件提取
        $classMiddleware = $this->analyzeClassMiddleware($reflection);
        foreach ($classMiddleware as $middleware) {
            if (!empty($middleware['security'])) {
                $schemeName = $this->generateSecuritySchemeName($middleware);
                $schemes[$schemeName] = $middleware['security'];
            }
        }

        // 从方法级别中间件提取
        $methodMiddleware = $this->analyzeMethodMiddleware($reflection);
        foreach ($methodMiddleware as $methodName => $middlewares) {
            foreach ($middlewares as $middleware) {
                if (!empty($middleware['security'])) {
                    $schemeName = $this->generateSecuritySchemeName($middleware);
                    $schemes[$schemeName] = $middleware['security'];
                }
            }
        }

        return $schemes;
    }

    /**
     * 分析全局中间件
     *
     * @return array
     */
    protected function analyzeGlobalMiddleware(): array
    {
        // 这里可以分析 ThinkPHP 的全局中间件配置
        // 简化实现，返回常见的全局中间件
        return [
            [
                'name' => 'session',
                'type' => 'session',
                'description' => '全局会话中间件',
                'global' => true,
            ],
            [
                'name' => 'csrf',
                'type' => 'csrf',
                'description' => '全局 CSRF 保护',
                'global' => true,
            ],
        ];
    }

    /**
     * 生成安全方案名称
     *
     * @param array $middleware
     * @return string
     */
    protected function generateSecuritySchemeName(array $middleware): string
    {
        $type = $middleware['type'] ?? 'custom';
        $name = $middleware['name'] ?? 'unknown';
        
        return ucfirst($type) . 'Security';
    }

    /**
     * 生成 OpenAPI 安全定义
     *
     * @param array $middlewareInfo
     * @return array
     */
    public function generateOpenApiSecurity(array $middlewareInfo): array
    {
        $security = [];
        $securitySchemes = [];

        // 收集所有安全方案
        foreach ($middlewareInfo['security_schemes'] as $schemeName => $scheme) {
            $securitySchemes[$schemeName] = $scheme;
        }

        // 生成安全要求
        $allMiddleware = array_merge(
            $middlewareInfo['class_middleware'],
            ...array_values($middlewareInfo['method_middleware'])
        );

        foreach ($allMiddleware as $middleware) {
            if (!empty($middleware['security'])) {
                $schemeName = $this->generateSecuritySchemeName($middleware);
                if (!in_array([$schemeName => []], $security)) {
                    $security[] = [$schemeName => []];
                }
            }
        }

        return [
            'security' => $security,
            'securitySchemes' => $securitySchemes,
        ];
    }

    /**
     * 检查是否应该跳过方法
     *
     * @param ReflectionMethod $method
     * @return bool
     */
    protected function shouldSkipMethod(ReflectionMethod $method): bool
    {
        $methodName = $method->getName();

        // 跳过魔术方法
        if (str_starts_with($methodName, '__')) {
            return true;
        }

        // 跳过 ThinkPHP 框架方法
        if (in_array($methodName, ['initialize', '_empty', '_initialize'])) {
            return true;
        }

        return false;
    }

    /**
     * 获取中间件统计信息
     *
     * @param array $middlewareInfo
     * @return array
     */
    public function getMiddlewareStats(array $middlewareInfo): array
    {
        $stats = [
            'total_middleware' => 0,
            'security_middleware' => 0,
            'custom_middleware' => 0,
            'global_middleware' => count($middlewareInfo['global_middleware']),
            'types' => [],
        ];

        $allMiddleware = array_merge(
            $middlewareInfo['class_middleware'],
            ...array_values($middlewareInfo['method_middleware'])
        );

        foreach ($allMiddleware as $middleware) {
            $stats['total_middleware']++;
            
            if (!empty($middleware['security'])) {
                $stats['security_middleware']++;
            }
            
            if ($middleware['type'] === 'custom') {
                $stats['custom_middleware']++;
            }
            
            $type = $middleware['type'];
            $stats['types'][$type] = ($stats['types'][$type] ?? 0) + 1;
        }

        return $stats;
    }
}
