<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Export;

/**
 * Insomnia Workspace 导出器
 */
class InsomniaExporter
{
    /**
     * 导出为 Insomnia Workspace
     *
     * @param array $openApiDoc OpenAPI 文档
     * @return array Insomnia Workspace
     */
    public function export(array $openApiDoc): array
    {
        $workspace = [
            '_type' => 'export',
            '__export_format' => 4,
            '__export_date' => date('c'),
            '__export_source' => 'think-scramble',
            'resources' => [],
        ];

        // 添加工作空间
        $workspace['resources'][] = $this->buildWorkspace($openApiDoc);

        // 添加环境
        $workspace['resources'][] = $this->buildEnvironment($openApiDoc);

        // 添加请求
        $requests = $this->buildRequests($openApiDoc);
        $workspace['resources'] = array_merge($workspace['resources'], $requests);

        return $workspace;
    }

    /**
     * 构建工作空间
     */
    protected function buildWorkspace(array $openApiDoc): array
    {
        $info = $openApiDoc['info'] ?? [];
        
        return [
            '_id' => 'wrk_' . $this->generateId(),
            '_type' => 'workspace',
            'name' => $info['title'] ?? 'API Workspace',
            'description' => $info['description'] ?? '',
            'parentId' => null,
            'scope' => 'collection',
        ];
    }

    /**
     * 构建环境
     */
    protected function buildEnvironment(array $openApiDoc): array
    {
        $servers = $openApiDoc['servers'] ?? [['url' => 'http://localhost']];
        $baseUrl = $servers[0]['url'];
        
        $data = [
            'base_url' => $baseUrl,
        ];

        // 添加认证变量
        $securitySchemes = $openApiDoc['components']['securitySchemes'] ?? [];
        foreach ($securitySchemes as $name => $scheme) {
            switch ($scheme['type']) {
                case 'http':
                    if ($scheme['scheme'] === 'bearer') {
                        $data['bearer_token'] = 'your-bearer-token';
                    } elseif ($scheme['scheme'] === 'basic') {
                        $data['username'] = 'your-username';
                        $data['password'] = 'your-password';
                    }
                    break;
                    
                case 'apiKey':
                    $data['api_key'] = 'your-api-key';
                    break;
            }
        }

        return [
            '_id' => 'env_' . $this->generateId(),
            '_type' => 'environment',
            'name' => 'Base Environment',
            'data' => $data,
            'dataPropertyOrder' => array_keys($data),
            'color' => null,
            'isPrivate' => false,
            'metaSortKey' => 1000000000000,
            'parentId' => 'wrk_' . $this->generateId(),
        ];
    }

    /**
     * 构建请求
     */
    protected function buildRequests(array $openApiDoc): array
    {
        $requests = [];
        $paths = $openApiDoc['paths'] ?? [];

        foreach ($paths as $path => $pathItem) {
            foreach ($pathItem as $method => $operation) {
                if (!in_array(strtoupper($method), ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS'])) {
                    continue;
                }

                $requests[] = $this->buildRequest($path, $method, $operation, $openApiDoc);
            }
        }

        return $requests;
    }

    /**
     * 构建单个请求
     */
    protected function buildRequest(string $path, string $method, array $operation, array $openApiDoc): array
    {
        $request = [
            '_id' => 'req_' . $this->generateId(),
            '_type' => 'request',
            'name' => $operation['summary'] ?? $operation['operationId'] ?? "{$method} {$path}",
            'description' => $operation['description'] ?? '',
            'url' => '{{ _.base_url }}' . $path,
            'method' => strtoupper($method),
            'headers' => $this->buildHeaders($operation),
            'parameters' => $this->buildParameters($operation),
            'authentication' => $this->buildAuthentication($operation, $openApiDoc),
            'metaSortKey' => -time(),
            'isPrivate' => false,
            'settingStoreCookies' => true,
            'settingSendCookies' => true,
            'settingDisableRenderRequestBody' => false,
            'settingEncodeUrl' => true,
            'settingRebuildPath' => true,
            'settingFollowRedirects' => 'global',
            'parentId' => 'wrk_' . $this->generateId(),
        ];

        // 添加请求体
        if (isset($operation['requestBody'])) {
            $body = $this->buildRequestBody($operation['requestBody']);
            $request['body'] = $body['body'];
            $request['headers'] = array_merge($request['headers'], $body['headers']);
        }

        return $request;
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
                    'name' => $parameter['name'],
                    'value' => $parameter['example'] ?? '{{ _.header_' . $parameter['name'] . ' }}',
                    'description' => $parameter['description'] ?? '',
                    'disabled' => false,
                ];
            }
        }

        return $headers;
    }

    /**
     * 构建参数
     */
    protected function buildParameters(array $operation): array
    {
        $parameters = [];

        // 从参数中提取 query 参数
        $operationParams = $operation['parameters'] ?? [];
        foreach ($operationParams as $parameter) {
            if ($parameter['in'] === 'query') {
                $parameters[] = [
                    'name' => $parameter['name'],
                    'value' => $parameter['example'] ?? '{{ _.query_' . $parameter['name'] . ' }}',
                    'description' => $parameter['description'] ?? '',
                    'disabled' => !($parameter['required'] ?? false),
                ];
            }
        }

        return $parameters;
    }

    /**
     * 构建认证
     */
    protected function buildAuthentication(array $operation, array $openApiDoc): array
    {
        $security = $operation['security'] ?? $openApiDoc['security'] ?? [];
        
        if (empty($security)) {
            return ['type' => 'none'];
        }

        $securitySchemes = $openApiDoc['components']['securitySchemes'] ?? [];
        $firstSecurity = $security[0];
        $schemeName = array_keys($firstSecurity)[0];
        
        if (!isset($securitySchemes[$schemeName])) {
            return ['type' => 'none'];
        }

        $scheme = $securitySchemes[$schemeName];
        
        switch ($scheme['type']) {
            case 'http':
                if ($scheme['scheme'] === 'bearer') {
                    return [
                        'type' => 'bearer',
                        'token' => '{{ _.bearer_token }}',
                        'prefix' => 'Bearer',
                    ];
                } elseif ($scheme['scheme'] === 'basic') {
                    return [
                        'type' => 'basic',
                        'username' => '{{ _.username }}',
                        'password' => '{{ _.password }}',
                    ];
                }
                break;
                
            case 'apiKey':
                if ($scheme['in'] === 'header') {
                    return [
                        'type' => 'apikey',
                        'key' => $scheme['name'],
                        'value' => '{{ _.api_key }}',
                        'addTo' => 'header',
                    ];
                } elseif ($scheme['in'] === 'query') {
                    return [
                        'type' => 'apikey',
                        'key' => $scheme['name'],
                        'value' => '{{ _.api_key }}',
                        'addTo' => 'queryParams',
                    ];
                }
                break;
        }

        return ['type' => 'none'];
    }

    /**
     * 构建请求体
     */
    protected function buildRequestBody(array $requestBody): array
    {
        $content = $requestBody['content'] ?? [];
        $headers = [];
        
        // 优先处理 JSON
        if (isset($content['application/json'])) {
            $headers[] = [
                'name' => 'Content-Type',
                'value' => 'application/json',
                'disabled' => false,
            ];
            
            return [
                'body' => [
                    'mimeType' => 'application/json',
                    'text' => json_encode($this->generateExampleFromSchema($content['application/json']['schema'] ?? []), JSON_PRETTY_PRINT),
                ],
                'headers' => $headers,
            ];
        }

        // 处理表单数据
        if (isset($content['application/x-www-form-urlencoded'])) {
            $headers[] = [
                'name' => 'Content-Type',
                'value' => 'application/x-www-form-urlencoded',
                'disabled' => false,
            ];
            
            $params = [];
            $schema = $content['application/x-www-form-urlencoded']['schema'] ?? [];
            $properties = $schema['properties'] ?? [];
            
            foreach ($properties as $name => $property) {
                $params[] = [
                    'name' => $name,
                    'value' => $property['example'] ?? '{{ _.form_' . $name . ' }}',
                    'description' => $property['description'] ?? '',
                    'disabled' => false,
                ];
            }
            
            return [
                'body' => [
                    'mimeType' => 'application/x-www-form-urlencoded',
                    'params' => $params,
                ],
                'headers' => $headers,
            ];
        }

        // 处理文件上传
        if (isset($content['multipart/form-data'])) {
            $params = [];
            $schema = $content['multipart/form-data']['schema'] ?? [];
            $properties = $schema['properties'] ?? [];
            
            foreach ($properties as $name => $property) {
                $param = [
                    'name' => $name,
                    'description' => $property['description'] ?? '',
                    'disabled' => false,
                ];
                
                if ($property['type'] === 'string' && $property['format'] === 'binary') {
                    $param['type'] = 'file';
                    $param['fileName'] = '';
                } else {
                    $param['value'] = $property['example'] ?? '{{ _.form_' . $name . ' }}';
                }
                
                $params[] = $param;
            }
            
            return [
                'body' => [
                    'mimeType' => 'multipart/form-data',
                    'params' => $params,
                ],
                'headers' => $headers,
            ];
        }

        return [
            'body' => [
                'mimeType' => 'text/plain',
                'text' => '',
            ],
            'headers' => $headers,
        ];
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
     * 生成 ID
     */
    protected function generateId(): string
    {
        return substr(md5(uniqid()), 0, 16);
    }

    /**
     * 保存为 JSON 文件
     */
    public function saveToFile(array $workspace, string $filename): void
    {
        $json = json_encode($workspace, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($filename, $json);
    }
}
