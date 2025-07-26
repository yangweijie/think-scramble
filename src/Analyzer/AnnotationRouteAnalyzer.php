<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Analyzer;

use ReflectionClass;
use ReflectionMethod;
use Yangweijie\ThinkScramble\Exception\AnalysisException;

/**
 * 注解路由分析器
 * 
 * 分析控制器中的路由注解，生成路由信息
 */
class AnnotationRouteAnalyzer
{
    /**
     * 注解解析器
     */
    protected AnnotationParser $annotationParser;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->annotationParser = new AnnotationParser();
    }

    /**
     * 分析控制器的注解路由
     *
     * @param string $className 控制器类名
     * @return array
     * @throws AnalysisException
     */
    public function analyzeController(string $className): array
    {
        try {
            if (!class_exists($className)) {
                throw new AnalysisException("Class {$className} not found");
            }

            $reflection = new ReflectionClass($className);
            $routes = [];

            // 分析类级别的注解
            $classAnnotations = $this->annotationParser->parseClassAnnotations($reflection);
            $classRoutePrefix = $this->extractRoutePrefix($classAnnotations);
            $classMiddleware = $this->extractClassMiddleware($classAnnotations);

            // 分析方法级别的注解
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
            foreach ($methods as $method) {
                if ($this->shouldSkipMethod($method)) {
                    continue;
                }

                $methodRoutes = $this->analyzeMethod($method, $classRoutePrefix, $classMiddleware);
                $routes = array_merge($routes, $methodRoutes);
            }

            return [
                'class' => $className,
                'class_annotations' => $classAnnotations,
                'routes' => $routes,
            ];

        } catch (\Exception $e) {
            throw new AnalysisException("Failed to analyze controller {$className}: " . $e->getMessage());
        }
    }

    /**
     * 分析方法的注解路由
     *
     * @param ReflectionMethod $method
     * @param string $classPrefix
     * @param array $classMiddleware
     * @return array
     */
    protected function analyzeMethod(ReflectionMethod $method, string $classPrefix = '', array $classMiddleware = []): array
    {
        $methodAnnotations = $this->annotationParser->parseMethodAnnotations($method);
        $routes = [];

        // 处理路由注解
        foreach ($methodAnnotations['routes'] as $routeAnnotation) {
            $route = $this->buildRouteFromAnnotation(
                $routeAnnotation,
                $method,
                $classPrefix,
                $classMiddleware,
                $methodAnnotations
            );
            
            if ($route) {
                $routes[] = $route;
            }
        }

        // 如果没有路由注解，但有其他相关注解，生成默认路由
        if (empty($routes) && $this->hasRelevantAnnotations($methodAnnotations)) {
            $routes[] = $this->buildDefaultRoute($method, $classPrefix, $classMiddleware, $methodAnnotations);
        }

        return $routes;
    }

    /**
     * 从注解构建路由信息
     *
     * @param array $routeAnnotation
     * @param ReflectionMethod $method
     * @param string $classPrefix
     * @param array $classMiddleware
     * @param array $methodAnnotations
     * @return array|null
     */
    protected function buildRouteFromAnnotation(
        array $routeAnnotation,
        ReflectionMethod $method,
        string $classPrefix,
        array $classMiddleware,
        array $methodAnnotations
    ): ?array {
        $parsed = $routeAnnotation['parsed'] ?? [];
        
        if (empty($parsed['path']) && empty($parsed['method'])) {
            return null;
        }

        // 构建完整路径
        $path = $this->buildFullPath($classPrefix, $parsed['path'] ?? '');
        
        // 合并中间件
        $middleware = array_merge($classMiddleware, $this->extractMethodMiddleware($methodAnnotations));

        return [
            'path' => $path,
            'method' => $parsed['method'] ?? 'GET',
            'controller' => $method->getDeclaringClass()->getName(),
            'action' => $method->getName(),
            'name' => $parsed['name'] ?? '',
            'middleware' => $middleware,
            'domain' => $parsed['domain'] ?? '',
            'annotation_type' => $routeAnnotation['type'],
            'validate' => $this->extractValidateInfo($methodAnnotations),
            'openapi' => $this->extractOpenApiInfo($methodAnnotations),
            'source' => 'annotation',
        ];
    }

    /**
     * 构建默认路由
     *
     * @param ReflectionMethod $method
     * @param string $classPrefix
     * @param array $classMiddleware
     * @param array $methodAnnotations
     * @return array
     */
    protected function buildDefaultRoute(
        ReflectionMethod $method,
        string $classPrefix,
        array $classMiddleware,
        array $methodAnnotations
    ): array {
        $className = $method->getDeclaringClass()->getShortName();
        $methodName = $method->getName();
        
        // 生成默认路径
        $path = $this->buildDefaultPath($className, $methodName, $classPrefix);
        
        return [
            'path' => $path,
            'method' => 'GET',
            'controller' => $method->getDeclaringClass()->getName(),
            'action' => $methodName,
            'name' => '',
            'middleware' => array_merge($classMiddleware, $this->extractMethodMiddleware($methodAnnotations)),
            'domain' => '',
            'annotation_type' => 'default',
            'validate' => $this->extractValidateInfo($methodAnnotations),
            'openapi' => $this->extractOpenApiInfo($methodAnnotations),
            'source' => 'annotation_default',
        ];
    }

    /**
     * 提取路由前缀
     *
     * @param array $classAnnotations
     * @return string
     */
    protected function extractRoutePrefix(array $classAnnotations): string
    {
        foreach ($classAnnotations['routes'] as $route) {
            if ($route['type'] === 'Route' && !empty($route['parsed']['path'])) {
                return rtrim($route['parsed']['path'], '/');
            }
        }

        return '';
    }

    /**
     * 提取类级别中间件
     *
     * @param array $classAnnotations
     * @return array
     */
    protected function extractClassMiddleware(array $classAnnotations): array
    {
        $middleware = [];

        foreach ($classAnnotations['middleware'] as $middlewareAnnotation) {
            $parsed = $middlewareAnnotation['parsed'] ?? [];
            if (!empty($parsed['middleware'])) {
                $middleware = array_merge($middleware, $parsed['middleware']);
            }
        }

        return $middleware;
    }

    /**
     * 提取方法级别中间件
     *
     * @param array $methodAnnotations
     * @return array
     */
    protected function extractMethodMiddleware(array $methodAnnotations): array
    {
        $middleware = [];

        foreach ($methodAnnotations['middleware'] as $middlewareAnnotation) {
            $parsed = $middlewareAnnotation['parsed'] ?? [];
            if (!empty($parsed['middleware'])) {
                $middleware = array_merge($middleware, $parsed['middleware']);
            }
        }

        return $middleware;
    }

    /**
     * 提取验证信息
     *
     * @param array $methodAnnotations
     * @return array
     */
    protected function extractValidateInfo(array $methodAnnotations): array
    {
        $validate = [];

        foreach ($methodAnnotations['validate'] as $validateAnnotation) {
            $parsed = $validateAnnotation['parsed'] ?? [];
            if (!empty($parsed['class'])) {
                $validate[] = $parsed;
            }
        }

        return $validate;
    }

    /**
     * 提取 OpenAPI 信息
     *
     * @param array $methodAnnotations
     * @return array
     */
    protected function extractOpenApiInfo(array $methodAnnotations): array
    {
        return $methodAnnotations['openapi'] ?? [];
    }

    /**
     * 构建完整路径
     *
     * @param string $prefix
     * @param string $path
     * @return string
     */
    protected function buildFullPath(string $prefix, string $path): string
    {
        $prefix = trim($prefix, '/');
        $path = trim($path, '/');

        if (empty($prefix)) {
            return '/' . $path;
        }

        if (empty($path)) {
            return '/' . $prefix;
        }

        return '/' . $prefix . '/' . $path;
    }

    /**
     * 构建默认路径
     *
     * @param string $className
     * @param string $methodName
     * @param string $prefix
     * @return string
     */
    protected function buildDefaultPath(string $className, string $methodName, string $prefix): string
    {
        // 移除 Controller 后缀
        $controller = preg_replace('/Controller$/', '', $className);
        $controller = strtolower($controller);
        
        $path = $controller . '/' . $methodName;
        
        return $this->buildFullPath($prefix, $path);
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

        // 跳过非公共方法
        if (!$method->isPublic()) {
            return true;
        }

        return false;
    }

    /**
     * 检查是否有相关注解
     *
     * @param array $methodAnnotations
     * @return bool
     */
    protected function hasRelevantAnnotations(array $methodAnnotations): bool
    {
        return !empty($methodAnnotations['validate']) ||
               !empty($methodAnnotations['middleware']) ||
               !empty($methodAnnotations['openapi']);
    }

    /**
     * 获取所有注解路由
     *
     * @param array $controllers 控制器列表
     * @return array
     */
    public function getAllAnnotationRoutes(array $controllers): array
    {
        $allRoutes = [];

        foreach ($controllers as $controller) {
            try {
                $controllerRoutes = $this->analyzeController($controller);
                $allRoutes[] = $controllerRoutes;
            } catch (AnalysisException $e) {
                // 记录错误但继续处理其他控制器
                continue;
            }
        }

        return $allRoutes;
    }
}
