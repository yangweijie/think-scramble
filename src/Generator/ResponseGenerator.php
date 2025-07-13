<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Generator;

use Yangweijie\ThinkScramble\Contracts\ConfigInterface;
use Yangweijie\ThinkScramble\Exception\GenerationException;

/**
 * OpenAPI 响应生成器
 * 
 * 生成符合 OpenAPI 3.0 规范的响应定义
 */
class ResponseGenerator
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
     * 标准响应模板
     */
    protected array $standardResponses = [
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
        '201' => [
            'description' => 'Created successfully',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'code' => ['type' => 'integer', 'example' => 201],
                            'message' => ['type' => 'string', 'example' => 'Created'],
                            'data' => ['type' => 'object'],
                        ],
                    ],
                ],
            ],
        ],
        '400' => [
            'description' => 'Bad Request',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'code' => ['type' => 'integer', 'example' => 400],
                            'message' => ['type' => 'string', 'example' => 'Bad Request'],
                            'errors' => [
                                'type' => 'object',
                                'additionalProperties' => [
                                    'type' => 'array',
                                    'items' => ['type' => 'string'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        '401' => [
            'description' => 'Unauthorized',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'code' => ['type' => 'integer', 'example' => 401],
                            'message' => ['type' => 'string', 'example' => 'Unauthorized'],
                        ],
                    ],
                ],
            ],
        ],
        '403' => [
            'description' => 'Forbidden',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'code' => ['type' => 'integer', 'example' => 403],
                            'message' => ['type' => 'string', 'example' => 'Forbidden'],
                        ],
                    ],
                ],
            ],
        ],
        '404' => [
            'description' => 'Not Found',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'code' => ['type' => 'integer', 'example' => 404],
                            'message' => ['type' => 'string', 'example' => 'Not Found'],
                        ],
                    ],
                ],
            ],
        ],
        '422' => [
            'description' => 'Validation Error',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'code' => ['type' => 'integer', 'example' => 422],
                            'message' => ['type' => 'string', 'example' => 'Validation failed'],
                            'errors' => [
                                'type' => 'object',
                                'additionalProperties' => [
                                    'type' => 'array',
                                    'items' => ['type' => 'string'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        '429' => [
            'description' => 'Too Many Requests',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'code' => ['type' => 'integer', 'example' => 429],
                            'message' => ['type' => 'string', 'example' => 'Too Many Requests'],
                            'retry_after' => ['type' => 'integer', 'example' => 60],
                        ],
                    ],
                ],
            ],
        ],
        '500' => [
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
        ],
    ];

    /**
     * 构造函数
     *
     * @param ConfigInterface $config 配置接口
     */
    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
        $this->schemaGenerator = new SchemaGenerator($config);
    }

    /**
     * 生成响应
     *
     * @param array $routeInfo 路由信息
     * @param array $controllerInfo 控制器信息
     * @param array $middlewareInfo 中间件信息
     * @return array
     * @throws GenerationException
     */
    public function generateResponses(array $routeInfo, array $controllerInfo, array $middlewareInfo = []): array
    {
        try {
            $responses = [];
            $method = strtoupper($routeInfo['method'] ?? 'GET');

            // 添加成功响应
            $successResponse = $this->generateSuccessResponse($method, $routeInfo, $controllerInfo);
            $responses['200'] = $successResponse;

            // 根据 HTTP 方法添加特定响应
            if ($method === 'POST') {
                $responses['201'] = $this->standardResponses['201'];
            }

            // 添加错误响应
            $errorResponses = $this->generateErrorResponses($middlewareInfo);
            $responses = array_merge($responses, $errorResponses);

            return $responses;

        } catch (\Exception $e) {
            throw new GenerationException("Failed to generate responses: " . $e->getMessage());
        }
    }

    /**
     * 生成成功响应
     *
     * @param string $method HTTP 方法
     * @param array $routeInfo 路由信息
     * @param array $controllerInfo 控制器信息
     * @return array
     */
    protected function generateSuccessResponse(string $method, array $routeInfo, array $controllerInfo): array
    {
        $action = $routeInfo['action'] ?? '';
        $response = $this->standardResponses['200'];

        // 根据方法和动作自定义响应
        if (isset($controllerInfo['methods'][$action])) {
            $methodInfo = $controllerInfo['methods'][$action];
            $returnType = $methodInfo['return_type'] ?? null;

            if ($returnType) {
                $dataSchema = $this->schemaGenerator->generateFromType($returnType);
                $response['content']['application/json']['schema']['properties']['data'] = $dataSchema;
            }
        }

        // 根据动作名称推断响应类型
        $response = $this->customizeResponseByAction($action, $response);

        return $response;
    }

    /**
     * 根据动作自定义响应
     *
     * @param string $action 动作名称
     * @param array $response 响应定义
     * @return array
     */
    protected function customizeResponseByAction(string $action, array $response): array
    {
        $actionPatterns = [
            'index' => 'list',
            'list' => 'list',
            'show' => 'single',
            'read' => 'single',
            'create' => 'single',
            'store' => 'single',
            'save' => 'single',
            'update' => 'single',
            'delete' => 'boolean',
            'destroy' => 'boolean',
        ];

        $pattern = $actionPatterns[$action] ?? 'single';

        switch ($pattern) {
            case 'list':
                $response['content']['application/json']['schema']['properties']['data'] = [
                    'type' => 'object',
                    'properties' => [
                        'items' => [
                            'type' => 'array',
                            'items' => ['type' => 'object'],
                        ],
                        'total' => ['type' => 'integer', 'example' => 100],
                        'page' => ['type' => 'integer', 'example' => 1],
                        'limit' => ['type' => 'integer', 'example' => 20],
                    ],
                ];
                break;

            case 'boolean':
                $response['content']['application/json']['schema']['properties']['data'] = [
                    'type' => 'boolean',
                    'example' => true,
                ];
                break;

            case 'single':
            default:
                // 保持默认的 object 类型
                break;
        }

        return $response;
    }

    /**
     * 生成错误响应
     *
     * @param array $middlewareInfo 中间件信息
     * @return array
     */
    protected function generateErrorResponses(array $middlewareInfo): array
    {
        $responses = [];

        // 基本错误响应
        $responses['400'] = $this->standardResponses['400'];
        $responses['500'] = $this->standardResponses['500'];

        // 根据中间件添加特定错误响应
        if (isset($middlewareInfo['features'])) {
            $features = $middlewareInfo['features'];

            // 认证相关错误
            if ($features['requires_auth'] ?? false) {
                $responses['401'] = $this->standardResponses['401'];
                $responses['403'] = $this->standardResponses['403'];
            }

            // 限流相关错误
            if ($features['has_rate_limit'] ?? false) {
                $responses['429'] = $this->standardResponses['429'];
            }
        }

        // 验证错误
        $responses['422'] = $this->standardResponses['422'];

        return $responses;
    }

    /**
     * 生成自定义响应
     *
     * @param int $statusCode 状态码
     * @param string $description 描述
     * @param array $schema 数据模式
     * @param array $headers 响应头
     * @return array
     */
    public function generateCustomResponse(int $statusCode, string $description, array $schema = [], array $headers = []): array
    {
        $response = [
            'description' => $description,
        ];

        if (!empty($schema)) {
            $response['content'] = [
                'application/json' => [
                    'schema' => $schema,
                ],
            ];
        }

        if (!empty($headers)) {
            $response['headers'] = $headers;
        }

        return $response;
    }

    /**
     * 获取标准响应
     *
     * @param string $statusCode 状态码
     * @return array|null
     */
    public function getStandardResponse(string $statusCode): ?array
    {
        return $this->standardResponses[$statusCode] ?? null;
    }

    /**
     * 添加响应头
     *
     * @param array $response 响应定义
     * @param array $headers 响应头
     * @return array
     */
    public function addResponseHeaders(array $response, array $headers): array
    {
        if (!empty($headers)) {
            $response['headers'] = array_merge($response['headers'] ?? [], $headers);
        }

        return $response;
    }

    /**
     * 设置响应示例
     *
     * @param array $response 响应定义
     * @param array $examples 示例数据
     * @return array
     */
    public function setResponseExamples(array $response, array $examples): array
    {
        foreach ($examples as $mediaType => $example) {
            if (isset($response['content'][$mediaType])) {
                $response['content'][$mediaType]['example'] = $example;
            }
        }

        return $response;
    }
}
