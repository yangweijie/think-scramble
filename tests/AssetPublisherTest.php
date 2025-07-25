<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use think\App;
use Yangweijie\ThinkScramble\Service\AssetPublisher;

/**
 * AssetPublisher 测试类
 */
class AssetPublisherTest extends TestCase
{
    private AssetPublisher $assetPublisher;
    private string $testPublicDir;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 创建模拟的 App 实例
        $app = $this->createMock(App::class);
        $this->testPublicDir = sys_get_temp_dir() . '/test_public_' . uniqid();
        
        $app->method('getRootPath')->willReturn($this->testPublicDir . '/');
        
        $this->assetPublisher = new AssetPublisher($app);
        
        // 创建测试目录
        mkdir($this->testPublicDir, 0755, true);
    }

    protected function tearDown(): void
    {
        // 清理测试目录
        if (is_dir($this->testPublicDir)) {
            $this->removeDirectory($this->testPublicDir);
        }
        
        parent::tearDown();
    }

    public function testGetAvailableRenderers(): void
    {
        $renderers = $this->assetPublisher->getAvailableRenderers();
        
        $this->assertIsArray($renderers);
        $this->assertArrayHasKey('stoplight-elements', $renderers);
        $this->assertArrayHasKey('swagger-ui', $renderers);
        
        // 检查 Stoplight Elements 配置
        $stoplightElements = $renderers['stoplight-elements'];
        $this->assertEquals('Stoplight Elements', $stoplightElements['name']);
        $this->assertContains('elements-styles.min.css', $stoplightElements['files']);
        $this->assertContains('elements-web-components.min.js', $stoplightElements['files']);
        
        // 检查 Swagger UI 配置
        $swaggerUI = $renderers['swagger-ui'];
        $this->assertEquals('Swagger UI', $swaggerUI['name']);
        $this->assertContains('swagger-ui.css', $swaggerUI['files']);
        $this->assertContains('swagger-ui-bundle.js', $swaggerUI['files']);
    }

    public function testIsRendererAvailable(): void
    {
        // 在没有发布资源的情况下，渲染器应该不可用
        $this->assertFalse($this->assetPublisher->isRendererAvailable('stoplight-elements'));
        $this->assertFalse($this->assetPublisher->isRendererAvailable('swagger-ui'));
        
        // 不存在的渲染器应该返回 false
        $this->assertFalse($this->assetPublisher->isRendererAvailable('non-existent'));
    }

    public function testGetStoplightElementsHtml(): void
    {
        $apiUrl = '/api/openapi.json';
        $options = [
            'title' => 'Test API',
            'layout' => 'stacked',
            'router' => 'memory'
        ];
        
        $html = $this->assetPublisher->getStoplightElementsHtml($apiUrl, $options);
        
        // 检查 HTML 结构
        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<title>Test API</title>', $html);
        $this->assertStringContainsString('elements-styles.min.css', $html);
        $this->assertStringContainsString('elements-web-components.min.js', $html);
        $this->assertStringContainsString('<elements-api', $html);
        $this->assertStringContainsString('apiDescriptionUrl="/api/openapi.json"', $html);
        $this->assertStringContainsString('layout="stacked"', $html);
        $this->assertStringContainsString('router="memory"', $html);
    }

    public function testGetStoplightElementsHtmlWithDefaults(): void
    {
        $apiUrl = '/api/openapi.json';
        $html = $this->assetPublisher->getStoplightElementsHtml($apiUrl);
        
        // 检查默认值
        $this->assertStringContainsString('<title>API Documentation</title>', $html);
        $this->assertStringContainsString('layout="sidebar"', $html);
        $this->assertStringContainsString('router="hash"', $html);
        $this->assertStringContainsString('tryItCredentialsPolicy="same-origin"', $html);
    }

    public function testGetSwaggerUIHtml(): void
    {
        $apiUrl = '/api/openapi.json';
        $options = ['title' => 'Test Swagger UI'];
        
        $html = $this->assetPublisher->getSwaggerUIHtml($apiUrl, $options);
        
        // 检查 HTML 结构
        $this->assertStringContainsString('<!DOCTYPE html>', $html);
        $this->assertStringContainsString('<title>Test Swagger UI</title>', $html);
        $this->assertStringContainsString('swagger-ui.css', $html);
        $this->assertStringContainsString('swagger-ui-bundle.js', $html);
        $this->assertStringContainsString('SwaggerUIBundle', $html);
        $this->assertStringContainsString("url: '/api/openapi.json'", $html);
    }

    public function testGetSwaggerUIHtmlWithDefaults(): void
    {
        $apiUrl = '/api/openapi.json';
        $html = $this->assetPublisher->getSwaggerUIHtml($apiUrl);
        
        // 检查默认标题
        $this->assertStringContainsString('<title>API Documentation</title>', $html);
    }

    /**
     * 递归删除目录
     */
    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        
        rmdir($dir);
    }
}

/**
 * 集成测试类
 * 
 * 需要实际的资源文件才能运行
 */
class AssetPublisherIntegrationTest extends TestCase
{
    private AssetPublisher $assetPublisher;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 使用真实的 App 实例进行集成测试
        // 注意：这需要在实际的 ThinkPHP 环境中运行
        if (class_exists('\think\App')) {
            $app = new \think\App();
            $this->assetPublisher = new AssetPublisher($app);
        } else {
            $this->markTestSkipped('ThinkPHP App class not available');
        }
    }

    public function testPublishAndCheckAssets(): void
    {
        // 发布资源文件
        $result = $this->assetPublisher->publishAssets();
        $this->assertTrue($result);
        
        // 检查资源是否已发布
        $this->assertTrue($this->assetPublisher->areAssetsPublished());
        
        // 检查各个渲染器是否可用
        $this->assertTrue($this->assetPublisher->isRendererAvailable('stoplight-elements'));
        $this->assertTrue($this->assetPublisher->isRendererAvailable('swagger-ui'));
    }

    public function testForcePublishAssets(): void
    {
        // 强制重新发布
        $result = $this->assetPublisher->forcePublishAssets();
        $this->assertTrue($result);
        
        // 验证资源仍然可用
        $this->assertTrue($this->assetPublisher->areAssetsPublished());
    }
}
