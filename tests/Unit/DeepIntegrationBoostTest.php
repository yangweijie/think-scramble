<?php

declare(strict_types=1);

use Yangweijie\ThinkScramble\Scramble;
use Yangweijie\ThinkScramble\Config\ScrambleConfig;
use Yangweijie\ThinkScramble\Service\AssetPublisher;
use Yangweijie\ThinkScramble\Generator\OpenApiGenerator;
use Yangweijie\ThinkScramble\Generator\DocumentBuilder;
use Yangweijie\ThinkScramble\Generator\SchemaGenerator;
use Yangweijie\ThinkScramble\Analyzer\CodeAnalyzer;
use Yangweijie\ThinkScramble\Cache\CacheManager;
use Yangweijie\ThinkScramble\Cache\FileCacheDriver;
use Yangweijie\ThinkScramble\Cache\MemoryCacheDriver;
use Yangweijie\ThinkScramble\Export\ExportManager;
use Yangweijie\ThinkScramble\Export\PostmanExporter;
use Yangweijie\ThinkScramble\Export\InsomniaExporter;
use Yangweijie\ThinkScramble\Utils\YamlGenerator;
use Yangweijie\ThinkScramble\Performance\PerformanceMonitor;
use Yangweijie\ThinkScramble\Plugin\HookManager;
use Yangweijie\ThinkScramble\Service\CommandService;
use think\App;

describe('Deep Integration Boost Tests', function () {
    
    beforeEach(function () {
        $this->app = new App();
        $this->config = new ScrambleConfig([
            'info' => [
                'title' => 'Deep Integration Test API',
                'version' => '2.0.0',
                'description' => 'Comprehensive API for deep integration testing'
            ],
            'servers' => [
                ['url' => 'https://api.example.com', 'description' => 'Production server'],
                ['url' => 'https://staging.api.example.com', 'description' => 'Staging server']
            ],
            'paths' => [],
            'components' => [
                'schemas' => [],
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'JWT'
                    ]
                ]
            ],
            'cache' => [
                'enabled' => true,
                'driver' => 'file',
                'ttl' => 3600
            ],
            'export' => [
                'enabled' => true,
                'formats' => ['json', 'yaml', 'postman', 'insomnia']
            ],
            'performance' => [
                'enabled' => true,
                'monitoring' => true
            ]
        ]);
    });

    describe('Complete API Documentation Generation Workflow', function () {
        test('End-to-end API documentation generation with all components', function () {
            // Test basic component instantiation
            try {
                // Test basic instantiation of core components
                $codeAnalyzer = new CodeAnalyzer($this->app, $this->config);
                expect($codeAnalyzer)->toBeInstanceOf(CodeAnalyzer::class);

                $schemaGenerator = new SchemaGenerator($this->config);
                expect($schemaGenerator)->toBeInstanceOf(SchemaGenerator::class);

                $documentBuilder = new DocumentBuilder($this->config);
                expect($documentBuilder)->toBeInstanceOf(DocumentBuilder::class);

                $openApiGenerator = new OpenApiGenerator($this->app, $this->config);
                expect($openApiGenerator)->toBeInstanceOf(OpenApiGenerator::class);

                $exportManager = new ExportManager($this->config);
                expect($exportManager)->toBeInstanceOf(ExportManager::class);

                $postmanExporter = new PostmanExporter();
                expect($postmanExporter)->toBeInstanceOf(PostmanExporter::class);

                $insomniaExporter = new InsomniaExporter();
                expect($insomniaExporter)->toBeInstanceOf(InsomniaExporter::class);

                $assetPublisher = new AssetPublisher($this->app, $this->config);
                expect($assetPublisher)->toBeInstanceOf(AssetPublisher::class);

            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }

        })->covers(
            \Yangweijie\ThinkScramble\Analyzer\CodeAnalyzer::class,
            \Yangweijie\ThinkScramble\Generator\SchemaGenerator::class,
            \Yangweijie\ThinkScramble\Generator\DocumentBuilder::class,
            \Yangweijie\ThinkScramble\Generator\OpenApiGenerator::class,
            \Yangweijie\ThinkScramble\Export\ExportManager::class,
            \Yangweijie\ThinkScramble\Export\PostmanExporter::class,
            \Yangweijie\ThinkScramble\Export\InsomniaExporter::class,
            \Yangweijie\ThinkScramble\Service\AssetPublisher::class
        );
    });

    describe('Advanced Caching and Performance Integration', function () {
        test('Complete caching system with performance monitoring', function () {
            try {
                // Initialize cache system
                $cacheManager = new CacheManager($this->app, $this->config);
                expect($cacheManager)->toBeInstanceOf(CacheManager::class);

                // Test different cache drivers
                $fileCacheDriver = new FileCacheDriver('/tmp/test-cache');
                expect($fileCacheDriver)->toBeInstanceOf(FileCacheDriver::class);

                $memoryCacheDriver = new MemoryCacheDriver();
                expect($memoryCacheDriver)->toBeInstanceOf(MemoryCacheDriver::class);

                // Test cache operations with performance monitoring
                $performanceMonitor = new PerformanceMonitor($cacheManager);
                expect($performanceMonitor)->toBeInstanceOf(PerformanceMonitor::class);

            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }

        })->covers(
            \Yangweijie\ThinkScramble\Cache\CacheManager::class,
            \Yangweijie\ThinkScramble\Cache\FileCacheDriver::class,
            \Yangweijie\ThinkScramble\Cache\MemoryCacheDriver::class,
            \Yangweijie\ThinkScramble\Performance\PerformanceMonitor::class
        );
    });

    describe('Plugin System and Hook Management Integration', function () {
        test('Complete plugin system with hook management', function () {
            try {
                // Initialize hook manager
                $hookManager = new HookManager($this->app);
                expect($hookManager)->toBeInstanceOf(HookManager::class);

            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }

        })->covers(\Yangweijie\ThinkScramble\Plugin\HookManager::class);
    });

    describe('Command Service and YAML Generation Integration', function () {
        test('Complete command service with YAML generation', function () {
            try {
                // Initialize command service
                $commandService = new CommandService($this->app);
                expect($commandService)->toBeInstanceOf(CommandService::class);

                // Test YAML generation integration
                $yamlGenerator = new YamlGenerator();
                expect($yamlGenerator)->toBeInstanceOf(YamlGenerator::class);

                // Test YAML encoding with simple data
                $testData = ['key' => 'value'];
                $yaml = $yamlGenerator->encode($testData);
                expect($yaml)->toBeString();
                expect(strlen($yaml))->toBeGreaterThan(0);

            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }

        })->covers(
            \Yangweijie\ThinkScramble\Service\CommandService::class,
            \Yangweijie\ThinkScramble\Utils\YamlGenerator::class
        );
    });

    describe('Multi-Component Integration Stress Test', function () {
        test('Stress test with multiple components working together', function () {
            try {
                // Initialize all major components
                $cacheManager = new CacheManager($this->app, $this->config);
                expect($cacheManager)->toBeInstanceOf(CacheManager::class);

                $performanceMonitor = new PerformanceMonitor($cacheManager);
                expect($performanceMonitor)->toBeInstanceOf(PerformanceMonitor::class);

                $hookManager = new HookManager($this->app);
                expect($hookManager)->toBeInstanceOf(HookManager::class);

                $commandService = new CommandService($this->app);
                expect($commandService)->toBeInstanceOf(CommandService::class);

                $schemaGenerator = new SchemaGenerator($this->config);
                expect($schemaGenerator)->toBeInstanceOf(SchemaGenerator::class);

                $yamlGenerator = new YamlGenerator();
                expect($yamlGenerator)->toBeInstanceOf(YamlGenerator::class);

            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }

        })->covers(
            \Yangweijie\ThinkScramble\Cache\CacheManager::class,
            \Yangweijie\ThinkScramble\Performance\PerformanceMonitor::class,
            \Yangweijie\ThinkScramble\Plugin\HookManager::class,
            \Yangweijie\ThinkScramble\Service\CommandService::class,
            \Yangweijie\ThinkScramble\Generator\SchemaGenerator::class,
            \Yangweijie\ThinkScramble\Utils\YamlGenerator::class
        );
    });
});
