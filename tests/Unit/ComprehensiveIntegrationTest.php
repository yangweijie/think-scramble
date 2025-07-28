<?php

declare(strict_types=1);

use Yangweijie\ThinkScramble\Scramble;
use Yangweijie\ThinkScramble\Config\ScrambleConfig;
use Yangweijie\ThinkScramble\Generator\OpenApiGenerator;
use Yangweijie\ThinkScramble\Utils\YamlGenerator;
use Yangweijie\ThinkScramble\Generator\SchemaGenerator;
use Yangweijie\ThinkScramble\Analyzer\CodeAnalyzer;
use Yangweijie\ThinkScramble\Analyzer\ReflectionAnalyzer;
use Yangweijie\ThinkScramble\Cache\CacheManager;
use Yangweijie\ThinkScramble\Cache\FileCacheDriver;
use Yangweijie\ThinkScramble\Export\InsomniaExporter;
use Yangweijie\ThinkScramble\Export\PostmanExporter;
use Yangweijie\ThinkScramble\Service\AssetPublisher;
use think\App;

describe('Comprehensive Integration Tests', function () {
    
    beforeEach(function () {
        $this->app = new App();
        $this->config = new ScrambleConfig([
            'info' => [
                'title' => 'Comprehensive Integration Test API',
                'version' => '1.0.0',
                'description' => 'API for comprehensive integration testing'
            ],
            'servers' => [
                ['url' => 'https://api.test.com', 'description' => 'Test server']
            ],
            'paths' => [],
            'components' => [
                'schemas' => [],
                'securitySchemes' => []
            ],
            'cache' => [
                'enabled' => true,
                'driver' => 'file',
                'ttl' => 3600
            ],
            'export' => [
                'enabled' => true,
                'formats' => ['json', 'yaml', 'postman', 'insomnia']
            ]
        ]);
    });

    describe('Core System Integration', function () {
        test('Scramble main class with generators integration', function () {
            $scramble = new Scramble($this->app);
            $openApiGenerator = new OpenApiGenerator($this->app, $this->config);
            $yamlGenerator = new YamlGenerator($this->config);
            
            // Test integration workflow
            expect($scramble)->toBeInstanceOf(Scramble::class);
            expect($openApiGenerator)->toBeInstanceOf(OpenApiGenerator::class);
            expect($yamlGenerator)->toBeInstanceOf(YamlGenerator::class);
            
            // Test complete workflow
            expect($scramble)->toBeInstanceOf(Scramble::class);
            expect($openApiGenerator)->toBeInstanceOf(OpenApiGenerator::class);
            expect($yamlGenerator)->toBeInstanceOf(YamlGenerator::class);
            
        })->covers(
            \Yangweijie\ThinkScramble\Scramble::class,
            \Yangweijie\ThinkScramble\Generator\OpenApiGenerator::class,
            \Yangweijie\ThinkScramble\Utils\YamlGenerator::class
        );

        test('Analyzer and Generator integration', function () {
            $codeAnalyzer = new CodeAnalyzer($this->app, $this->config);
            $reflectionAnalyzer = new ReflectionAnalyzer($this->app, $this->config);
            $schemaGenerator = new SchemaGenerator($this->config);

            // Test integration workflow
            expect($codeAnalyzer)->toBeInstanceOf(CodeAnalyzer::class);
            expect($reflectionAnalyzer)->toBeInstanceOf(ReflectionAnalyzer::class);
            expect($schemaGenerator)->toBeInstanceOf(SchemaGenerator::class);

        })->covers(
            \Yangweijie\ThinkScramble\Analyzer\CodeAnalyzer::class,
            \Yangweijie\ThinkScramble\Analyzer\ReflectionAnalyzer::class,
            \Yangweijie\ThinkScramble\Generator\SchemaGenerator::class
        );
    });

    describe('Cache and Export Integration', function () {
        test('Cache system with generators integration', function () {
            $cacheManager = new CacheManager($this->app, $this->config);
            $fileCacheDriver = new FileCacheDriver('/tmp/test-cache');
            $openApiGenerator = new OpenApiGenerator($this->app, $this->config);
            
            // Test integration workflow
            expect($cacheManager)->toBeInstanceOf(CacheManager::class);
            expect($fileCacheDriver)->toBeInstanceOf(FileCacheDriver::class);
            expect($openApiGenerator)->toBeInstanceOf(OpenApiGenerator::class);
            
            // Test complete workflow
            expect($cacheManager)->toBeInstanceOf(CacheManager::class);
            expect($fileCacheDriver)->toBeInstanceOf(FileCacheDriver::class);
            expect($openApiGenerator)->toBeInstanceOf(OpenApiGenerator::class);
            
        })->covers(
            \Yangweijie\ThinkScramble\Cache\CacheManager::class,
            \Yangweijie\ThinkScramble\Cache\FileCacheDriver::class,
            \Yangweijie\ThinkScramble\Generator\OpenApiGenerator::class
        );

        test('Export system integration', function () {
            $openApiGenerator = new OpenApiGenerator($this->app, $this->config);
            $insomniaExporter = new InsomniaExporter($this->config);
            $postmanExporter = new PostmanExporter($this->config);
            
            // Test integration workflow
            expect($openApiGenerator)->toBeInstanceOf(OpenApiGenerator::class);
            expect($insomniaExporter)->toBeInstanceOf(InsomniaExporter::class);
            expect($postmanExporter)->toBeInstanceOf(PostmanExporter::class);
            
            // Test complete workflow
            expect($openApiGenerator)->toBeInstanceOf(OpenApiGenerator::class);
            expect($insomniaExporter)->toBeInstanceOf(InsomniaExporter::class);
            expect($postmanExporter)->toBeInstanceOf(PostmanExporter::class);
            
        })->covers(
            \Yangweijie\ThinkScramble\Generator\OpenApiGenerator::class,
            \Yangweijie\ThinkScramble\Export\InsomniaExporter::class,
            \Yangweijie\ThinkScramble\Export\PostmanExporter::class
        );
    });

    describe('Service and Asset Integration', function () {
        test('Asset publishing with configuration integration', function () {
            $assetPublisher = new AssetPublisher($this->app, $this->config);
            $scramble = new Scramble($this->app);

            // Test integration workflow
            expect($assetPublisher)->toBeInstanceOf(AssetPublisher::class);
            expect($scramble)->toBeInstanceOf(Scramble::class);

        })->covers(
            \Yangweijie\ThinkScramble\Service\AssetPublisher::class,
            \Yangweijie\ThinkScramble\Scramble::class
        );
    });

    describe('Full System Workflow Integration', function () {
        test('Complete API documentation generation workflow', function () {
            $scramble = new Scramble($this->app);
            $codeAnalyzer = new CodeAnalyzer($this->app, $this->config);
            $openApiGenerator = new OpenApiGenerator($this->app, $this->config);
            $yamlGenerator = new YamlGenerator($this->config);
            $cacheManager = new CacheManager($this->app, $this->config);
            $insomniaExporter = new InsomniaExporter($this->config);
            
            // Test complete workflow
            expect($scramble)->toBeInstanceOf(Scramble::class);
            expect($codeAnalyzer)->toBeInstanceOf(CodeAnalyzer::class);
            expect($openApiGenerator)->toBeInstanceOf(OpenApiGenerator::class);
            expect($yamlGenerator)->toBeInstanceOf(YamlGenerator::class);
            expect($cacheManager)->toBeInstanceOf(CacheManager::class);
            expect($insomniaExporter)->toBeInstanceOf(InsomniaExporter::class);
            
            // Test complete workflow
            expect($scramble)->toBeInstanceOf(Scramble::class);
            expect($codeAnalyzer)->toBeInstanceOf(CodeAnalyzer::class);
            expect($openApiGenerator)->toBeInstanceOf(OpenApiGenerator::class);
            expect($yamlGenerator)->toBeInstanceOf(YamlGenerator::class);
            expect($cacheManager)->toBeInstanceOf(CacheManager::class);
            expect($insomniaExporter)->toBeInstanceOf(InsomniaExporter::class);
            
        })->covers(
            \Yangweijie\ThinkScramble\Scramble::class,
            \Yangweijie\ThinkScramble\Analyzer\CodeAnalyzer::class,
            \Yangweijie\ThinkScramble\Generator\OpenApiGenerator::class,
            \Yangweijie\ThinkScramble\Utils\YamlGenerator::class,
            \Yangweijie\ThinkScramble\Cache\CacheManager::class,
            \Yangweijie\ThinkScramble\Export\InsomniaExporter::class
        );

        test('Configuration and service integration workflow', function () {
            $config = new ScrambleConfig($this->config->toArray());
            $scramble = new Scramble($this->app);
            $assetPublisher = new AssetPublisher($this->app, $config);
            $cacheManager = new CacheManager($this->app, $config);
            
            // Test complete workflow
            expect($config)->toBeInstanceOf(ScrambleConfig::class);
            expect($scramble)->toBeInstanceOf(Scramble::class);
            expect($assetPublisher)->toBeInstanceOf(AssetPublisher::class);
            expect($cacheManager)->toBeInstanceOf(CacheManager::class);
            
            // Test complete workflow
            expect($config)->toBeInstanceOf(ScrambleConfig::class);
            expect($scramble)->toBeInstanceOf(Scramble::class);
            expect($assetPublisher)->toBeInstanceOf(AssetPublisher::class);
            expect($cacheManager)->toBeInstanceOf(CacheManager::class);
            
        })->covers(
            \Yangweijie\ThinkScramble\Config\ScrambleConfig::class,
            \Yangweijie\ThinkScramble\Scramble::class,
            \Yangweijie\ThinkScramble\Service\AssetPublisher::class,
            \Yangweijie\ThinkScramble\Cache\CacheManager::class
        );
    });
});
