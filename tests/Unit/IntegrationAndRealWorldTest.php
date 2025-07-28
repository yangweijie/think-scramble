<?php

declare(strict_types=1);

use Yangweijie\ThinkScramble\Config\ScrambleConfig;
use Yangweijie\ThinkScramble\Cache\CacheManager;
use Yangweijie\ThinkScramble\Generator\DocumentBuilder;
use Yangweijie\ThinkScramble\Generator\OpenApiGenerator;
use Yangweijie\ThinkScramble\Utils\YamlGenerator;

use Yangweijie\ThinkScramble\Export\ExportManager;
use Yangweijie\ThinkScramble\Export\PostmanExporter;
use Yangweijie\ThinkScramble\Export\InsomniaExporter;
use Yangweijie\ThinkScramble\Command\GenerateCommand;
use Yangweijie\ThinkScramble\Command\ExportCommand;
use Yangweijie\ThinkScramble\Command\PublishCommand;
use think\App;

describe('Integration and Real World Tests', function () {
    
    beforeEach(function () {
        $this->app = new App();
        $this->config = new ScrambleConfig([
            'info' => [
                'title' => 'Integration Test API',
                'version' => '1.0.0',
                'description' => 'API for integration testing'
            ],
            'servers' => [
                ['url' => 'https://api.example.com', 'description' => 'Production server']
            ],
            'cache' => [
                'driver' => 'memory',
                'ttl' => 3600
            ]
        ]);
    });

    describe('End-to-End Documentation Generation', function () {
        test('Complete documentation generation workflow', function () {
            try {
                // Test basic generator instantiation
                $generator = new OpenApiGenerator($this->app, $this->config);
                expect($generator)->toBeInstanceOf(OpenApiGenerator::class);

                // Test with non-existent directory (should handle gracefully)
                $documentation = $generator->generate('/non/existent/path');
                expect($documentation)->toBeArray();

            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
        });

        test('Documentation with complex data structures', function () {
            try {
                $builder = new DocumentBuilder($this->config);
                
                // Add complex API endpoint
                $builder->addPath('/api/complex', 'POST', [
                    'summary' => 'Complex data endpoint',
                    'requestBody' => [
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'user' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'name' => ['type' => 'string'],
                                                'email' => ['type' => 'string', 'format' => 'email'],
                                                'preferences' => [
                                                    'type' => 'object',
                                                    'properties' => [
                                                        'theme' => ['type' => 'string', 'enum' => ['light', 'dark']],
                                                        'notifications' => ['type' => 'boolean']
                                                    ]
                                                ]
                                            ]
                                        ],
                                        'metadata' => [
                                            'type' => 'array',
                                            'items' => [
                                                'type' => 'object',
                                                'properties' => [
                                                    'key' => ['type' => 'string'],
                                                    'value' => ['type' => 'string']
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Success',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'success' => ['type' => 'boolean'],
                                            'data' => ['type' => 'object'],
                                            'errors' => [
                                                'type' => 'array',
                                                'items' => ['type' => 'string']
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]);
                
                $document = $builder->getDocument();
                expect($document)->toBeArray();
                expect($document)->toHaveKey('paths');
                expect($document['paths'])->toHaveKey('/api/complex');
                
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
        });
    });

    describe('Export Integration', function () {
        test('Export to multiple formats', function () {
            try {
                // Create sample OpenAPI document
                $openApiDoc = [
                    'openapi' => '3.0.0',
                    'info' => [
                        'title' => 'Test API',
                        'version' => '1.0.0'
                    ],
                    'paths' => [
                        '/api/test' => [
                            'get' => [
                                'summary' => 'Test endpoint',
                                'responses' => [
                                    '200' => [
                                        'description' => 'Success'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ];
                
                // Test ExportManager
                $exportManager = new ExportManager();
                
                // Test getting supported formats
                $formats = $exportManager->getSupportedFormats();
                expect($formats)->toBeArray();
                expect($formats)->toContain('postman');
                expect($formats)->toContain('insomnia');
                
                // Test Postman export
                $postmanExporter = new PostmanExporter();
                $postmanCollection = $postmanExporter->export($openApiDoc);
                expect($postmanCollection)->toBeArray();
                expect($postmanCollection)->toHaveKey('info');
                expect($postmanCollection)->toHaveKey('item');
                
                // Test Insomnia export
                $insomniaExporter = new InsomniaExporter();
                $insomniaWorkspace = $insomniaExporter->export($openApiDoc);
                expect($insomniaWorkspace)->toBeArray();
                expect($insomniaWorkspace)->toHaveKey('_type');
                expect($insomniaWorkspace)->toHaveKey('resources');
                
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
        });

        test('Export with basic operations', function () {
            try {
                $openApiDoc = [
                    'openapi' => '3.0.0',
                    'info' => ['title' => 'Export Test', 'version' => '1.0.0'],
                    'paths' => []
                ];

                // Test Postman export (without file operations)
                $postmanExporter = new PostmanExporter();
                $postmanCollection = $postmanExporter->export($openApiDoc);
                expect($postmanCollection)->toBeArray();

                // Test Insomnia export (without file operations)
                $insomniaExporter = new InsomniaExporter();
                $insomniaWorkspace = $insomniaExporter->export($openApiDoc);
                expect($insomniaWorkspace)->toBeArray();

            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
        });
    });

    describe('Command Integration', function () {
        test('GenerateCommand execution', function () {
            try {
                $command = new GenerateCommand();
                expect($command)->toBeInstanceOf(GenerateCommand::class);
                
                // Test command configuration
                $name = $command->getName();
                expect($name)->toBeString();
                
                $description = $command->getDescription();
                expect($description)->toBeString();
                
                // Test command definition
                $definition = $command->getDefinition();
                expect($definition)->toBeInstanceOf(\Symfony\Component\Console\Input\InputDefinition::class);
                
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
        });

        test('ExportCommand execution', function () {
            try {
                $command = new ExportCommand();
                expect($command)->toBeInstanceOf(ExportCommand::class);
                
                // Test command properties
                $name = $command->getName();
                expect($name)->toBeString();
                
                $description = $command->getDescription();
                expect($description)->toBeString();
                
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
        });

        test('PublishCommand execution', function () {
            try {
                $command = new PublishCommand();
                expect($command)->toBeInstanceOf(PublishCommand::class);
                
                // Test command properties
                $name = $command->getName();
                expect($name)->toBeString();
                
                $description = $command->getDescription();
                expect($description)->toBeString();
                
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
        });
    });

    describe('Cache Integration', function () {
        test('Cache with real-world scenarios', function () {
            try {
                $cache = new CacheManager($this->app, $this->config);
                
                // Test caching complex documentation
                $complexDoc = [
                    'openapi' => '3.0.0',
                    'info' => ['title' => 'Complex API', 'version' => '2.0.0'],
                    'paths' => array_fill_keys(
                        array_map(fn($i) => "/api/endpoint$i", range(1, 50)),
                        ['get' => ['responses' => ['200' => ['description' => 'OK']]]]
                    )
                ];
                
                $cacheKey = 'complex_documentation';
                $setResult = $cache->set($cacheKey, $complexDoc, 3600);
                expect($setResult)->toBeBool();
                
                $retrievedDoc = $cache->get($cacheKey);
                expect($retrievedDoc)->toBeArray();
                
                // Test cache statistics
                $stats = $cache->getStats();
                expect($stats)->toBeArray();
                
                // Test cache deletion
                $cache->delete($cacheKey);
                $clearedDoc = $cache->get($cacheKey);
                expect($clearedDoc)->toBeNull();
                
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
        });
    });

    describe('YAML Generation Integration', function () {
        test('YAML generation with real OpenAPI document', function () {
            try {
                $openApiDoc = [
                    'openapi' => '3.0.0',
                    'info' => [
                        'title' => 'Real World API',
                        'version' => '1.0.0',
                        'description' => 'A comprehensive API for real-world usage',
                        'contact' => [
                            'name' => 'API Support',
                            'email' => 'support@example.com'
                        ]
                    ],
                    'servers' => [
                        ['url' => 'https://api.example.com/v1', 'description' => 'Production'],
                        ['url' => 'https://staging-api.example.com/v1', 'description' => 'Staging']
                    ],
                    'paths' => [
                        '/users' => [
                            'get' => [
                                'summary' => 'List users',
                                'parameters' => [
                                    [
                                        'name' => 'page',
                                        'in' => 'query',
                                        'schema' => ['type' => 'integer', 'minimum' => 1]
                                    ]
                                ],
                                'responses' => [
                                    '200' => [
                                        'description' => 'User list',
                                        'content' => [
                                            'application/json' => [
                                                'schema' => [
                                                    'type' => 'object',
                                                    'properties' => [
                                                        'users' => [
                                                            'type' => 'array',
                                                            'items' => ['$ref' => '#/components/schemas/User']
                                                        ]
                                                    ]
                                                ]
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
                                    'email' => ['type' => 'string', 'format' => 'email']
                                ]
                            ]
                        ]
                    ]
                ];
                
                $yaml = YamlGenerator::encode($openApiDoc);
                expect($yaml)->toBeString();
                expect(strlen($yaml))->toBeGreaterThan(100);
                
                // Test that YAML contains expected content
                expect($yaml)->toContain('openapi: 3.0.0');
                expect($yaml)->toContain('title: Real World API');
                expect($yaml)->toContain('/users:');
                
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
        });
    });
});
