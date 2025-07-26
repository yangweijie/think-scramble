<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Generator;

use Yangweijie\ThinkScramble\Contracts\ConfigInterface;
use Yangweijie\ThinkScramble\Exception\GenerationException;
use Yangweijie\ThinkScramble\Analyzer\FileUploadAnalyzer;
use Yangweijie\ThinkScramble\Analyzer\ValidateAnnotationAnalyzer;
use Yangweijie\ThinkScramble\Analyzer\AnnotationParser;

/**
 * OpenAPI 参数提取器
 * 
 * 从路由和控制器信息中提取 OpenAPI 参数定义
 */
class ParameterExtractor
{
    /**
     * 配置接口
     */
    protected ConfigInterface $config;

    /**
     * 模式生成器
     */
    protected SchemaGenerator $schemaGenerator;

    /**
     * 文件上传分析器
     */
    protected FileUploadAnalyzer $fileUploadAnalyzer;

    /**
     * 验证注解分析器
     */
    protected ValidateAnnotationAnalyzer $validateAnalyzer;

    /**
     * 注解解析器
     */
    protected AnnotationParser $annotationParser;

    /**
     * 构造函数
     *
     * @param ConfigInterface $config 配置接口
     */
    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
        $this->schemaGenerator = new SchemaGenerator($config);
        $this->fileUploadAnalyzer = new FileUploadAnalyzer();
        $this->validateAnalyzer = new ValidateAnnotationAnalyzer();
        $this->annotationParser = new AnnotationParser();
    }

    /**
     * 提取参数
     *
     * @param array $routeInfo 路由信息
     * @param array $controllerInfo 控制器信息
     * @return array
     * @throws GenerationException
     */
    public function extractParameters(array $routeInfo, array $controllerInfo): array
    {
        try {
            $parameters = [];

            // 提取路径参数
            $pathParameters = $this->extractPathParameters($routeInfo);
            $parameters = array_merge($parameters, $pathParameters);

            // 提取查询参数
            $queryParameters = $this->extractQueryParameters($routeInfo, $controllerInfo);
            $parameters = array_merge($parameters, $queryParameters);

            // 提取头部参数
            $headerParameters = $this->extractHeaderParameters($routeInfo, $controllerInfo);
            $parameters = array_merge($parameters, $headerParameters);

            // 提取文件上传参数
            $fileParameters = $this->extractFileUploadParameters($routeInfo, $controllerInfo);
            $parameters = array_merge($parameters, $fileParameters);

            // 提取注解参数
            $annotationParameters = $this->extractAnnotationParameters($routeInfo, $controllerInfo);
            $parameters = array_merge($parameters, $annotationParameters);

            return $parameters;

        } catch (\Exception $e) {
            throw new GenerationException("Failed to extract parameters: " . $e->getMessage());
        }
    }

    /**
     * 提取路径参数
     *
     * @param array $routeInfo 路由信息
     * @return array
     */
    protected function extractPathParameters(array $routeInfo): array
    {
        $parameters = [];
        $routeParameters = $routeInfo['parameters'] ?? [];

        foreach ($routeParameters as $param) {
            $parameters[] = [
                'name' => $param['name'],
                'in' => 'path',
                'required' => $param['required'] ?? true,
                'description' => $this->generateParameterDescription($param['name'], 'path'),
                'schema' => $this->generateParameterSchema($param),
            ];
        }

        return $parameters;
    }

    /**
     * 提取查询参数
     *
     * @param array $routeInfo 路由信息
     * @param array $controllerInfo 控制器信息
     * @return array
     */
    protected function extractQueryParameters(array $routeInfo, array $controllerInfo): array
    {
        $parameters = [];
        $action = $routeInfo['action'] ?? '';

        if (!$action || !isset($controllerInfo['methods'][$action])) {
            return $parameters;
        }

        $methodInfo = $controllerInfo['methods'][$action];
        $methodParameters = $methodInfo['parameters'] ?? [];

        foreach ($methodParameters as $param) {
            // 跳过路径参数
            if ($this->isPathParameter($param['name'], $routeInfo)) {
                continue;
            }

            // 跳过请求对象参数
            if ($this->isRequestParameter($param)) {
                continue;
            }

            $parameters[] = [
                'name' => $param['name'],
                'in' => 'query',
                'required' => !($param['is_optional'] ?? false),
                'description' => $this->generateParameterDescription($param['name'], 'query'),
                'schema' => $this->generateParameterSchemaFromType($param['type'] ?? null),
            ];
        }

        return $parameters;
    }

    /**
     * 提取头部参数
     *
     * @param array $routeInfo 路由信息
     * @param array $controllerInfo 控制器信息
     * @return array
     */
    protected function extractHeaderParameters(array $routeInfo, array $controllerInfo): array
    {
        $parameters = [];

        // 添加常见的头部参数
        $commonHeaders = $this->config->get('api.common_headers', []);
        
        foreach ($commonHeaders as $header) {
            $parameters[] = [
                'name' => $header['name'],
                'in' => 'header',
                'required' => $header['required'] ?? false,
                'description' => $header['description'] ?? '',
                'schema' => $header['schema'] ?? ['type' => 'string'],
            ];
        }

        return $parameters;
    }

    /**
     * 提取文件上传参数
     *
     * @param array $routeInfo 路由信息
     * @param array $controllerInfo 控制器信息
     * @return array
     */
    protected function extractFileUploadParameters(array $routeInfo, array $controllerInfo): array
    {
        $parameters = [];
        $action = $routeInfo['action'] ?? '';

        if (!$action || !isset($controllerInfo['methods'][$action])) {
            return $parameters;
        }

        // 获取方法反射
        $className = $controllerInfo['class'] ?? '';
        if (!$className || !class_exists($className)) {
            return $parameters;
        }

        try {
            $reflection = new \ReflectionClass($className);
            if (!$reflection->hasMethod($action)) {
                return $parameters;
            }

            $method = $reflection->getMethod($action);
            $fileUploads = $this->fileUploadAnalyzer->analyzeMethod($method);

            foreach ($fileUploads as $upload) {
                $parameters[] = $this->fileUploadAnalyzer->generateOpenApiParameter($upload);
            }
        } catch (\Exception $e) {
            // 忽略分析错误
        }

        return $parameters;
    }

    /**
     * 提取注解参数
     *
     * @param array $routeInfo 路由信息
     * @param array $controllerInfo 控制器信息
     * @return array
     */
    protected function extractAnnotationParameters(array $routeInfo, array $controllerInfo): array
    {
        $parameters = [];
        $action = $routeInfo['action'] ?? '';
        $className = $controllerInfo['class'] ?? '';

        if (!$action || !$className || !class_exists($className)) {
            return $parameters;
        }

        try {
            $reflection = new \ReflectionClass($className);
            if (!$reflection->hasMethod($action)) {
                return $parameters;
            }

            $method = $reflection->getMethod($action);

            // 分析验证注解
            $validateInfo = $this->validateAnalyzer->analyzeMethod($method);
            if (!empty($validateInfo['openapi_parameters'])) {
                $parameters = array_merge($parameters, $validateInfo['openapi_parameters']);
            }

            // 分析其他注解参数
            $methodAnnotations = $this->annotationParser->parseMethodAnnotations($method);
            $apiParameters = $this->extractApiParameters($methodAnnotations);
            $parameters = array_merge($parameters, $apiParameters);

        } catch (\Exception $e) {
            // 忽略分析错误
        }

        return $parameters;
    }

    /**
     * 从注解中提取 API 参数
     *
     * @param array $methodAnnotations
     * @return array
     */
    protected function extractApiParameters(array $methodAnnotations): array
    {
        $parameters = [];

        foreach ($methodAnnotations['openapi'] as $annotation) {
            if ($annotation['type'] === 'ApiParam') {
                $parsed = $annotation['parsed'] ?? [];
                if (!empty($parsed['name'])) {
                    $parameters[] = [
                        'name' => $parsed['name'],
                        'in' => 'query',
                        'required' => false,
                        'description' => $parsed['description'] ?? '',
                        'schema' => $this->mapApiParamType($parsed['type'] ?? 'string'),
                    ];
                }
            }
        }

        return $parameters;
    }

    /**
     * 映射 API 参数类型
     *
     * @param string $type
     * @return array
     */
    protected function mapApiParamType(string $type): array
    {
        return match (strtolower($type)) {
            'number', 'int', 'integer' => ['type' => 'integer'],
            'float', 'double' => ['type' => 'number'],
            'bool', 'boolean' => ['type' => 'boolean'],
            'array' => ['type' => 'array'],
            'object' => ['type' => 'object'],
            default => ['type' => 'string'],
        };
    }

    /**
     * 生成参数模式
     *
     * @param array $param 参数信息
     * @return array
     */
    protected function generateParameterSchema(array $param): array
    {
        $type = $param['type'] ?? 'string';
        $pattern = $param['pattern'] ?? '';

        $schema = [
            'type' => $this->mapParameterType($type),
        ];

        // 添加模式约束
        if (!empty($pattern)) {
            $schema['pattern'] = $pattern;
        }

        // 添加示例值
        $schema['example'] = $this->generateExampleValue($type);

        return $schema;
    }

    /**
     * 从类型生成参数模式
     *
     * @param mixed $type 类型信息
     * @return array
     */
    protected function generateParameterSchemaFromType($type): array
    {
        if ($type === null) {
            return ['type' => 'string'];
        }

        if (is_object($type)) {
            return $this->schemaGenerator->generateFromType($type);
        }

        if (is_string($type)) {
            return [
                'type' => $this->mapParameterType($type),
                'example' => $this->generateExampleValue($type),
            ];
        }

        return ['type' => 'string'];
    }

    /**
     * 映射参数类型
     *
     * @param string $type 类型
     * @return string
     */
    protected function mapParameterType(string $type): string
    {
        return match (strtolower($type)) {
            'int', 'integer' => 'integer',
            'float', 'double', 'number' => 'number',
            'bool', 'boolean' => 'boolean',
            'array' => 'array',
            default => 'string',
        };
    }

    /**
     * 生成示例值
     *
     * @param string $type 类型
     * @return mixed
     */
    protected function generateExampleValue(string $type)
    {
        return match (strtolower($type)) {
            'int', 'integer' => 1,
            'float', 'double', 'number' => 1.0,
            'bool', 'boolean' => true,
            'array' => [],
            default => 'string',
        };
    }

    /**
     * 生成参数描述
     *
     * @param string $name 参数名称
     * @param string $location 参数位置
     * @return string
     */
    protected function generateParameterDescription(string $name, string $location): string
    {
        $descriptions = [
            'id' => 'Resource identifier',
            'page' => 'Page number for pagination',
            'limit' => 'Number of items per page',
            'offset' => 'Number of items to skip',
            'sort' => 'Sort field',
            'order' => 'Sort order (asc/desc)',
            'search' => 'Search query',
            'filter' => 'Filter criteria',
        ];

        if (isset($descriptions[$name])) {
            return $descriptions[$name];
        }

        return ucfirst($name) . " parameter";
    }

    /**
     * 检查是否为路径参数
     *
     * @param string $paramName 参数名称
     * @param array $routeInfo 路由信息
     * @return bool
     */
    protected function isPathParameter(string $paramName, array $routeInfo): bool
    {
        $routeParameters = $routeInfo['parameters'] ?? [];
        
        foreach ($routeParameters as $param) {
            if ($param['name'] === $paramName) {
                return true;
            }
        }

        return false;
    }

    /**
     * 检查是否为请求对象参数
     *
     * @param array $param 参数信息
     * @return bool
     */
    protected function isRequestParameter(array $param): bool
    {
        $type = $param['type'] ?? null;
        
        if (is_string($type)) {
            // 检查是否为 Request 类型
            if (str_contains(strtolower($type), 'request')) {
                return true;
            }
        }

        return false;
    }

    /**
     * 提取验证器参数
     *
     * @param array $validatorInfo 验证器信息
     * @return array
     */
    public function extractValidatorParameters(array $validatorInfo): array
    {
        $parameters = [];
        $openApiParameters = $validatorInfo['openapi_parameters'] ?? [];

        foreach ($openApiParameters as $param) {
            $parameters[] = [
                'name' => $param['name'],
                'in' => $param['in'] ?? 'query',
                'required' => $param['required'] ?? false,
                'description' => $param['description'] ?? $this->generateParameterDescription($param['name'], 'query'),
                'schema' => $param['schema'] ?? ['type' => 'string'],
            ];
        }

        return $parameters;
    }

    /**
     * 合并参数列表
     *
     * @param array $parameters1 参数列表1
     * @param array $parameters2 参数列表2
     * @return array
     */
    public function mergeParameters(array $parameters1, array $parameters2): array
    {
        $merged = $parameters1;
        $existingNames = array_column($parameters1, 'name');

        foreach ($parameters2 as $param) {
            if (!in_array($param['name'], $existingNames)) {
                $merged[] = $param;
            }
        }

        return $merged;
    }

    /**
     * 过滤参数
     *
     * @param array $parameters 参数列表
     * @param callable $filter 过滤函数
     * @return array
     */
    public function filterParameters(array $parameters, callable $filter): array
    {
        return array_filter($parameters, $filter);
    }

    /**
     * 排序参数
     *
     * @param array $parameters 参数列表
     * @return array
     */
    public function sortParameters(array $parameters): array
    {
        // 按参数位置和名称排序
        usort($parameters, function ($a, $b) {
            $locationOrder = ['path' => 1, 'query' => 2, 'header' => 3, 'cookie' => 4];
            
            $aOrder = $locationOrder[$a['in']] ?? 5;
            $bOrder = $locationOrder[$b['in']] ?? 5;
            
            if ($aOrder !== $bOrder) {
                return $aOrder <=> $bOrder;
            }
            
            return strcmp($a['name'], $b['name']);
        });

        return $parameters;
    }
}
