<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Generator;

use Yangweijie\ThinkScramble\Contracts\ConfigInterface;
use Yangweijie\ThinkScramble\Exception\GenerationException;

/**
 * OpenAPI 文档构建器
 * 
 * 构建符合 OpenAPI 3.0 规范的完整 API 文档结构
 */
class DocumentBuilder
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
     * 参数提取器
     */
    protected ParameterExtractor $parameterExtractor;

    /**
     * 文档数据
     */
    protected array $document = [];

    /**
     * 构造函数
     *
     * @param ConfigInterface $config 配置接口
     */
    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
        $this->schemaGenerator = new SchemaGenerator($config);
        $this->parameterExtractor = new ParameterExtractor($config);
        
        $this->initializeDocument();
    }

    /**
     * 初始化文档结构
     *
     * @return void
     */
    protected function initializeDocument(): void
    {
        $this->document = [
            'openapi' => '3.0.3',
            'info' => $this->buildInfo(),
            'servers' => $this->buildServers(),
            'paths' => [],
            'components' => [
                'schemas' => [],
                'parameters' => [],
                'responses' => [],
                'securitySchemes' => [],
            ],
            'tags' => [],
            'security' => [],
        ];
    }

    /**
     * 构建文档信息
     *
     * @return array
     */
    protected function buildInfo(): array
    {
        return [
            'title' => $this->config->get('info.title', 'API Documentation'),
            'description' => $this->config->get('info.description', 'Generated API documentation'),
            'version' => $this->config->get('info.version', '1.0.0'),
            'contact' => $this->config->get('info.contact', []),
            'license' => $this->config->get('info.license', []),
        ];
    }

    /**
     * 构建服务器信息
     *
     * @return array
     */
    protected function buildServers(): array
    {
        $servers = $this->config->get('servers', []);
        
        if (empty($servers)) {
            $servers = [
                [
                    'url' => $this->config->get('api.base_url', '/'),
                    'description' => 'Development server',
                ],
            ];
        }

        return $servers;
    }

    /**
     * 添加路径
     *
     * @param string $path 路径
     * @param string $method HTTP 方法
     * @param array $operation 操作定义
     * @return self
     */
    public function addPath(string $path, string $method, array $operation): self
    {
        $method = strtolower($method);
        
        if (!isset($this->document['paths'][$path])) {
            $this->document['paths'][$path] = [];
        }

        $this->document['paths'][$path][$method] = $operation;

        return $this;
    }

    /**
     * 添加组件模式
     *
     * @param string $name 模式名称
     * @param array $schema 模式定义
     * @return self
     */
    public function addSchema(string $name, array $schema): self
    {
        $this->document['components']['schemas'][$name] = $schema;
        return $this;
    }

    /**
     * 添加组件参数
     *
     * @param string $name 参数名称
     * @param array $parameter 参数定义
     * @return self
     */
    public function addParameter(string $name, array $parameter): self
    {
        $this->document['components']['parameters'][$name] = $parameter;
        return $this;
    }

    /**
     * 添加组件响应
     *
     * @param string $name 响应名称
     * @param array $response 响应定义
     * @return self
     */
    public function addResponse(string $name, array $response): self
    {
        $this->document['components']['responses'][$name] = $response;
        return $this;
    }

    /**
     * 添加安全方案
     *
     * @param string $name 方案名称
     * @param array $scheme 安全方案定义
     * @return self
     */
    public function addSecurityScheme(string $name, array $scheme): self
    {
        $this->document['components']['securitySchemes'][$name] = $scheme;
        return $this;
    }

    /**
     * 添加标签
     *
     * @param array $tag 标签定义
     * @return self
     */
    public function addTag(array $tag): self
    {
        $this->document['tags'][] = $tag;
        return $this;
    }

    /**
     * 设置全局安全
     *
     * @param array $security 安全配置
     * @return self
     */
    public function setSecurity(array $security): self
    {
        $this->document['security'] = $security;
        return $this;
    }

    /**
     * 构建操作定义
     *
     * @param array $routeInfo 路由信息
     * @param array $controllerInfo 控制器信息
     * @param array $middlewareInfo 中间件信息
     * @return array
     * @throws GenerationException
     */
    public function buildOperation(array $routeInfo, array $controllerInfo, array $middlewareInfo = []): array
    {
        try {
            $operation = [
                'summary' => $this->generateSummary($routeInfo, $controllerInfo),
                'description' => $this->generateDescription($routeInfo, $controllerInfo),
                'operationId' => $this->generateOperationId($routeInfo),
                'tags' => $this->generateTags($routeInfo, $controllerInfo),
                'parameters' => $this->parameterExtractor->extractParameters($routeInfo, $controllerInfo),
                'responses' => $this->generateResponses($routeInfo, $controllerInfo),
            ];

            // 添加请求体（如果需要）
            $requestBody = $this->generateRequestBody($routeInfo, $controllerInfo);
            if ($requestBody) {
                $operation['requestBody'] = $requestBody;
            }

            // 添加安全要求
            $security = $this->generateSecurity($middlewareInfo);
            if ($security) {
                $operation['security'] = $security;
            }

            return $operation;

        } catch (\Exception $e) {
            throw new GenerationException("Failed to build operation: " . $e->getMessage());
        }
    }

    /**
     * 生成操作摘要
     *
     * @param array $routeInfo 路由信息
     * @param array $controllerInfo 控制器信息
     * @return string
     */
    protected function generateSummary(array $routeInfo, array $controllerInfo): string
    {
        $action = $routeInfo['action'] ?? 'unknown';
        $controller = $routeInfo['controller'] ?? 'unknown';
        
        return ucfirst($action) . ' ' . $controller;
    }

    /**
     * 生成操作描述
     *
     * @param array $routeInfo 路由信息
     * @param array $controllerInfo 控制器信息
     * @return string
     */
    protected function generateDescription(array $routeInfo, array $controllerInfo): string
    {
        $action = $routeInfo['action'] ?? 'unknown';
        
        if (isset($controllerInfo['methods'][$action]['doc_comment'])) {
            // 从 DocComment 提取描述
            $docComment = $controllerInfo['methods'][$action]['doc_comment'];
            if (preg_match('/\/\*\*\s*\n\s*\*\s*(.+?)\s*\n/', $docComment, $matches)) {
                return trim($matches[1]);
            }
        }

        return "Execute {$action} action";
    }

    /**
     * 生成操作ID
     *
     * @param array $routeInfo 路由信息
     * @return string
     */
    protected function generateOperationId(array $routeInfo): string
    {
        $controller = $routeInfo['controller'] ?? 'unknown';
        $action = $routeInfo['action'] ?? 'unknown';
        $method = strtolower($routeInfo['method'] ?? 'get');
        
        return $method . ucfirst($controller) . ucfirst($action);
    }

    /**
     * 生成标签
     *
     * @param array $routeInfo 路由信息
     * @param array $controllerInfo 控制器信息
     * @return array
     */
    protected function generateTags(array $routeInfo, array $controllerInfo): array
    {
        $controller = $routeInfo['controller'] ?? 'unknown';
        return [$controller];
    }

    /**
     * 生成响应
     *
     * @param array $routeInfo 路由信息
     * @param array $controllerInfo 控制器信息
     * @return array
     */
    protected function generateResponses(array $routeInfo, array $controllerInfo): array
    {
        $responses = [
            '200' => [
                'description' => 'Successful response',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'code' => ['type' => 'integer', 'example' => 200],
                                'message' => ['type' => 'string', 'example' => 'Success'],
                                'data' => ['type' => 'object'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // 添加错误响应
        $responses['400'] = [
            'description' => 'Bad Request',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'code' => ['type' => 'integer', 'example' => 400],
                            'message' => ['type' => 'string', 'example' => 'Bad Request'],
                        ],
                    ],
                ],
            ],
        ];

        $responses['500'] = [
            'description' => 'Internal Server Error',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'code' => ['type' => 'integer', 'example' => 500],
                            'message' => ['type' => 'string', 'example' => 'Internal Server Error'],
                        ],
                    ],
                ],
            ],
        ];

        return $responses;
    }

    /**
     * 生成请求体
     *
     * @param array $routeInfo 路由信息
     * @param array $controllerInfo 控制器信息
     * @return array|null
     */
    protected function generateRequestBody(array $routeInfo, array $controllerInfo): ?array
    {
        $method = strtoupper($routeInfo['method'] ?? 'GET');
        
        // 只有 POST、PUT、PATCH 等方法才有请求体
        if (!in_array($method, ['POST', 'PUT', 'PATCH'])) {
            return null;
        }

        return [
            'required' => true,
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [],
                    ],
                ],
                'application/x-www-form-urlencoded' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [],
                    ],
                ],
            ],
        ];
    }

    /**
     * 生成安全要求
     *
     * @param array $middlewareInfo 中间件信息
     * @return array|null
     */
    protected function generateSecurity(array $middlewareInfo): ?array
    {
        if (empty($middlewareInfo) || !isset($middlewareInfo['features']['requires_auth']) || !$middlewareInfo['features']['requires_auth']) {
            return null;
        }

        return [
            ['bearerAuth' => []],
        ];
    }

    /**
     * 获取文档数据
     *
     * @return array
     */
    public function getDocument(): array
    {
        return $this->document;
    }

    /**
     * 转换为 JSON 格式
     *
     * @param bool $pretty 是否格式化
     * @return string
     * @throws GenerationException
     */
    public function toJson(bool $pretty = true): string
    {
        $flags = $pretty ? JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE : 0;
        
        $json = json_encode($this->document, $flags);
        
        if ($json === false) {
            throw new GenerationException('Failed to encode document to JSON: ' . json_last_error_msg());
        }

        return $json;
    }

    /**
     * 转换为 YAML 格式
     *
     * @return string
     * @throws GenerationException
     */
    public function toYaml(): string
    {
        if (!function_exists('yaml_emit')) {
            throw new GenerationException('YAML extension is not available');
        }

        $yaml = yaml_emit($this->document);
        
        if ($yaml === false) {
            throw new GenerationException('Failed to encode document to YAML');
        }

        return $yaml;
    }
}
