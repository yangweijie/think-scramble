<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Tests\Support;

/**
 * 测试数据生成器
 */
class TestDataGenerator
{
    /**
     * 生成测试控制器代码
     */
    public static function generateController(string $name, array $methods = []): string
    {
        $defaultMethods = [
            'index' => [
                'summary' => 'Get list',
                'description' => 'Get paginated list of items',
                'method' => 'GET',
            ],
            'show' => [
                'summary' => 'Get item',
                'description' => 'Get single item by ID',
                'method' => 'GET',
                'params' => ['id' => 'int'],
            ],
            'store' => [
                'summary' => 'Create item',
                'description' => 'Create new item',
                'method' => 'POST',
                'requestBody' => ['name' => 'string', 'email' => 'string'],
            ],
            'update' => [
                'summary' => 'Update item',
                'description' => 'Update existing item',
                'method' => 'PUT',
                'params' => ['id' => 'int'],
                'requestBody' => ['name' => 'string', 'email' => 'string'],
            ],
            'destroy' => [
                'summary' => 'Delete item',
                'description' => 'Delete item by ID',
                'method' => 'DELETE',
                'params' => ['id' => 'int'],
            ],
        ];

        $methods = array_merge($defaultMethods, $methods);
        
        $code = "<?php\n\n";
        $code .= "declare(strict_types=1);\n\n";
        $code .= "namespace App\\Controller;\n\n";
        $code .= "use think\\Request;\n";
        $code .= "use think\\Response;\n\n";
        $code .= "/**\n";
        $code .= " * {$name} Controller\n";
        $code .= " * @tag {$name}\n";
        $code .= " */\n";
        $code .= "class {$name}Controller\n";
        $code .= "{\n";

        foreach ($methods as $methodName => $config) {
            $code .= self::generateControllerMethod($methodName, $config);
        }

        $code .= "}\n";

        return $code;
    }

    /**
     * 生成控制器方法
     */
    private static function generateControllerMethod(string $name, array $config): string
    {
        $code = "    /**\n";
        $code .= "     * {$config['summary']}\n";
        $code .= "     * @summary {$config['summary']}\n";
        $code .= "     * @description {$config['description']}\n";

        // 添加参数注解
        if (isset($config['params'])) {
            foreach ($config['params'] as $param => $type) {
                $code .= "     * @param {$type} \${$param}\n";
            }
        }

        // 添加请求体注解
        if (isset($config['requestBody'])) {
            $code .= "     * @requestBody {\n";
            foreach ($config['requestBody'] as $field => $type) {
                $code .= "     *   \"{$field}\": \"{$type}\",\n";
            }
            $code = rtrim($code, ",\n") . "\n";
            $code .= "     * }\n";
        }

        // 添加响应注解
        $code .= "     * @response 200 {\n";
        $code .= "     *   \"code\": 200,\n";
        $code .= "     *   \"message\": \"success\",\n";
        $code .= "     *   \"data\": {}\n";
        $code .= "     * }\n";
        $code .= "     */\n";

        // 方法签名
        $params = ['Request $request'];
        if (isset($config['params'])) {
            foreach ($config['params'] as $param => $type) {
                $phpType = $type === 'int' ? 'int' : 'string';
                $params[] = "{$phpType} \${$param}";
            }
        }

        $code .= "    public function {$name}(" . implode(', ', $params) . "): Response\n";
        $code .= "    {\n";
        $code .= "        return json([\n";
        $code .= "            'code' => 200,\n";
        $code .= "            'message' => 'success',\n";
        $code .= "            'data' => []\n";
        $code .= "        ]);\n";
        $code .= "    }\n\n";

        return $code;
    }

    /**
     * 生成测试模型代码
     */
    public static function generateModel(string $name, array $fields = []): string
    {
        $defaultFields = [
            'id' => 'int',
            'name' => 'string',
            'email' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];

        $fields = array_merge($defaultFields, $fields);

        $code = "<?php\n\n";
        $code .= "declare(strict_types=1);\n\n";
        $code .= "namespace App\\Model;\n\n";
        $code .= "use think\\Model;\n\n";
        $code .= "/**\n";
        $code .= " * {$name} Model\n";
        $code .= " */\n";
        $code .= "class {$name} extends Model\n";
        $code .= "{\n";
        $code .= "    protected \$table = '" . strtolower($name) . "s';\n\n";

        // 字段定义
        $code .= "    protected \$schema = [\n";
        foreach ($fields as $field => $type) {
            $code .= "        '{$field}' => '{$type}',\n";
        }
        $code .= "    ];\n\n";

        // 验证规则
        $code .= "    protected \$validate = [\n";
        foreach ($fields as $field => $type) {
            if ($field === 'id') continue;
            
            $rules = [];
            if ($field === 'email') {
                $rules[] = 'email';
            }
            if (in_array($field, ['name', 'email'])) {
                $rules[] = 'require';
            }
            
            if (!empty($rules)) {
                $code .= "        '{$field}' => '" . implode('|', $rules) . "',\n";
            }
        }
        $code .= "    ];\n\n";

        // 关联关系示例
        if ($name !== 'User') {
            $code .= "    /**\n";
            $code .= "     * 关联用户\n";
            $code .= "     */\n";
            $code .= "    public function user()\n";
            $code .= "    {\n";
            $code .= "        return \$this->belongsTo(User::class);\n";
            $code .= "    }\n\n";
        }

        $code .= "}\n";

        return $code;
    }

    /**
     * 生成测试中间件代码
     */
    public static function generateMiddleware(string $name, array $config = []): string
    {
        $code = "<?php\n\n";
        $code .= "declare(strict_types=1);\n\n";
        $code .= "namespace App\\Middleware;\n\n";
        $code .= "use think\\Request;\n";
        $code .= "use think\\Response;\n\n";
        $code .= "/**\n";
        $code .= " * {$name} Middleware\n";
        $code .= " */\n";
        $code .= "class {$name}\n";
        $code .= "{\n";
        $code .= "    public function handle(Request \$request, \\Closure \$next): Response\n";
        $code .= "    {\n";

        if (isset($config['auth']) && $config['auth']) {
            $code .= "        // 检查认证\n";
            $code .= "        \$token = \$request->header('Authorization');\n";
            $code .= "        if (empty(\$token)) {\n";
            $code .= "            return json(['code' => 401, 'message' => 'Unauthorized'], 401);\n";
            $code .= "        }\n\n";
        }

        if (isset($config['rate_limit']) && $config['rate_limit']) {
            $code .= "        // 速率限制\n";
            $code .= "        \$key = 'rate_limit:' . \$request->ip();\n";
            $code .= "        // 实现速率限制逻辑\n\n";
        }

        $code .= "        return \$next(\$request);\n";
        $code .= "    }\n";
        $code .= "}\n";

        return $code;
    }

    /**
     * 生成测试路由配置
     */
    public static function generateRoutes(array $controllers = []): string
    {
        $code = "<?php\n\n";
        $code .= "use think\\facade\\Route;\n\n";

        foreach ($controllers as $controller => $config) {
            $prefix = strtolower($controller);
            $code .= "// {$controller} routes\n";
            $code .= "Route::group('{$prefix}', function () {\n";
            
            $methods = $config['methods'] ?? ['index', 'show', 'store', 'update', 'destroy'];
            
            foreach ($methods as $method) {
                switch ($method) {
                    case 'index':
                        $code .= "    Route::get('/', '{$controller}Controller/index');\n";
                        break;
                    case 'show':
                        $code .= "    Route::get('/<id>', '{$controller}Controller/show');\n";
                        break;
                    case 'store':
                        $code .= "    Route::post('/', '{$controller}Controller/store');\n";
                        break;
                    case 'update':
                        $code .= "    Route::put('/<id>', '{$controller}Controller/update');\n";
                        break;
                    case 'destroy':
                        $code .= "    Route::delete('/<id>', '{$controller}Controller/destroy');\n";
                        break;
                }
            }
            
            $code .= "})";
            
            if (isset($config['middleware'])) {
                $middleware = is_array($config['middleware']) 
                    ? implode(',', $config['middleware'])
                    : $config['middleware'];
                $code .= "->middleware('{$middleware}')";
            }
            
            $code .= ";\n\n";
        }

        return $code;
    }

    /**
     * 生成 OpenAPI 测试数据
     */
    public static function generateOpenApiDocument(): array
    {
        return [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'Test API',
                'version' => '1.0.0',
                'description' => 'Test API for ThinkScramble',
            ],
            'servers' => [
                [
                    'url' => 'http://localhost:8000',
                    'description' => 'Development server',
                ],
            ],
            'paths' => [
                '/users' => [
                    'get' => [
                        'summary' => 'Get users',
                        'operationId' => 'getUsers',
                        'responses' => [
                            '200' => [
                                'description' => 'Success',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'code' => ['type' => 'integer'],
                                                'data' => ['type' => 'array'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'post' => [
                        'summary' => 'Create user',
                        'operationId' => 'createUser',
                        'requestBody' => [
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'name' => ['type' => 'string'],
                                            'email' => ['type' => 'string'],
                                        ],
                                        'required' => ['name', 'email'],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '201' => [
                                'description' => 'Created',
                            ],
                        ],
                    ],
                ],
            ],
            'components' => [
                'schemas' => [
                    'User' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'name' => ['type' => 'string'],
                            'email' => ['type' => 'string'],
                        ],
                    ],
                ],
                'securitySchemes' => [
                    'BearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                    ],
                ],
            ],
        ];
    }

    /**
     * 创建临时测试文件
     */
    public static function createTempFile(string $content, string $extension = 'php'): string
    {
        $tempDir = sys_get_temp_dir() . '/think-scramble-tests';
        
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        
        $filename = $tempDir . '/' . uniqid('test_') . '.' . $extension;
        file_put_contents($filename, $content);
        
        return $filename;
    }

    /**
     * 清理临时测试文件
     */
    public static function cleanupTempFiles(): void
    {
        $tempDir = sys_get_temp_dir() . '/think-scramble-tests';
        
        if (is_dir($tempDir)) {
            $files = glob($tempDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($tempDir);
        }
    }
}
