<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Analyzer;

use ReflectionClass;
use ReflectionMethod;
use Yangweijie\ThinkScramble\Exception\AnalysisException;

/**
 * ThinkPHP 注解解析器
 * 
 * 支持 think-annotation 扩展的所有注解类型
 */
class AnnotationParser
{
    /**
     * DocBlock 解析器
     */
    protected DocBlockParser $docBlockParser;

    /**
     * 支持的路由注解
     */
    protected array $routeAnnotations = [
        'Route', 'Get', 'Post', 'Put', 'Delete', 'Patch', 'Options', 'Head',
        'Resource', 'Group'
    ];

    /**
     * 支持的中间件注解
     */
    protected array $middlewareAnnotations = [
        'Middleware'
    ];

    /**
     * 支持的验证注解
     */
    protected array $validateAnnotations = [
        'Validate', 'ValidateRule'
    ];

    /**
     * 支持的依赖注入注解
     */
    protected array $injectAnnotations = [
        'Inject', 'Value'
    ];

    /**
     * 支持的 API 文档注解
     */
    protected array $apiAnnotations = [
        'Api', 'ApiParam', 'ApiResponse', 'ApiSuccess', 'ApiError'
    ];

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->docBlockParser = new DocBlockParser();
    }

    /**
     * 解析类注解
     *
     * @param ReflectionClass $class
     * @return array
     */
    public function parseClassAnnotations(ReflectionClass $class): array
    {
        $annotations = [];
        $docComment = $class->getDocComment();

        if ($docComment) {
            $parsed = $this->docBlockParser->parse($docComment);
            $annotations = $this->extractAnnotations($parsed['tags']);
        }

        return [
            'class' => $class->getName(),
            'annotations' => $annotations,
            'routes' => $this->extractRouteAnnotations($annotations),
            'middleware' => $this->extractMiddlewareAnnotations($annotations),
            'resource' => $this->extractResourceAnnotations($annotations),
        ];
    }

    /**
     * 解析方法注解
     *
     * @param ReflectionMethod $method
     * @return array
     */
    public function parseMethodAnnotations(ReflectionMethod $method): array
    {
        $annotations = [];
        $docComment = $method->getDocComment();

        if ($docComment) {
            $parsed = $this->docBlockParser->parse($docComment);
            $annotations = $this->extractAnnotations($parsed['tags']);
        }

        return [
            'method' => $method->getName(),
            'class' => $method->getDeclaringClass()->getName(),
            'annotations' => $annotations,
            'routes' => $this->extractRouteAnnotations($annotations),
            'middleware' => $this->extractMiddlewareAnnotations($annotations),
            'validate' => $this->extractValidateAnnotations($annotations),
            'inject' => $this->extractInjectAnnotations($annotations),
            'openapi' => $this->extractOpenApiAnnotations($annotations),
        ];
    }

    /**
     * 从标签中提取注解
     *
     * @param array $tags
     * @return array
     */
    protected function extractAnnotations(array $tags): array
    {
        $annotations = [];

        foreach ($tags as $tag) {
            $tagName = $tag['name'] ?? '';
            
            // 检查是否为注解标签
            if ($this->isAnnotationTag($tagName)) {
                $annotations[] = $this->parseAnnotationTag($tag);
            }
        }

        return $annotations;
    }

    /**
     * 检查是否为注解标签
     *
     * @param string $tagName
     * @return bool
     */
    protected function isAnnotationTag(string $tagName): bool
    {
        $allAnnotations = array_merge(
            $this->routeAnnotations,
            $this->middlewareAnnotations,
            $this->validateAnnotations,
            $this->injectAnnotations,
            $this->apiAnnotations
        );

        return in_array($tagName, $allAnnotations);
    }

    /**
     * 解析注解标签
     *
     * @param array $tag
     * @return array
     */
    protected function parseAnnotationTag(array $tag): array
    {
        $tagName = $tag['name'] ?? '';
        $content = $tag['content'] ?? '';

        $annotation = [
            'type' => $tagName,
            'content' => $content,
            'parsed' => [],
        ];

        // 根据注解类型进行特殊解析
        switch ($tagName) {
            case 'Route':
                $annotation['parsed'] = $this->parseRouteAnnotation($content);
                break;
            case 'Get':
            case 'Post':
            case 'Put':
            case 'Delete':
            case 'Patch':
            case 'Options':
            case 'Head':
                $annotation['parsed'] = $this->parseHttpMethodAnnotation($tagName, $content);
                break;
            case 'Middleware':
                $annotation['parsed'] = $this->parseMiddlewareAnnotation($content);
                break;
            case 'Validate':
                $annotation['parsed'] = $this->parseValidateAnnotation($content);
                break;
            case 'Resource':
                $annotation['parsed'] = $this->parseResourceAnnotation($content);
                break;
            case 'Inject':
                $annotation['parsed'] = $this->parseInjectAnnotation($content);
                break;
            default:
                $annotation['parsed'] = $this->parseGenericAnnotation($content);
                break;
        }

        return $annotation;
    }

    /**
     * 解析路由注解
     *
     * @param string $content
     * @return array
     */
    protected function parseRouteAnnotation(string $content): array
    {
        // 支持格式：
        // @Route("user/{id}", method="GET", name="user.show")
        // @Route("/api/users", method="POST")
        
        $route = [
            'path' => '',
            'method' => 'GET',
            'name' => '',
            'middleware' => [],
            'domain' => '',
        ];

        // 简单解析，提取路径
        if (preg_match('/["\']([^"\']+)["\']/', $content, $matches)) {
            $route['path'] = $matches[1];
        }

        // 提取方法
        if (preg_match('/method\s*=\s*["\']([^"\']+)["\']/', $content, $matches)) {
            $route['method'] = strtoupper($matches[1]);
        }

        // 提取名称
        if (preg_match('/name\s*=\s*["\']([^"\']+)["\']/', $content, $matches)) {
            $route['name'] = $matches[1];
        }

        return $route;
    }

    /**
     * 解析 HTTP 方法注解
     *
     * @param string $method
     * @param string $content
     * @return array
     */
    protected function parseHttpMethodAnnotation(string $method, string $content): array
    {
        $route = [
            'path' => '',
            'method' => strtoupper($method),
            'name' => '',
        ];

        // 提取路径
        if (preg_match('/["\']([^"\']+)["\']/', $content, $matches)) {
            $route['path'] = $matches[1];
        }

        return $route;
    }

    /**
     * 解析中间件注解
     *
     * @param string $content
     * @return array
     */
    protected function parseMiddlewareAnnotation(string $content): array
    {
        // 支持格式：
        // @Middleware("auth")
        // @Middleware({"auth", "throttle:60,1"})
        
        $middleware = [];

        // 单个中间件
        if (preg_match('/["\']([^"\']+)["\']/', $content, $matches)) {
            $middleware[] = $matches[1];
        }

        // 多个中间件（简化处理）
        if (preg_match_all('/["\']([^"\']+)["\']/', $content, $matches)) {
            $middleware = $matches[1];
        }

        return [
            'middleware' => $middleware,
        ];
    }

    /**
     * 解析验证注解
     *
     * @param string $content
     * @return array
     */
    protected function parseValidateAnnotation(string $content): array
    {
        // 支持格式：
        // @Validate("UserValidate")
        // @Validate({"name": "require", "email": "email"})
        
        $validate = [
            'class' => '',
            'rules' => [],
            'message' => [],
        ];

        // 验证器类
        if (preg_match('/["\']([^"\']+)["\']/', $content, $matches)) {
            $validate['class'] = $matches[1];
        }

        return $validate;
    }

    /**
     * 解析资源路由注解
     *
     * @param string $content
     * @return array
     */
    protected function parseResourceAnnotation(string $content): array
    {
        $resource = [
            'name' => '',
            'only' => [],
            'except' => [],
        ];

        // 提取资源名称
        if (preg_match('/["\']([^"\']+)["\']/', $content, $matches)) {
            $resource['name'] = $matches[1];
        }

        return $resource;
    }

    /**
     * 解析依赖注入注解
     *
     * @param string $content
     * @return array
     */
    protected function parseInjectAnnotation(string $content): array
    {
        return [
            'class' => trim($content, '"\''),
        ];
    }

    /**
     * 解析通用注解
     *
     * @param string $content
     * @return array
     */
    protected function parseGenericAnnotation(string $content): array
    {
        return [
            'value' => $content,
        ];
    }

    /**
     * 提取路由注解
     *
     * @param array $annotations
     * @return array
     */
    protected function extractRouteAnnotations(array $annotations): array
    {
        $routes = [];

        foreach ($annotations as $annotation) {
            if (in_array($annotation['type'], $this->routeAnnotations)) {
                $routes[] = $annotation;
            }
        }

        return $routes;
    }

    /**
     * 提取中间件注解
     *
     * @param array $annotations
     * @return array
     */
    protected function extractMiddlewareAnnotations(array $annotations): array
    {
        $middleware = [];

        foreach ($annotations as $annotation) {
            if (in_array($annotation['type'], $this->middlewareAnnotations)) {
                $middleware[] = $annotation;
            }
        }

        return $middleware;
    }

    /**
     * 提取验证注解
     *
     * @param array $annotations
     * @return array
     */
    protected function extractValidateAnnotations(array $annotations): array
    {
        $validate = [];

        foreach ($annotations as $annotation) {
            if (in_array($annotation['type'], $this->validateAnnotations)) {
                $validate[] = $annotation;
            }
        }

        return $validate;
    }

    /**
     * 提取依赖注入注解
     *
     * @param array $annotations
     * @return array
     */
    protected function extractInjectAnnotations(array $annotations): array
    {
        $inject = [];

        foreach ($annotations as $annotation) {
            if (in_array($annotation['type'], $this->injectAnnotations)) {
                $inject[] = $annotation;
            }
        }

        return $inject;
    }

    /**
     * 提取资源路由注解
     *
     * @param array $annotations
     * @return array
     */
    protected function extractResourceAnnotations(array $annotations): array
    {
        $resource = [];

        foreach ($annotations as $annotation) {
            if ($annotation['type'] === 'Resource') {
                $resource[] = $annotation;
            }
        }

        return $resource;
    }

    /**
     * 提取 OpenAPI 相关注解
     *
     * @param array $annotations
     * @return array
     */
    protected function extractOpenApiAnnotations(array $annotations): array
    {
        $openapi = [];

        foreach ($annotations as $annotation) {
            // 检查是否为 OpenAPI 相关注解
            if ($this->isOpenApiAnnotation($annotation['type'])) {
                $openapi[] = $annotation;
            }
        }

        return $openapi;
    }

    /**
     * 检查是否为 OpenAPI 注解
     *
     * @param string $type
     * @return bool
     */
    protected function isOpenApiAnnotation(string $type): bool
    {
        $openApiAnnotations = [
            'OA\Get', 'OA\Post', 'OA\Put', 'OA\Delete', 'OA\Patch',
            'OA\Parameter', 'OA\RequestBody', 'OA\Response',
            'OA\Schema', 'OA\Property', 'OA\Tag',
            'Api', 'ApiParam', 'ApiResponse', 'ApiSuccess', 'ApiError'
        ];

        return in_array($type, $openApiAnnotations);
    }
}
