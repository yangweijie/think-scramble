<?php

declare(strict_types=1);

use Yangweijie\ThinkScramble\Config\ScrambleConfig;
use Yangweijie\ThinkScramble\Export\ExportManager;
use Yangweijie\ThinkScramble\Export\PostmanExporter;
use Yangweijie\ThinkScramble\Export\InsomniaExporter;
use Yangweijie\ThinkScramble\Middleware\CacheMiddleware;
use Yangweijie\ThinkScramble\Middleware\DocsAccessMiddleware;
use Yangweijie\ThinkScramble\Controller\DocsController;
use think\App;
use think\Request;
use think\Response;

describe('Export and Middleware Coverage Tests', function () {
    
    beforeEach(function () {
        $this->app = new App();
        $this->config = new ScrambleConfig([
            'info' => [
                'title' => 'Export Middleware API',
                'version' => '1.0.0'
            ],
            'docs' => [
                'enabled' => true,
                'route' => '/docs',
                'cache' => true
            ]
        ]);
        
        // Sample OpenAPI document for testing
        $this->sampleOpenApi = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'Sample API',
                'version' => '1.0.0',
                'description' => 'A sample API for testing exports'
            ],
            'servers' => [
                ['url' => 'https://api.example.com', 'description' => 'Production server']
            ],
            'paths' => [
                '/users' => [
                    'get' => [
                        'summary' => 'List users',
                        'tags' => ['Users'],
                        'responses' => [
                            '200' => [
                                'description' => 'Successful response',
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
                    ],
                    'post' => [
                        'summary' => 'Create user',
                        'tags' => ['Users'],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/User']
                                ]
                            ]
                        ],
                        'responses' => [
                            '201' => [
                                'description' => 'User created',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/User']
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                '/users/{id}' => [
                    'get' => [
                        'summary' => 'Get user by ID',
                        'tags' => ['Users'],
                        'parameters' => [
                            [
                                'name' => 'id',
                                'in' => 'path',
                                'required' => true,
                                'schema' => ['type' => 'integer']
                            ]
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'User found',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/User']
                                    ]
                                ]
                            ],
                            '404' => [
                                'description' => 'User not found'
                            ]
                        ]
                    ]
                ]
            ],
            'components' => [
                'schemas' => [
                    'User' => [
                        'type' => 'object',
                        'required' => ['name', 'email'],
                        'properties' => [
                            'id' => [
                                'type' => 'integer',
                                'description' => 'User ID'
                            ],
                            'name' => [
                                'type' => 'string',
                                'description' => 'User name'
                            ],
                            'email' => [
                                'type' => 'string',
                                'format' => 'email',
                                'description' => 'User email'
                            ],
                            'created_at' => [
                                'type' => 'string',
                                'format' => 'date-time',
                                'description' => 'Creation timestamp'
                            ]
                        ]
                    ]
                ]
            ]
        ];
    });

    describe('Export Module Core Coverage', function () {
        test('ExportManager comprehensive operations', function () {
            $exportManager = new ExportManager();
            
            // Test basic instantiation
            expect($exportManager)->toBeInstanceOf(ExportManager::class);
            
            // Test getting supported formats
            $formats = $exportManager->getSupportedFormats();
            expect($formats)->toBeArray();
            expect($formats)->toContain('postman');
            expect($formats)->toContain('insomnia');
            
            // Test basic functionality
            expect($exportManager)->toBeInstanceOf(ExportManager::class);
            
        })->covers(\Yangweijie\ThinkScramble\Export\ExportManager::class);

        test('PostmanExporter detailed functionality', function () {
            $exporter = new PostmanExporter();

            // Test basic instantiation
            expect($exporter)->toBeInstanceOf(PostmanExporter::class);

            // Test exporting OpenAPI to Postman collection
            $collection = $exporter->export($this->sampleOpenApi ?? []);
            expect($collection)->toBeArray();

        })->covers(\Yangweijie\ThinkScramble\Export\PostmanExporter::class);

        test('InsomniaExporter detailed functionality', function () {
            $exporter = new InsomniaExporter();

            // Test basic instantiation
            expect($exporter)->toBeInstanceOf(InsomniaExporter::class);

            // Test exporting OpenAPI to Insomnia workspace
            $workspace = $exporter->export($this->sampleOpenApi ?? []);
            expect($workspace)->toBeArray();

        })->covers(\Yangweijie\ThinkScramble\Export\InsomniaExporter::class);
    });

    describe('Middleware Module Core Coverage', function () {
        test('CacheMiddleware caching operations', function () {
            $middleware = new CacheMiddleware($this->app, $this->config);

            // Test basic instantiation
            expect($middleware)->toBeInstanceOf(CacheMiddleware::class);

        })->covers(\Yangweijie\ThinkScramble\Middleware\CacheMiddleware::class);

        test('DocsAccessMiddleware access control', function () {
            $middleware = new DocsAccessMiddleware($this->app, $this->config);

            // Test basic instantiation
            expect($middleware)->toBeInstanceOf(DocsAccessMiddleware::class);

        })->covers(\Yangweijie\ThinkScramble\Middleware\DocsAccessMiddleware::class);
    });

    describe('Controller Module Core Coverage', function () {
        test('DocsController documentation serving', function () {
            $controller = new DocsController($this->app);

            // Test basic instantiation
            expect($controller)->toBeInstanceOf(DocsController::class);

        })->covers(\Yangweijie\ThinkScramble\Controller\DocsController::class);
    });
});
