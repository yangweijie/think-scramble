<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Export;

/**
 * Postman Collection 导出器
 */
class PostmanExporter
{
    /**
     * 导出为 Postman Collection
     *
     * @param array $openApiDoc OpenAPI 文档
     * @return array Postman Collection
     */
    public function export(array $openApiDoc): array
    {
        $collection = [
            'info' => $this->buildInfo($openApiDoc),
            'item' => $this->buildItems($openApiDoc),
            'auth' => $this->buildAuth($openApiDoc),
            'variable' => $this->buildVariables($openApiDoc),
        ];

        return $collection;
    }

    /**
     * 构建集合信息
     */
    protected function buildInfo(array $openApiDoc): array
    {
        $info = $openApiDoc['info'] ?? [];
        
        return [
            'name' => $info['title'] ?? 'API Collection',
            'description' => $info['description'] ?? '',
            'version' => $info['version'] ?? '1.0.0',
            'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
        ];
    }

    /**
     * 构建请求项目
     */
    protected function buildItems(array $openApiDoc): array
    {
        $items = [];
        $paths = $openApiDoc['paths'] ?? [];

        foreach ($paths as $path => $pathItem) {
            foreach ($pathItem as $method => $operation) {
                if (!in_array(strtoupper($method), ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS'])) {
                    continue;
                }

                $items[] = $this->buildRequestItem($path, $method, $operation, $openApiDoc);
            }
        }

        return $items;
    }

    /**
     * 构建单个请求项目
     */
    protected function buildRequestItem(string $path, string $method, array $operation, array $openApiDoc): array
    {
        $item = [
            'name' => $operation['summary'] ?? $operation['operationId'] ?? "{$method} {$path}",
            'request' => [
                'method' => strtoupper($method),
                'header' => $this->buildHeaders($operation),
                'url' => $this->buildUrl($path, $operation, $openApiDoc),
            ],
            'response' => [],
        ];

        // 添加请求体
        if (isset($operation['requestBody'])) {
            $item['request']['body'] = $this->buildRequestBody($operation['requestBody']);
        }

        // 添加描述
        if (isset($operation['description'])) {
            $item['request']['description'] = $operation['description'];
        }

        // 添加认证
        if (isset($operation['security'])) {
            $item['request']['auth'] = $this->buildRequestAuth($operation['security'], $openApiDoc);
        }

        return $item;
    }

    /**
     * 构建请求头
     */
    protected function buildHeaders(array $operation): array
    {
        $headers = [];

        // 从参数中提取 header 参数
        $parameters = $operation['parameters'] ?? [];
        foreach ($parameters as $parameter) {
            if ($parameter['in'] === 'header') {
                $headers[] = [
                    'key' => $parameter['name'],
                    'value' => $parameter['example'] ?? '{{' . $parameter['name'] . '}}',
                    'description' => $parameter['description'] ?? '',
                ];
            }
        }

        return $headers;
    }

    /**
     * 构建 URL
     */
    protected function buildUrl(string $path, array $operation, array $openApiDoc): array
    {
        $servers = $openApiDoc['servers'] ?? [['url' => 'http://localhost']];
        $baseUrl = $servers[0]['url'];

        // 处理路径参数
        $pathParams = [];
        $queryParams = [];
        
        $parameters = $operation['parameters'] ?? [];
        foreach ($parameters as $parameter) {
            if ($parameter['in'] === 'path') {
                $pathParams[] = [
                    'key' => $parameter['name'],
                    'value' => $parameter['example'] ?? '{{' . $parameter['name'] . '}}',
                    'description' => $parameter['description'] ?? '',
                ];
            } elseif ($parameter['in'] === 'query') {
                $queryParams[] = [
                    'key' => $parameter['name'],
                    'value' => $parameter['example'] ?? '{{' . $parameter['name'] . '}}',
                    'description' => $parameter['description'] ?? '',
                ];
            }
        }

        return [
            'raw' => $baseUrl . $path,
            'host' => [parse_url($baseUrl, PHP_URL_HOST)],
            'path' => array_filter(explode('/', parse_url($baseUrl, PHP_URL_PATH) . $path)),
            'query' => $queryParams,
            'variable' => $pathParams,
        ];
    }

    /**
     * 构建请求体
     */
    protected function buildRequestBody(array $requestBody): array
    {
        $content = $requestBody['content'] ?? [];
        
        // 优先处理 JSON
        if (isset($content['application/json'])) {
            return [
                'mode' => 'raw',
                'raw' => json_encode($this->generateExampleFromSchema($content['application/json']['schema'] ?? []), JSON_PRETTY_PRINT),
                'options' => [
                    'raw' => [
                        'language' => 'json',
                    ],
                ],
            ];
        }

        // 处理表单数据
        if (isset($content['application/x-www-form-urlencoded'])) {
            $formData = [];
            $schema = $content['application/x-www-form-urlencoded']['schema'] ?? [];
            $properties = $schema['properties'] ?? [];
            
            foreach ($properties as $name => $property) {
                $formData[] = [
                    'key' => $name,
                    'value' => $property['example'] ?? '{{' . $name . '}}',
                    'description' => $property['description'] ?? '',
                ];
            }
            
            return [
                'mode' => 'urlencoded',
                'urlencoded' => $formData,
            ];
        }

        // 处理文件上传
        if (isset($content['multipart/form-data'])) {
            $formData = [];
            $schema = $content['multipart/form-data']['schema'] ?? [];
            $properties = $schema['properties'] ?? [];
            
            foreach ($properties as $name => $property) {
                $item = [
                    'key' => $name,
                    'description' => $property['description'] ?? '',
                ];
                
                if ($property['type'] === 'string' && $property['format'] === 'binary') {
                    $item['type'] = 'file';
                    $item['src'] = [];
                } else {
                    $item['value'] = $property['example'] ?? '{{' . $name . '}}';
                }
                
                $formData[] = $item;
            }
            
            return [
                'mode' => 'formdata',
                'formdata' => $formData,
            ];
        }

        return [
            'mode' => 'raw',
            'raw' => '',
        ];
    }

    /**
     * 构建认证
     */
    protected function buildAuth(array $openApiDoc): ?array
    {
        $securitySchemes = $openApiDoc['components']['securitySchemes'] ?? [];
        
        if (empty($securitySchemes)) {
            return null;
        }

        // 取第一个安全方案作为默认
        $firstScheme = array_values($securitySchemes)[0];
        
        switch ($firstScheme['type']) {
            case 'http':
                if ($firstScheme['scheme'] === 'bearer') {
                    return [
                        'type' => 'bearer',
                        'bearer' => [
                            [
                                'key' => 'token',
                                'value' => '{{bearerToken}}',
                                'type' => 'string',
                            ],
                        ],
                    ];
                } elseif ($firstScheme['scheme'] === 'basic') {
                    return [
                        'type' => 'basic',
                        'basic' => [
                            [
                                'key' => 'username',
                                'value' => '{{username}}',
                                'type' => 'string',
                            ],
                            [
                                'key' => 'password',
                                'value' => '{{password}}',
                                'type' => 'string',
                            ],
                        ],
                    ];
                }
                break;
                
            case 'apiKey':
                return [
                    'type' => 'apikey',
                    'apikey' => [
                        [
                            'key' => 'key',
                            'value' => $firstScheme['name'],
                            'type' => 'string',
                        ],
                        [
                            'key' => 'value',
                            'value' => '{{apiKey}}',
                            'type' => 'string',
                        ],
                        [
                            'key' => 'in',
                            'value' => $firstScheme['in'],
                            'type' => 'string',
                        ],
                    ],
                ];
        }

        return null;
    }

    /**
     * 构建请求认证
     */
    protected function buildRequestAuth(array $security, array $openApiDoc): ?array
    {
        if (empty($security)) {
            return null;
        }

        $securitySchemes = $openApiDoc['components']['securitySchemes'] ?? [];
        $firstSecurity = $security[0];
        $schemeName = array_keys($firstSecurity)[0];
        
        if (!isset($securitySchemes[$schemeName])) {
            return null;
        }

        $scheme = $securitySchemes[$schemeName];
        
        switch ($scheme['type']) {
            case 'http':
                if ($scheme['scheme'] === 'bearer') {
                    return [
                        'type' => 'bearer',
                        'bearer' => [
                            [
                                'key' => 'token',
                                'value' => '{{bearerToken}}',
                                'type' => 'string',
                            ],
                        ],
                    ];
                }
                break;
        }

        return null;
    }

    /**
     * 构建变量
     */
    protected function buildVariables(array $openApiDoc): array
    {
        $variables = [];
        
        // 添加服务器变量
        $servers = $openApiDoc['servers'] ?? [];
        if (!empty($servers)) {
            $baseUrl = $servers[0]['url'];
            $variables[] = [
                'key' => 'baseUrl',
                'value' => $baseUrl,
                'type' => 'string',
            ];
        }

        // 添加认证变量
        $securitySchemes = $openApiDoc['components']['securitySchemes'] ?? [];
        foreach ($securitySchemes as $name => $scheme) {
            switch ($scheme['type']) {
                case 'http':
                    if ($scheme['scheme'] === 'bearer') {
                        $variables[] = [
                            'key' => 'bearerToken',
                            'value' => 'your-bearer-token',
                            'type' => 'string',
                        ];
                    } elseif ($scheme['scheme'] === 'basic') {
                        $variables[] = [
                            'key' => 'username',
                            'value' => 'your-username',
                            'type' => 'string',
                        ];
                        $variables[] = [
                            'key' => 'password',
                            'value' => 'your-password',
                            'type' => 'string',
                        ];
                    }
                    break;
                    
                case 'apiKey':
                    $variables[] = [
                        'key' => 'apiKey',
                        'value' => 'your-api-key',
                        'type' => 'string',
                    ];
                    break;
            }
        }

        return $variables;
    }

    /**
     * 从 Schema 生成示例
     */
    protected function generateExampleFromSchema(array $schema): mixed
    {
        if (isset($schema['example'])) {
            return $schema['example'];
        }

        $type = $schema['type'] ?? 'object';
        
        switch ($type) {
            case 'object':
                $example = [];
                $properties = $schema['properties'] ?? [];
                foreach ($properties as $name => $property) {
                    $example[$name] = $this->generateExampleFromSchema($property);
                }
                return $example;
                
            case 'array':
                $items = $schema['items'] ?? ['type' => 'string'];
                return [$this->generateExampleFromSchema($items)];
                
            case 'string':
                return 'string';
                
            case 'integer':
                return 0;
                
            case 'number':
                return 0.0;
                
            case 'boolean':
                return true;
                
            default:
                return null;
        }
    }

    /**
     * 保存为 JSON 文件
     */
    public function saveToFile(array $collection, string $filename): void
    {
        $json = json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($filename, $json);
    }
}
