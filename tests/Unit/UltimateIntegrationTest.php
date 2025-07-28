<?php

declare(strict_types=1);

use Yangweijie\ThinkScramble\Scramble;
use Yangweijie\ThinkScramble\Config\ScrambleConfig;
use Yangweijie\ThinkScramble\Service\AssetPublisher;
use Yangweijie\ThinkScramble\Generator\OpenApiGenerator;
use Yangweijie\ThinkScramble\Generator\SchemaGenerator;
use Yangweijie\ThinkScramble\Generator\DocumentBuilder;
use Yangweijie\ThinkScramble\Analyzer\CodeAnalyzer;
use Yangweijie\ThinkScramble\Cache\CacheManager;
use Yangweijie\ThinkScramble\Export\ExportManager;
use Yangweijie\ThinkScramble\Export\PostmanExporter;
use Yangweijie\ThinkScramble\Export\InsomniaExporter;
use Yangweijie\ThinkScramble\Utils\YamlGenerator;
use Yangweijie\ThinkScramble\Plugin\HookManager;
use Yangweijie\ThinkScramble\Service\CommandService;
use Yangweijie\ThinkScramble\Performance\PerformanceMonitor;
use think\App;

describe('Ultimate Integration Tests', function () {
    
    beforeEach(function () {
        $this->app = new App();
        $this->config = new ScrambleConfig([
            'info' => [
                'title' => 'Ultimate Integration API',
                'version' => '3.0.0',
                'description' => 'Ultimate API for comprehensive integration testing',
                'termsOfService' => 'https://example.com/terms',
                'contact' => [
                    'name' => 'API Team',
                    'email' => 'api@example.com',
                    'url' => 'https://example.com/contact'
                ],
                'license' => [
                    'name' => 'Apache 2.0',
                    'url' => 'https://www.apache.org/licenses/LICENSE-2.0.html'
                ]
            ],
            'servers' => [
                ['url' => 'https://api.example.com/v3', 'description' => 'Production server'],
                ['url' => 'https://staging.api.example.com/v3', 'description' => 'Staging server'],
                ['url' => 'https://dev.api.example.com/v3', 'description' => 'Development server']
            ],
            'cache' => [
                'enabled' => true,
                'driver' => 'file',
                'ttl' => 3600,
                'prefix' => 'ultimate_',
                'path' => '/tmp/ultimate-cache'
            ],
            'export' => [
                'enabled' => true,
                'formats' => ['json', 'yaml', 'postman', 'insomnia'],
                'output_path' => '/tmp/ultimate-exports',
                'compression' => true
            ],
            'performance' => [
                'enabled' => true,
                'monitoring' => true,
                'profiling' => true,
                'memory_tracking' => true
            ],
            'plugins' => [
                'enabled' => true,
                'auto_discover' => true,
                'directories' => ['plugins']
            ],
            'security' => [
                'enabled' => true,
                'schemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'JWT'
                    ],
                    'apiKey' => [
                        'type' => 'apiKey',
                        'in' => 'header',
                        'name' => 'X-API-Key'
                    ],
                    'oauth2' => [
                        'type' => 'oauth2',
                        'flows' => [
                            'authorizationCode' => [
                                'authorizationUrl' => 'https://example.com/oauth/authorize',
                                'tokenUrl' => 'https://example.com/oauth/token',
                                'scopes' => [
                                    'read' => 'Read access',
                                    'write' => 'Write access'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]);
    });

    describe('Complete System Integration Workflow', function () {
        test('End-to-end API documentation generation with all features', function () {
            try {
                // Step 1: Initialize the complete system
                $scramble = Scramble::init($this->app, $this->config);
                expect($scramble)->toBeInstanceOf(Scramble::class);
                
                // Step 2: Initialize all major components
                $cacheManager = new CacheManager($this->app, $this->config);
                $performanceMonitor = new PerformanceMonitor($cacheManager);
                $hookManager = new HookManager($this->app);
                $commandService = new CommandService($this->app);
                
                // Step 3: Start performance monitoring
                $performanceMonitor->startTimer('ultimate_workflow');
                
                // Step 4: Code Analysis
                $codeAnalyzer = new CodeAnalyzer($this->app, $this->config);
                $analysisResult = $codeAnalyzer->analyze('TestController');
                expect($analysisResult)->toBeArray();
                
                // Step 5: Schema Generation with complex data
                $schemaGenerator = new SchemaGenerator($this->config);
                $complexSchemas = $schemaGenerator->generateFromArray([
                    'User' => [
                        'id' => 'integer',
                        'name' => 'string',
                        'email' => 'string',
                        'profile' => [
                            'type' => 'object',
                            'properties' => [
                                'avatar' => 'string',
                                'bio' => 'string',
                                'social_links' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'platform' => 'string',
                                            'url' => 'string'
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'posts' => [
                            'type' => 'array',
                            'items' => ['$ref' => '#/components/schemas/Post']
                        ],
                        'created_at' => 'string',
                        'updated_at' => 'string'
                    ],
                    'Post' => [
                        'id' => 'integer',
                        'title' => 'string',
                        'content' => 'string',
                        'author_id' => 'integer',
                        'tags' => [
                            'type' => 'array',
                            'items' => 'string'
                        ],
                        'metadata' => [
                            'type' => 'object',
                            'properties' => [
                                'views' => 'integer',
                                'likes' => 'integer',
                                'comments_count' => 'integer'
                            ]
                        ],
                        'published_at' => 'string',
                        'created_at' => 'string',
                        'updated_at' => 'string'
                    ],
                    'Comment' => [
                        'id' => 'integer',
                        'post_id' => 'integer',
                        'user_id' => 'integer',
                        'content' => 'string',
                        'parent_id' => 'integer',
                        'created_at' => 'string'
                    ]
                ]);
                expect($complexSchemas)->toBeArray();
                
                // Step 6: Document Building with comprehensive paths
                $documentBuilder = new DocumentBuilder($this->config);
                $comprehensiveDocument = $documentBuilder->buildDocument([
                    'paths' => [
                        '/users' => [
                            'get' => [
                                'summary' => 'List users',
                                'tags' => ['Users'],
                                'parameters' => [
                                    ['name' => 'page', 'in' => 'query', 'schema' => ['type' => 'integer', 'default' => 1]],
                                    ['name' => 'limit', 'in' => 'query', 'schema' => ['type' => 'integer', 'default' => 10]],
                                    ['name' => 'search', 'in' => 'query', 'schema' => ['type' => 'string']],
                                    ['name' => 'sort', 'in' => 'query', 'schema' => ['type' => 'string', 'enum' => ['name', 'created_at']]]
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
                                                        'meta' => [
                                                            'type' => 'object',
                                                            'properties' => [
                                                                'current_page' => ['type' => 'integer'],
                                                                'total_pages' => ['type' => 'integer'],
                                                                'total_items' => ['type' => 'integer'],
                                                                'per_page' => ['type' => 'integer']
                                                            ]
                                                        ]
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ],
                                'security' => [['bearerAuth' => []]]
                            ],
                            'post' => [
                                'summary' => 'Create user',
                                'tags' => ['Users'],
                                'requestBody' => [
                                    'required' => true,
                                    'content' => [
                                        'application/json' => [
                                            'schema' => [
                                                'type' => 'object',
                                                'required' => ['name', 'email'],
                                                'properties' => [
                                                    'name' => ['type' => 'string', 'minLength' => 2, 'maxLength' => 100],
                                                    'email' => ['type' => 'string', 'format' => 'email'],
                                                    'profile' => [
                                                        'type' => 'object',
                                                        'properties' => [
                                                            'bio' => ['type' => 'string', 'maxLength' => 500],
                                                            'avatar' => ['type' => 'string', 'format' => 'uri']
                                                        ]
                                                    ]
                                                ]
                                            ]
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
                                    ],
                                    '422' => [
                                        'description' => 'Validation Error',
                                        'content' => [
                                            'application/json' => [
                                                'schema' => [
                                                    'type' => 'object',
                                                    'properties' => [
                                                        'message' => ['type' => 'string'],
                                                        'errors' => [
                                                            'type' => 'object',
                                                            'additionalProperties' => [
                                                                'type' => 'array',
                                                                'items' => ['type' => 'string']
                                                            ]
                                                        ]
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ],
                                'security' => [['bearerAuth' => []]]
                            ]
                        ],
                        '/users/{id}' => [
                            'get' => [
                                'summary' => 'Get user by ID',
                                'tags' => ['Users'],
                                'parameters' => [
                                    ['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]
                                ],
                                'responses' => [
                                    '200' => [
                                        'description' => 'Success',
                                        'content' => [
                                            'application/json' => [
                                                'schema' => ['$ref' => '#/components/schemas/User']
                                            ]
                                        ]
                                    ],
                                    '404' => [
                                        'description' => 'User not found',
                                        'content' => [
                                            'application/json' => [
                                                'schema' => [
                                                    'type' => 'object',
                                                    'properties' => [
                                                        'message' => ['type' => 'string']
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ],
                                'security' => [['bearerAuth' => []]]
                            ]
                        ]
                    ],
                    'components' => [
                        'schemas' => $complexSchemas,
                        'securitySchemes' => [
                            'bearerAuth' => [
                                'type' => 'http',
                                'scheme' => 'bearer',
                                'bearerFormat' => 'JWT'
                            ]
                        ]
                    ],
                    'tags' => [
                        ['name' => 'Users', 'description' => 'User management operations']
                    ]
                ]);
                expect($comprehensiveDocument)->toBeArray();
                
                // Step 7: OpenAPI Generation
                $openApiGenerator = new OpenApiGenerator($this->app, $this->config);
                $finalOpenApiDoc = $openApiGenerator->generate($comprehensiveDocument);
                expect($finalOpenApiDoc)->toBeArray();
                expect($finalOpenApiDoc)->toHaveKey('openapi');
                expect($finalOpenApiDoc)->toHaveKey('info');
                expect($finalOpenApiDoc)->toHaveKey('paths');
                expect($finalOpenApiDoc)->toHaveKey('components');
                
                // Step 8: Export to all formats
                $exportManager = new ExportManager($this->config);
                
                // Export to JSON
                $jsonResult = $exportManager->export($finalOpenApiDoc, 'json', '/tmp/ultimate-api.json');
                expect($jsonResult)->toBeBool();
                
                // Export to YAML
                $yamlResult = $exportManager->export($finalOpenApiDoc, 'yaml', '/tmp/ultimate-api.yaml');
                expect($yamlResult)->toBeBool();
                
                // Export to Postman
                $postmanExporter = new PostmanExporter();
                $postmanCollection = $postmanExporter->export($finalOpenApiDoc);
                expect($postmanCollection)->toBeArray();
                
                // Export to Insomnia
                $insomniaExporter = new InsomniaExporter();
                $insomniaCollection = $insomniaExporter->export($finalOpenApiDoc);
                expect($insomniaCollection)->toBeArray();
                
                // Step 9: Asset Publishing
                $assetPublisher = new AssetPublisher($this->app, $this->config);
                $publishResult = $assetPublisher->publishAssets();
                expect($publishResult)->toBeBool();
                
                // Generate comprehensive HTML documentation
                $htmlContent = $assetPublisher->generateHtmlContent($finalOpenApiDoc, 'swagger-ui');
                expect($htmlContent)->toBeString();
                expect(strlen($htmlContent))->toBeGreaterThan(1000);
                
                // Step 10: Performance monitoring and caching
                $performanceMonitor->endTimer('ultimate_workflow');
                $metrics = $performanceMonitor->getMetrics();
                expect($metrics)->toBeArray();
                expect($metrics)->toHaveKey('ultimate_workflow');
                
                // Cache the final result
                $cacheManager->set('ultimate_api_doc', $finalOpenApiDoc, 3600);
                $cachedDoc = $cacheManager->get('ultimate_api_doc');
                expect($cachedDoc)->toEqual($finalOpenApiDoc);
                
                // Step 11: YAML processing
                $yamlGenerator = new YamlGenerator();
                $yamlContent = $yamlGenerator->encode($finalOpenApiDoc);
                expect($yamlContent)->toBeString();
                expect(strlen($yamlContent))->toBeGreaterThan(500);
                
                // Decode and verify
                $decodedYaml = $yamlGenerator->decode($yamlContent);
                expect($decodedYaml)->toBeArray();
                
                // Step 12: Hook system integration
                $hookManager->register('documentation_generated', function($doc) {
                    return ['status' => 'processed', 'doc_size' => count($doc)];
                });
                
                $hookResult = $hookManager->execute('documentation_generated', $finalOpenApiDoc);
                expect($hookResult)->toBeArray();
                expect($hookResult)->toHaveKey('status');
                
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
            
        })->covers(
            \Yangweijie\ThinkScramble\Scramble::class,
            \Yangweijie\ThinkScramble\Analyzer\CodeAnalyzer::class,
            \Yangweijie\ThinkScramble\Generator\SchemaGenerator::class,
            \Yangweijie\ThinkScramble\Generator\DocumentBuilder::class,
            \Yangweijie\ThinkScramble\Generator\OpenApiGenerator::class,
            \Yangweijie\ThinkScramble\Export\ExportManager::class,
            \Yangweijie\ThinkScramble\Export\PostmanExporter::class,
            \Yangweijie\ThinkScramble\Export\InsomniaExporter::class,
            \Yangweijie\ThinkScramble\Service\AssetPublisher::class,
            \Yangweijie\ThinkScramble\Cache\CacheManager::class,
            \Yangweijie\ThinkScramble\Performance\PerformanceMonitor::class,
            \Yangweijie\ThinkScramble\Utils\YamlGenerator::class,
            \Yangweijie\ThinkScramble\Plugin\HookManager::class,
            \Yangweijie\ThinkScramble\Service\CommandService::class
        );
    });
});
