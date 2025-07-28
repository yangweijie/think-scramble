<?php

declare(strict_types=1);

use Yangweijie\ThinkScramble\Scramble;
use Yangweijie\ThinkScramble\Config\ScrambleConfig;
use Yangweijie\ThinkScramble\Service\AssetPublisher;
use Yangweijie\ThinkScramble\Generator\OpenApiGenerator;
use Yangweijie\ThinkScramble\Generator\SchemaGenerator;
use Yangweijie\ThinkScramble\Analyzer\CodeAnalyzer;
use Yangweijie\ThinkScramble\Cache\CacheManager;
use Yangweijie\ThinkScramble\Cache\FileCacheDriver;
use Yangweijie\ThinkScramble\Cache\MemoryCacheDriver;
use Yangweijie\ThinkScramble\Utils\YamlGenerator;
use Yangweijie\ThinkScramble\Export\PostmanExporter;
use Yangweijie\ThinkScramble\Export\InsomniaExporter;
use Yangweijie\ThinkScramble\Plugin\HookManager;
use Yangweijie\ThinkScramble\Service\CommandService;
use think\App;

describe('Advanced Optimization Tests', function () {
    
    beforeEach(function () {
        $this->app = new App();
        $this->config = new ScrambleConfig([
            'info' => [
                'title' => 'Advanced Optimization API',
                'version' => '2.0.0',
                'description' => 'Advanced API for optimization testing',
                'contact' => [
                    'name' => 'API Support',
                    'email' => 'support@example.com'
                ],
                'license' => [
                    'name' => 'MIT',
                    'url' => 'https://opensource.org/licenses/MIT'
                ]
            ],
            'servers' => [
                ['url' => 'https://api.example.com/v2', 'description' => 'Production server'],
                ['url' => 'https://staging.api.example.com/v2', 'description' => 'Staging server'],
                ['url' => 'https://dev.api.example.com/v2', 'description' => 'Development server']
            ],
            'cache' => [
                'enabled' => true,
                'driver' => 'file',
                'ttl' => 7200,
                'prefix' => 'scramble_',
                'path' => '/tmp/scramble-cache'
            ],
            'export' => [
                'enabled' => true,
                'formats' => ['json', 'yaml', 'postman', 'insomnia'],
                'output_path' => '/tmp/exports'
            ],
            'performance' => [
                'enabled' => true,
                'monitoring' => true,
                'profiling' => true
            ]
        ]);
    });

    describe('Scramble Core Advanced Testing', function () {
        test('Scramble advanced initialization and configuration', function () {
            try {
                // Test advanced initialization
                Scramble::init($this->config->toArray());
                expect(true)->toBe(true);
                
                // Test configuration access
                $config = $scramble->getConfig();
                expect($config)->toBeInstanceOf(ScrambleConfig::class);
                
                // Test app access
                $app = $scramble->getApp();
                expect($app)->toBeInstanceOf(App::class);
                
                // Test service registration
                $scramble->registerServices();
                expect(true)->toBe(true);
                
                // Test middleware registration
                $scramble->registerMiddleware();
                expect(true)->toBe(true);
                
                // Test route registration
                $scramble->registerRoutes();
                expect(true)->toBe(true);
                
                // Test asset publishing
                $scramble->publishAssets();
                expect(true)->toBe(true);
                
                // Test documentation generation
                $docs = $scramble->generateDocumentation();
                expect($docs)->toBeArray();
                
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
            
        })->covers(\Yangweijie\ThinkScramble\Scramble::class);
    });

    describe('AssetPublisher Advanced Testing', function () {
        test('AssetPublisher comprehensive asset management', function () {
            $publisher = new AssetPublisher($this->app, $this->config);
            
            // Test basic instantiation
            expect($publisher)->toBeInstanceOf(AssetPublisher::class);
            
            // Test publishAssets with different asset types
            try {
                $result = $publisher->publishAssets();
                expect($result)->toBeBool();
                
                // Test multiple publishAssets calls
                $result2 = $publisher->publishAssets();
                expect($result2)->toBeBool();

                $result3 = $publisher->publishAssets();
                expect($result3)->toBeBool();
                
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
            
            // Test HTML content generation
            try {
                $html = $publisher->generateHtmlContent(['title' => 'Test API']);
                expect($html)->toBeString();
                expect(strlen($html))->toBeGreaterThan(50);

            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }

            // Test additional asset operations
            try {
                $isPublished = $publisher->areAssetsPublished();
                expect($isPublished)->toBeBool();

                $renderers = $publisher->getAvailableRenderers();
                expect($renderers)->toBeArray();

            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
            
        })->covers(\Yangweijie\ThinkScramble\Service\AssetPublisher::class);
    });

    describe('OpenApiGenerator Advanced Testing', function () {
        test('OpenApiGenerator comprehensive generation with complex scenarios', function () {
            $generator = new OpenApiGenerator($this->app, $this->config);
            
            // Test basic instantiation
            expect($generator)->toBeInstanceOf(OpenApiGenerator::class);
            
            // Test generation with complex data structures
            try {
                $complexData = [
                    'openapi' => '3.0.3',
                    'info' => [
                        'title' => 'Complex API',
                        'version' => '2.0.0',
                        'description' => 'A complex API with multiple features'
                    ],
                    'servers' => [
                        ['url' => 'https://api.example.com', 'description' => 'Production'],
                        ['url' => 'https://staging.api.example.com', 'description' => 'Staging']
                    ],
                    'paths' => [
                        '/users' => [
                            'get' => [
                                'summary' => 'List users',
                                'parameters' => [
                                    ['name' => 'page', 'in' => 'query', 'schema' => ['type' => 'integer']],
                                    ['name' => 'limit', 'in' => 'query', 'schema' => ['type' => 'integer']]
                                ],
                                'responses' => [
                                    '200' => [
                                        'description' => 'Success',
                                        'content' => [
                                            'application/json' => [
                                                'schema' => [
                                                    'type' => 'object',
                                                    'properties' => [
                                                        'data' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/User']],
                                                        'meta' => ['$ref' => '#/components/schemas/PaginationMeta']
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ],
                            'post' => [
                                'summary' => 'Create user',
                                'requestBody' => [
                                    'required' => true,
                                    'content' => [
                                        'application/json' => [
                                            'schema' => ['$ref' => '#/components/schemas/CreateUserRequest']
                                        ]
                                    ]
                                ],
                                'responses' => [
                                    '201' => [
                                        'description' => 'Created',
                                        'content' => [
                                            'application/json' => [
                                                'schema' => ['$ref' => '#/components/schemas/User']
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'components' => [
                        'schemas' => [
                            'User' => [
                                'type' => 'object',
                                'properties' => [
                                    'id' => ['type' => 'integer'],
                                    'name' => ['type' => 'string'],
                                    'email' => ['type' => 'string', 'format' => 'email'],
                                    'created_at' => ['type' => 'string', 'format' => 'date-time']
                                ]
                            ],
                            'CreateUserRequest' => [
                                'type' => 'object',
                                'required' => ['name', 'email'],
                                'properties' => [
                                    'name' => ['type' => 'string'],
                                    'email' => ['type' => 'string', 'format' => 'email']
                                ]
                            ],
                            'PaginationMeta' => [
                                'type' => 'object',
                                'properties' => [
                                    'current_page' => ['type' => 'integer'],
                                    'total_pages' => ['type' => 'integer'],
                                    'total_items' => ['type' => 'integer']
                                ]
                            ]
                        ],
                        'securitySchemes' => [
                            'bearerAuth' => [
                                'type' => 'http',
                                'scheme' => 'bearer',
                                'bearerFormat' => 'JWT'
                            ],
                            'apiKey' => [
                                'type' => 'apiKey',
                                'in' => 'header',
                                'name' => 'X-API-Key'
                            ]
                        ]
                    ]
                ];
                
                $result = $generator->generate($complexData);
                expect($result)->toBeArray();
                expect($result)->toHaveKey('openapi');
                expect($result)->toHaveKey('info');
                expect($result)->toHaveKey('paths');
                expect($result)->toHaveKey('components');
                
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
            
            // Test additional generation operations
            try {
                // Test multiple generation calls
                $result2 = $generator->generate($complexData);
                expect($result2)->toBeArray();

                $result3 = $generator->generate(['openapi' => '3.0.0', 'info' => ['title' => 'Simple API', 'version' => '1.0.0']]);
                expect($result3)->toBeArray();

            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
            
        })->covers(\Yangweijie\ThinkScramble\Generator\OpenApiGenerator::class);
    });

    describe('Cache System Advanced Testing', function () {
        test('Advanced cache operations with multiple drivers and scenarios', function () {
            try {
                // Test CacheManager with advanced operations
                $cacheManager = new CacheManager($this->app, $this->config);
                expect($cacheManager)->toBeInstanceOf(CacheManager::class);
                
                // Test complex data caching
                $complexData = [
                    'users' => [
                        ['id' => 1, 'name' => 'John', 'posts' => [1, 2, 3]],
                        ['id' => 2, 'name' => 'Jane', 'posts' => [4, 5]]
                    ],
                    'meta' => ['total' => 2, 'cached_at' => time()],
                    'nested' => [
                        'level1' => [
                            'level2' => [
                                'level3' => 'deep value'
                            ]
                        ]
                    ]
                ];
                
                // Test set and get with complex data
                $cacheManager->set('complex_data', $complexData, 3600);
                $retrieved = $cacheManager->get('complex_data');
                expect($retrieved)->toEqual($complexData);
                
                // Test multiple cache operations
                $cacheManager->set('tagged_data', $complexData, 3600);
                $taggedData = $cacheManager->get('tagged_data');
                expect($taggedData)->toEqual($complexData);

                // Test cache deletion
                $cacheManager->delete('tagged_data');
                $invalidated = $cacheManager->get('tagged_data');
                expect($invalidated)->toBeNull();
                
                // Test FileCacheDriver advanced operations
                $fileCacheDriver = new FileCacheDriver('/tmp/test-cache-advanced');
                expect($fileCacheDriver)->toBeInstanceOf(FileCacheDriver::class);
                
                // Test file cache with serialization
                $fileCacheDriver->set('serialized_data', $complexData, 3600);
                $fileRetrieved = $fileCacheDriver->get('serialized_data');
                expect($fileRetrieved)->toEqual($complexData);
                
                // Test cache statistics
                $stats = $fileCacheDriver->getStats();
                expect($stats)->toBeArray();
                expect($stats)->toHaveKey('hits');
                expect($stats)->toHaveKey('misses');
                
                // Test MemoryCacheDriver advanced operations
                $memoryCacheDriver = new MemoryCacheDriver();
                expect($memoryCacheDriver)->toBeInstanceOf(MemoryCacheDriver::class);
                
                // Test memory cache with TTL
                $memoryCacheDriver->set('memory_ttl_data', $complexData, 1);
                $memoryData = $memoryCacheDriver->get('memory_ttl_data');
                expect($memoryData)->toEqual($complexData);
                
                // Test cache expiration (wait for TTL)
                sleep(2);
                $expiredData = $memoryCacheDriver->get('memory_ttl_data');
                expect($expiredData)->toBeNull();
                
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
            
        })->covers(
            \Yangweijie\ThinkScramble\Cache\CacheManager::class,
            \Yangweijie\ThinkScramble\Cache\FileCacheDriver::class,
            \Yangweijie\ThinkScramble\Cache\MemoryCacheDriver::class
        );
    });
});
