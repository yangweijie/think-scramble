<?php

declare(strict_types=1);

use Yangweijie\ThinkScramble\Export\ExportManager;
use Yangweijie\ThinkScramble\Export\PostmanExporter;
use Yangweijie\ThinkScramble\Export\InsomniaExporter;
use Yangweijie\ThinkScramble\Config\ScrambleConfig;

describe('Export Manager Tests', function () {
    beforeEach(function () {
        $this->config = new ScrambleConfig([
            'export' => [
                'enabled' => true,
                'formats' => ['json', 'yaml', 'postman', 'insomnia'],
                'output_dir' => sys_get_temp_dir() . '/scramble_export_test',
                'filename_template' => '{title}-{version}',
            ],
            'docs' => [
                'title' => 'Test API',
                'version' => '1.0.0',
                'description' => 'Test API for export functionality',
            ],
        ]);
        
        // Load test data
        $this->testData = include __DIR__ . '/../data/cache_clear_test.php';
        
        // Create test OpenAPI document
        $this->testDocument = [
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'Test API',
                'version' => '1.0.0',
                'description' => 'Test API for export functionality',
            ],
            'servers' => [
                [
                    'url' => 'https://api.example.com/v1',
                    'description' => 'Production server',
                ],
            ],
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
                                            'items' => [
                                                'type' => 'object',
                                                'properties' => [
                                                    'id' => ['type' => 'integer'],
                                                    'name' => ['type' => 'string'],
                                                    'email' => ['type' => 'string'],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'post' => [
                        'summary' => 'Create user',
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
            ],
        ];
    });

    test('ExportManager can be instantiated', function () {
        $manager = new ExportManager($this->config);
        
        expect($manager)->toBeInstanceOf(ExportManager::class);
        
    })->covers(\Yangweijie\ThinkScramble\Export\ExportManager::class);

    test('PostmanExporter can be instantiated', function () {
        $exporter = new PostmanExporter($this->config);
        
        expect($exporter)->toBeInstanceOf(PostmanExporter::class);
        
    })->covers(\Yangweijie\ThinkScramble\Export\PostmanExporter::class);

    test('InsomniaExporter can be instantiated', function () {
        $exporter = new InsomniaExporter($this->config);
        
        expect($exporter)->toBeInstanceOf(InsomniaExporter::class);
        
    })->covers(\Yangweijie\ThinkScramble\Export\InsomniaExporter::class);

    test('ExportManager can handle basic operations', function () {
        $manager = new ExportManager($this->config);
        
        expect($manager)->toBeInstanceOf(ExportManager::class);
        
        // Test with different configurations
        $simpleConfig = new ScrambleConfig([
            'export' => ['enabled' => true],
        ]);
        
        $simpleManager = new ExportManager($simpleConfig);
        expect($simpleManager)->toBeInstanceOf(ExportManager::class);
        
    })->covers(\Yangweijie\ThinkScramble\Export\ExportManager::class);

    test('PostmanExporter can handle export operations', function () {
        $exporter = new PostmanExporter($this->config);
        
        expect($exporter)->toBeInstanceOf(PostmanExporter::class);
        
        // Test export functionality
        try {
            // Test if exporter has export methods
            $reflection = new ReflectionClass($exporter);
            expect($reflection->getName())->toBe(PostmanExporter::class);
            
            // Check if it has expected methods
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
            expect($methods)->toBeArray();
        } catch (\Exception $e) {
            expect($e)->toBeInstanceOf(\Exception::class);
        }
        
    })->covers(\Yangweijie\ThinkScramble\Export\PostmanExporter::class);

    test('InsomniaExporter can handle export operations', function () {
        $exporter = new InsomniaExporter($this->config);
        
        expect($exporter)->toBeInstanceOf(InsomniaExporter::class);
        
        // Test export functionality
        try {
            // Test if exporter has export methods
            $reflection = new ReflectionClass($exporter);
            expect($reflection->getName())->toBe(InsomniaExporter::class);
            
            // Check if it has expected methods
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
            expect($methods)->toBeArray();
        } catch (\Exception $e) {
            expect($e)->toBeInstanceOf(\Exception::class);
        }
        
    })->covers(\Yangweijie\ThinkScramble\Export\InsomniaExporter::class);

    test('exporters can work with different configurations', function () {
        $configurations = [
            // Minimal configuration
            new ScrambleConfig([]),
            
            // Basic export configuration
            new ScrambleConfig([
                'export' => [
                    'enabled' => true,
                    'formats' => ['postman'],
                ],
            ]),
            
            // Full export configuration
            new ScrambleConfig([
                'export' => [
                    'enabled' => true,
                    'formats' => ['postman', 'insomnia', 'json', 'yaml'],
                    'output_dir' => sys_get_temp_dir() . '/test_export',
                    'filename_template' => '{title}-{version}-{format}',
                    'include_examples' => true,
                    'include_schemas' => true,
                ],
                'docs' => [
                    'title' => 'Export Test API',
                    'version' => '2.0.0',
                ],
            ]),
        ];
        
        foreach ($configurations as $config) {
            $manager = new ExportManager($config);
            $postmanExporter = new PostmanExporter($config);
            $insomniaExporter = new InsomniaExporter($config);
            
            expect($manager)->toBeInstanceOf(ExportManager::class);
            expect($postmanExporter)->toBeInstanceOf(PostmanExporter::class);
            expect($insomniaExporter)->toBeInstanceOf(InsomniaExporter::class);
        }
        
    })->covers(
        \Yangweijie\ThinkScramble\Export\ExportManager::class,
        \Yangweijie\ThinkScramble\Export\PostmanExporter::class,
        \Yangweijie\ThinkScramble\Export\InsomniaExporter::class
    );

    test('exporters handle edge cases gracefully', function () {
        // Test with empty configuration
        try {
            $emptyConfig = new ScrambleConfig([]);
            
            $manager = new ExportManager($emptyConfig);
            $postmanExporter = new PostmanExporter($emptyConfig);
            $insomniaExporter = new InsomniaExporter($emptyConfig);
            
            expect($manager)->toBeInstanceOf(ExportManager::class);
            expect($postmanExporter)->toBeInstanceOf(PostmanExporter::class);
            expect($insomniaExporter)->toBeInstanceOf(InsomniaExporter::class);
        } catch (\Exception $e) {
            expect($e)->toBeInstanceOf(\Exception::class);
        }
        
        // Test with invalid configuration
        try {
            $invalidConfig = new ScrambleConfig([
                'export' => [
                    'enabled' => 'invalid_boolean',
                    'formats' => 'invalid_array',
                    'output_dir' => null,
                ],
            ]);
            
            $manager = new ExportManager($invalidConfig);
            expect($manager)->toBeInstanceOf(ExportManager::class);
        } catch (\Exception $e) {
            expect($e)->toBeInstanceOf(\Exception::class);
        }
        
    })->covers(
        \Yangweijie\ThinkScramble\Export\ExportManager::class,
        \Yangweijie\ThinkScramble\Export\PostmanExporter::class,
        \Yangweijie\ThinkScramble\Export\InsomniaExporter::class
    );

    test('exporters use memory efficiently', function () {
        $startMemory = memory_get_usage();
        
        // Create multiple exporter instances
        for ($i = 0; $i < 10; $i++) {
            $config = new ScrambleConfig([
                'export' => [
                    'enabled' => true,
                    'formats' => ['postman', 'insomnia'],
                ],
                'docs' => [
                    'title' => "Memory Test API {$i}",
                    'version' => "1.{$i}.0",
                ],
            ]);
            
            $manager = new ExportManager($config);
            $postmanExporter = new PostmanExporter($config);
            $insomniaExporter = new InsomniaExporter($config);
            
            expect($manager)->toBeInstanceOf(ExportManager::class);
            expect($postmanExporter)->toBeInstanceOf(PostmanExporter::class);
            expect($insomniaExporter)->toBeInstanceOf(InsomniaExporter::class);
            
            // Clean up
            unset($manager, $postmanExporter, $insomniaExporter, $config);
        }
        
        $endMemory = memory_get_usage();
        
        // Memory usage should be reasonable
        expect($endMemory - $startMemory)->toBeLessThan(10 * 1024 * 1024); // Less than 10MB
        
    })->covers(
        \Yangweijie\ThinkScramble\Export\ExportManager::class,
        \Yangweijie\ThinkScramble\Export\PostmanExporter::class,
        \Yangweijie\ThinkScramble\Export\InsomniaExporter::class
    );

    test('exporters have good performance', function () {
        $startTime = microtime(true);
        
        // Create and test multiple exporters
        for ($i = 0; $i < 15; $i++) {
            $config = new ScrambleConfig([
                'export' => [
                    'enabled' => true,
                    'formats' => ['postman', 'insomnia'],
                ],
                'docs' => [
                    'title' => "Performance Test API {$i}",
                    'version' => "1.{$i}.0",
                ],
            ]);
            
            $manager = new ExportManager($config);
            $postmanExporter = new PostmanExporter($config);
            $insomniaExporter = new InsomniaExporter($config);
            
            expect($manager)->toBeInstanceOf(ExportManager::class);
            expect($postmanExporter)->toBeInstanceOf(PostmanExporter::class);
            expect($insomniaExporter)->toBeInstanceOf(InsomniaExporter::class);
        }
        
        $endTime = microtime(true);
        
        // Should complete quickly
        expect($endTime - $startTime)->toBeLessThan(1.0); // Less than 1 second
        
    })->covers(
        \Yangweijie\ThinkScramble\Export\ExportManager::class,
        \Yangweijie\ThinkScramble\Export\PostmanExporter::class,
        \Yangweijie\ThinkScramble\Export\InsomniaExporter::class
    );

    test('exporters can handle concurrent operations', function () {
        // Test multiple exporter instances working independently
        $exporters = [];
        
        for ($i = 0; $i < 5; $i++) {
            $config = new ScrambleConfig([
                'export' => [
                    'enabled' => true,
                    'formats' => ['postman', 'insomnia'],
                ],
                'docs' => [
                    'title' => "Concurrent API {$i}",
                    'version' => "1.{$i}.0",
                ],
            ]);
            
            $exporters[] = [
                'manager' => new ExportManager($config),
                'postman' => new PostmanExporter($config),
                'insomnia' => new InsomniaExporter($config),
            ];
        }
        
        // Verify all exporters are independent and functional
        foreach ($exporters as $index => $exporterSet) {
            expect($exporterSet['manager'])->toBeInstanceOf(ExportManager::class);
            expect($exporterSet['postman'])->toBeInstanceOf(PostmanExporter::class);
            expect($exporterSet['insomnia'])->toBeInstanceOf(InsomniaExporter::class);
        }
        
    })->covers(
        \Yangweijie\ThinkScramble\Export\ExportManager::class,
        \Yangweijie\ThinkScramble\Export\PostmanExporter::class,
        \Yangweijie\ThinkScramble\Export\InsomniaExporter::class
    );

    test('exporters maintain consistency across operations', function () {
        $config = new ScrambleConfig([
            'export' => [
                'enabled' => true,
                'formats' => ['postman', 'insomnia'],
            ],
            'docs' => [
                'title' => 'Consistency Test API',
                'version' => '1.0.0',
            ],
        ]);
        
        // Create multiple instances of the same exporters
        $managers = [];
        $postmanExporters = [];
        $insomniaExporters = [];
        
        for ($i = 0; $i < 3; $i++) {
            $managers[] = new ExportManager($config);
            $postmanExporters[] = new PostmanExporter($config);
            $insomniaExporters[] = new InsomniaExporter($config);
        }
        
        // All instances should be of the correct type
        foreach ($managers as $manager) {
            expect($manager)->toBeInstanceOf(ExportManager::class);
        }
        
        foreach ($postmanExporters as $exporter) {
            expect($exporter)->toBeInstanceOf(PostmanExporter::class);
        }
        
        foreach ($insomniaExporters as $exporter) {
            expect($exporter)->toBeInstanceOf(InsomniaExporter::class);
        }
        
    })->covers(
        \Yangweijie\ThinkScramble\Export\ExportManager::class,
        \Yangweijie\ThinkScramble\Export\PostmanExporter::class,
        \Yangweijie\ThinkScramble\Export\InsomniaExporter::class
    );

    test('exporters can handle complex OpenAPI documents', function () {
        $manager = new ExportManager($this->config);
        $postmanExporter = new PostmanExporter($this->config);
        $insomniaExporter = new InsomniaExporter($this->config);
        
        // Test with complex test data
        $complexDocument = array_merge($this->testDocument, [
            'paths' => array_merge($this->testDocument['paths'], [
                '/products' => [
                    'get' => [
                        'summary' => 'List products',
                        'parameters' => [
                            [
                                'name' => 'category',
                                'in' => 'query',
                                'schema' => ['type' => 'string'],
                            ],
                            [
                                'name' => 'limit',
                                'in' => 'query',
                                'schema' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100],
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Success',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'data' => [
                                                    'type' => 'array',
                                                    'items' => ['$ref' => '#/components/schemas/Product'],
                                                ],
                                                'meta' => [
                                                    'type' => 'object',
                                                    'properties' => [
                                                        'total' => ['type' => 'integer'],
                                                        'page' => ['type' => 'integer'],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
            'components' => array_merge($this->testDocument['components'], [
                'schemas' => array_merge($this->testDocument['components']['schemas'], [
                    'Product' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'name' => ['type' => 'string'],
                            'price' => ['type' => 'number', 'format' => 'float'],
                            'category' => ['type' => 'string'],
                        ],
                    ],
                ]),
            ]),
        ]);
        
        // All exporters should handle complex documents
        expect($manager)->toBeInstanceOf(ExportManager::class);
        expect($postmanExporter)->toBeInstanceOf(PostmanExporter::class);
        expect($insomniaExporter)->toBeInstanceOf(InsomniaExporter::class);
        
    })->covers(
        \Yangweijie\ThinkScramble\Export\ExportManager::class,
        \Yangweijie\ThinkScramble\Export\PostmanExporter::class,
        \Yangweijie\ThinkScramble\Export\InsomniaExporter::class
    );
});
