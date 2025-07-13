<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use think\App;
use think\Config;
use Yangweijie\ThinkScramble\Config\ScrambleConfig;

/**
 * 测试基类
 * 
 * 为所有测试提供通用的设置和工具方法
 */
abstract class TestCase extends BaseTestCase
{
    /**
     * ThinkPHP 应用实例
     */
    protected App $app;

    /**
     * Scramble 配置实例
     */
    protected ScrambleConfig $config;

    /**
     * 测试数据目录
     */
    protected string $testDataPath;

    /**
     * 设置测试环境
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->app = new App();
        $this->config = new ScrambleConfig();
        $this->testDataPath = __DIR__ . '/data';
        
        // 确保测试数据目录存在
        if (!is_dir($this->testDataPath)) {
            mkdir($this->testDataPath, 0755, true);
        }
        
        $this->setupTestEnvironment();
    }

    /**
     * 清理测试环境
     */
    protected function tearDown(): void
    {
        $this->cleanupTestFiles();
        parent::tearDown();
    }

    /**
     * 设置测试环境
     */
    protected function setupTestEnvironment(): void
    {
        // 设置测试配置
        $this->config->set('app.debug', true);
        $this->config->set('cache.enabled', true);
        $this->config->set('cache.ttl', 60);
        
        // 设置测试路径
        $this->config->set('paths.controllers', $this->testDataPath . '/controllers');
        $this->config->set('paths.models', $this->testDataPath . '/models');
    }

    /**
     * 清理测试文件
     */
    protected function cleanupTestFiles(): void
    {
        $testFiles = glob($this->testDataPath . '/test_*');
        foreach ($testFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    /**
     * 创建测试文件
     *
     * @param string $filename 文件名
     * @param string $content 文件内容
     * @return string 文件路径
     */
    protected function createTestFile(string $filename, string $content): string
    {
        $filepath = $this->testDataPath . '/' . $filename;
        file_put_contents($filepath, $content);
        return $filepath;
    }

    /**
     * 创建测试控制器文件
     *
     * @param string $className 类名
     * @param array $methods 方法列表
     * @return string 文件路径
     */
    protected function createTestController(string $className, array $methods = []): string
    {
        $methodsCode = '';
        foreach ($methods as $method) {
            $params = $method['params'] ?? '';
            $body = $method['body'] ?? 'return [];';
            $methodsCode .= "
    public function {$method['name']}({$params})
    {
        {$body}
    }
";
        }

        $content = "<?php

namespace app\\controller;

use think\\Controller;

class {$className} extends Controller
{
{$methodsCode}
}";

        return $this->createTestFile("controllers/{$className}.php", $content);
    }

    /**
     * 创建测试模型文件
     *
     * @param string $className 类名
     * @param array $properties 属性列表
     * @return string 文件路径
     */
    protected function createTestModel(string $className, array $properties = []): string
    {
        $propertiesCode = '';
        foreach ($properties as $property) {
            $propertiesCode .= "
    protected \${$property['name']};
";
        }

        $content = "<?php

namespace app\\model;

use think\\Model;

class {$className} extends Model
{
{$propertiesCode}
}";

        return $this->createTestFile("models/{$className}.php", $content);
    }

    /**
     * 断言数组包含指定键
     *
     * @param array $expectedKeys 期望的键
     * @param array $array 数组
     * @param string $message 错误消息
     */
    protected function assertArrayHasKeys(array $expectedKeys, array $array, string $message = ''): void
    {
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $array, $message ?: "Array should have key: {$key}");
        }
    }

    /**
     * 断言 OpenAPI 文档结构
     *
     * @param array $document OpenAPI 文档
     */
    protected function assertValidOpenApiDocument(array $document): void
    {
        // 检查必需的顶级字段
        $this->assertArrayHasKeys(['openapi', 'info', 'paths'], $document);
        
        // 检查 OpenAPI 版本
        $this->assertMatchesRegularExpression('/^3\.\d+\.\d+$/', $document['openapi']);
        
        // 检查 info 字段
        $this->assertArrayHasKeys(['title', 'version'], $document['info']);
        
        // 检查 paths 字段是数组
        $this->assertIsArray($document['paths']);
    }

    /**
     * 断言路径信息结构
     *
     * @param array $pathInfo 路径信息
     */
    protected function assertValidPathInfo(array $pathInfo): void
    {
        $this->assertIsArray($pathInfo);
        
        // 检查至少有一个 HTTP 方法
        $httpMethods = ['get', 'post', 'put', 'patch', 'delete', 'options', 'head'];
        $hasMethod = false;
        
        foreach ($httpMethods as $method) {
            if (isset($pathInfo[$method])) {
                $hasMethod = true;
                $this->assertValidOperationInfo($pathInfo[$method]);
            }
        }
        
        $this->assertTrue($hasMethod, 'Path should have at least one HTTP method');
    }

    /**
     * 断言操作信息结构
     *
     * @param array $operationInfo 操作信息
     */
    protected function assertValidOperationInfo(array $operationInfo): void
    {
        $this->assertIsArray($operationInfo);
        
        // 检查基本字段
        if (isset($operationInfo['responses'])) {
            $this->assertIsArray($operationInfo['responses']);
        }
        
        if (isset($operationInfo['parameters'])) {
            $this->assertIsArray($operationInfo['parameters']);
        }
        
        if (isset($operationInfo['requestBody'])) {
            $this->assertIsArray($operationInfo['requestBody']);
        }
    }

    /**
     * 获取测试数据
     *
     * @param string $key 数据键
     * @return mixed
     */
    protected function getTestData(string $key)
    {
        $testData = [
            'sample_controller' => [
                'name' => 'UserController',
                'methods' => [
                    [
                        'name' => 'index',
                        'params' => '',
                        'body' => 'return json([]);'
                    ],
                    [
                        'name' => 'show',
                        'params' => 'int $id',
                        'body' => 'return json(["id" => $id]);'
                    ],
                    [
                        'name' => 'store',
                        'params' => '',
                        'body' => 'return json(["message" => "created"]);'
                    ]
                ]
            ],
            'sample_model' => [
                'name' => 'User',
                'properties' => [
                    ['name' => 'id'],
                    ['name' => 'name'],
                    ['name' => 'email']
                ]
            ]
        ];

        return $testData[$key] ?? null;
    }

    /**
     * 模拟 HTTP 请求
     *
     * @param string $method HTTP 方法
     * @param string $uri URI
     * @param array $data 请求数据
     * @return array 模拟响应
     */
    protected function mockHttpRequest(string $method, string $uri, array $data = []): array
    {
        return [
            'method' => strtoupper($method),
            'uri' => $uri,
            'data' => $data,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ]
        ];
    }

    /**
     * 断言性能指标
     *
     * @param float $actualTime 实际时间（毫秒）
     * @param float $maxTime 最大允许时间（毫秒）
     * @param string $operation 操作名称
     */
    protected function assertPerformance(float $actualTime, float $maxTime, string $operation = 'operation'): void
    {
        $this->assertLessThanOrEqual(
            $maxTime,
            $actualTime,
            "Performance test failed: {$operation} took {$actualTime}ms, expected <= {$maxTime}ms"
        );
    }

    /**
     * 断言内存使用
     *
     * @param int $maxMemory 最大内存使用（字节）
     * @param string $operation 操作名称
     */
    protected function assertMemoryUsage(int $maxMemory, string $operation = 'operation'): void
    {
        $currentMemory = memory_get_usage(true);
        $this->assertLessThanOrEqual(
            $maxMemory,
            $currentMemory,
            "Memory usage test failed: {$operation} used " . round($currentMemory / 1024 / 1024, 2) . "MB, expected <= " . round($maxMemory / 1024 / 1024, 2) . "MB"
        );
    }
}
