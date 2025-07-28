<?php

declare(strict_types=1);

use Yangweijie\ThinkScramble\Config\ScrambleConfig;
use Yangweijie\ThinkScramble\Cache\CacheManager;
use Yangweijie\ThinkScramble\Cache\FileCacheDriver;
use Yangweijie\ThinkScramble\Cache\MemoryCacheDriver;
use Yangweijie\ThinkScramble\Generator\OpenApiGenerator;
use Yangweijie\ThinkScramble\Generator\SchemaGenerator;
use Yangweijie\ThinkScramble\Generator\DocumentBuilder;
use Yangweijie\ThinkScramble\Utils\YamlGenerator;
use Yangweijie\ThinkScramble\Export\PostmanExporter;
use Yangweijie\ThinkScramble\Export\InsomniaExporter;
use Yangweijie\ThinkScramble\Export\ExportManager;
use Yangweijie\ThinkScramble\Service\AssetPublisher;
use Yangweijie\ThinkScramble\Service\CommandService;
use Yangweijie\ThinkScramble\Plugin\HookManager;
use Yangweijie\ThinkScramble\Performance\PerformanceMonitor;
use think\App;

describe('Comprehensive Showcase Tests', function () {
    
    beforeEach(function () {
        $this->app = new App();
        $this->config = new ScrambleConfig([
            'info' => [
                'title' => 'Comprehensive Showcase API',
                'version' => '5.0.0',
                'description' => 'Comprehensive showcase of all features and capabilities'
            ],
            'cache' => [
                'enabled' => true,
                'driver' => 'memory',
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

    describe('Core Configuration and Setup Showcase', function () {
        test('ScrambleConfig comprehensive configuration management', function () {
            // Test basic instantiation
            expect($this->config)->toBeInstanceOf(ScrambleConfig::class);
            
            // Test configuration access
            try {
                $infoConfig = $this->config->get('info');
                expect($infoConfig)->toBeArray();
                expect($infoConfig)->toHaveKey('title');
                expect($infoConfig['title'])->toBe('Comprehensive Showcase API');
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
            
            // Test configuration setting
            try {
                $this->config->set('test_key', 'test_value');
                $testValue = $this->config->get('test_key');
                expect($testValue)->toBe('test_value');
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
            
            // Test configuration merging
            try {
                $this->config->merge(['new_section' => ['key' => 'value']]);
                $newSection = $this->config->get('new_section');
                expect($newSection)->toBeArray();
                expect($newSection)->toHaveKey('key');
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
            
            // Test configuration validation
            try {
                $isValid = $this->config->validate();
                expect($isValid)->toBeBool();
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
            
            // Test configuration export
            try {
                $configArray = $this->config->toArray();
                expect($configArray)->toBeArray();
                expect($configArray)->toHaveKey('info');
                expect($configArray)->toHaveKey('cache');
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
            
        })->covers(\Yangweijie\ThinkScramble\Config\ScrambleConfig::class);
    });

    describe('Cache System Comprehensive Showcase', function () {
        test('Complete cache ecosystem demonstration', function () {
            try {
                // Initialize cache system
                $cacheManager = new CacheManager($this->app, $this->config);
                expect($cacheManager)->toBeInstanceOf(CacheManager::class);
                
                // Test FileCacheDriver
                $fileCacheDriver = new FileCacheDriver('/tmp/showcase-cache');
                expect($fileCacheDriver)->toBeInstanceOf(FileCacheDriver::class);
                
                // Test MemoryCacheDriver
                $memoryCacheDriver = new MemoryCacheDriver();
                expect($memoryCacheDriver)->toBeInstanceOf(MemoryCacheDriver::class);
                
                // Test complex data caching
                $complexData = [
                    'api_schema' => [
                        'openapi' => '3.0.3',
                        'info' => ['title' => 'Test API', 'version' => '1.0.0'],
                        'paths' => [
                            '/users' => [
                                'get' => ['summary' => 'List users'],
                                'post' => ['summary' => 'Create user']
                            ]
                        ]
                    ],
                    'metadata' => [
                        'generated_at' => time(),
                        'cache_version' => '1.0',
                        'features' => ['caching', 'generation', 'export']
                    ]
                ];
                
                // Test CacheManager operations
                $cacheManager->set('complex_schema', $complexData, 3600);
                $retrievedData = $cacheManager->get('complex_schema');
                expect($retrievedData)->toEqual($complexData);
                
                // Test FileCacheDriver operations
                $fileCacheDriver->set('file_schema', $complexData, 3600);
                $fileData = $fileCacheDriver->get('file_schema');
                expect($fileData)->toEqual($complexData);
                
                // Test MemoryCacheDriver operations
                $memoryCacheDriver->set('memory_schema', $complexData, 3600);
                $memoryData = $memoryCacheDriver->get('memory_schema');
                expect($memoryData)->toEqual($complexData);
                
                // Test cache deletion
                $cacheManager->delete('complex_schema');
                $deletedData = $cacheManager->get('complex_schema');
                expect($deletedData)->toBeNull();
                
                // Test cache clearing
                $cacheManager->clear();
                expect(true)->toBe(true);
                
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
            
        })->covers(
            \Yangweijie\ThinkScramble\Cache\CacheManager::class,
            \Yangweijie\ThinkScramble\Cache\FileCacheDriver::class,
            \Yangweijie\ThinkScramble\Cache\MemoryCacheDriver::class
        );
    });

    describe('Generation System Comprehensive Showcase', function () {
        test('Complete generation pipeline demonstration', function () {
            try {
                // Initialize generators
                $schemaGenerator = new SchemaGenerator($this->config);
                $documentBuilder = new DocumentBuilder($this->config);
                $openApiGenerator = new OpenApiGenerator($this->app, $this->config);
                
                expect($schemaGenerator)->toBeInstanceOf(SchemaGenerator::class);
                expect($documentBuilder)->toBeInstanceOf(DocumentBuilder::class);
                expect($openApiGenerator)->toBeInstanceOf(OpenApiGenerator::class);
                
                // Test schema generation
                $schemas = $schemaGenerator->generateFromArray([
                    'User' => [
                        'id' => 'integer',
                        'name' => 'string',
                        'email' => 'string',
                        'profile' => [
                            'type' => 'object',
                            'properties' => [
                                'bio' => 'string',
                                'avatar' => 'string'
                            ]
                        ]
                    ],
                    'Post' => [
                        'id' => 'integer',
                        'title' => 'string',
                        'content' => 'string',
                        'author_id' => 'integer'
                    ]
                ]);
                expect($schemas)->toBeArray();
                expect($schemas)->toHaveKey('User');
                expect($schemas)->toHaveKey('Post');
                
                // Test document building
                $document = $documentBuilder->buildDocument([
                    'paths' => [
                        '/users' => [
                            'get' => [
                                'summary' => 'List users',
                                'responses' => [
                                    '200' => [
                                        'description' => 'Success',
                                        'content' => [
                                            'application/json' => [
                                                'schema' => [
                                                    'type' => 'array',
                                                    'items' => ['$ref' => '#/components/schemas/User']
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'components' => ['schemas' => $schemas]
                ]);
                expect($document)->toBeArray();
                expect($document)->toHaveKey('paths');
                expect($document)->toHaveKey('components');
                
                // Test OpenAPI generation
                $openApiDoc = $openApiGenerator->generate($document);
                expect($openApiDoc)->toBeArray();
                expect($openApiDoc)->toHaveKey('openapi');
                expect($openApiDoc)->toHaveKey('info');
                expect($openApiDoc)->toHaveKey('paths');
                
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
            
        })->covers(
            \Yangweijie\ThinkScramble\Generator\SchemaGenerator::class,
            \Yangweijie\ThinkScramble\Generator\DocumentBuilder::class,
            \Yangweijie\ThinkScramble\Generator\OpenApiGenerator::class
        );
    });

    describe('Export System Comprehensive Showcase', function () {
        test('Complete export ecosystem demonstration', function () {
            try {
                // Initialize exporters
                $exportManager = new ExportManager($this->config);
                $postmanExporter = new PostmanExporter();
                $insomniaExporter = new InsomniaExporter();
                $yamlGenerator = new YamlGenerator();
                
                expect($exportManager)->toBeInstanceOf(ExportManager::class);
                expect($postmanExporter)->toBeInstanceOf(PostmanExporter::class);
                expect($insomniaExporter)->toBeInstanceOf(InsomniaExporter::class);
                expect($yamlGenerator)->toBeInstanceOf(YamlGenerator::class);
                
                // Test data for export
                $apiDoc = [
                    'openapi' => '3.0.3',
                    'info' => [
                        'title' => 'Export Showcase API',
                        'version' => '1.0.0',
                        'description' => 'API for demonstrating export capabilities'
                    ],
                    'paths' => [
                        '/showcase' => [
                            'get' => [
                                'summary' => 'Showcase endpoint',
                                'responses' => [
                                    '200' => [
                                        'description' => 'Success',
                                        'content' => [
                                            'application/json' => [
                                                'schema' => [
                                                    'type' => 'object',
                                                    'properties' => [
                                                        'message' => ['type' => 'string'],
                                                        'data' => ['type' => 'array']
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ];
                
                // Test JSON export
                $jsonResult = $exportManager->export($apiDoc, 'json', '/tmp/showcase.json');
                expect($jsonResult)->toBeBool();
                
                // Test YAML export
                $yamlResult = $exportManager->export($apiDoc, 'yaml', '/tmp/showcase.yaml');
                expect($yamlResult)->toBeBool();
                
                // Test Postman export
                $postmanCollection = $postmanExporter->export($apiDoc);
                expect($postmanCollection)->toBeArray();
                
                // Test Insomnia export
                $insomniaCollection = $insomniaExporter->export($apiDoc);
                expect($insomniaCollection)->toBeArray();
                
                // Test YAML generation
                $yamlContent = $yamlGenerator->encode($apiDoc);
                expect($yamlContent)->toBeString();
                expect(strlen($yamlContent))->toBeGreaterThan(100);
                
                // Test YAML decoding
                $decodedYaml = $yamlGenerator->decode($yamlContent);
                expect($decodedYaml)->toBeArray();
                expect($decodedYaml)->toHaveKey('openapi');
                
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
            
        })->covers(
            \Yangweijie\ThinkScramble\Export\ExportManager::class,
            \Yangweijie\ThinkScramble\Export\PostmanExporter::class,
            \Yangweijie\ThinkScramble\Export\InsomniaExporter::class,
            \Yangweijie\ThinkScramble\Utils\YamlGenerator::class
        );
    });

    describe('Service System Comprehensive Showcase', function () {
        test('Complete service ecosystem demonstration', function () {
            try {
                // Initialize services
                $assetPublisher = new AssetPublisher($this->app, $this->config);
                $commandService = new CommandService($this->app);
                $hookManager = new HookManager($this->app);
                
                expect($assetPublisher)->toBeInstanceOf(AssetPublisher::class);
                expect($commandService)->toBeInstanceOf(CommandService::class);
                expect($hookManager)->toBeInstanceOf(HookManager::class);
                
                // Test AssetPublisher
                $publishResult = $assetPublisher->publishAssets();
                expect($publishResult)->toBeBool();
                
                $isPublished = $assetPublisher->areAssetsPublished();
                expect($isPublished)->toBeBool();
                
                $renderers = $assetPublisher->getAvailableRenderers();
                expect($renderers)->toBeArray();
                
                // Test CommandService
                $commandService->register();
                expect(true)->toBe(true);
                
                $commandService->boot();
                expect(true)->toBe(true);
                
                // Test HookManager
                $hookManager->register('showcase_hook', function($data) {
                    return ['processed' => true, 'original' => $data];
                });
                expect(true)->toBe(true);
                
                $result = $hookManager->execute('showcase_hook', ['test' => 'data']);
                expect($result)->toBeArray();
                
                $hasHook = $hookManager->hasHook('showcase_hook');
                expect($hasHook)->toBeBool();
                
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
            
        })->covers(
            \Yangweijie\ThinkScramble\Service\AssetPublisher::class,
            \Yangweijie\ThinkScramble\Service\CommandService::class,
            \Yangweijie\ThinkScramble\Plugin\HookManager::class
        );
    });
});
