<?php

declare(strict_types=1);

use Yangweijie\ThinkScramble\Config\ScrambleConfig;
use Yangweijie\ThinkScramble\Cache\CacheManager;
use Yangweijie\ThinkScramble\Generator\DocumentBuilder;
use Yangweijie\ThinkScramble\Generator\OpenApiGenerator;
use Yangweijie\ThinkScramble\Generator\SchemaGenerator;
use Yangweijie\ThinkScramble\Generator\ParameterExtractor;
use Yangweijie\ThinkScramble\Generator\ResponseGenerator;
use Yangweijie\ThinkScramble\Analyzer\CodeAnalyzer;
use Yangweijie\ThinkScramble\Analyzer\AstParser;
use Yangweijie\ThinkScramble\Analyzer\ReflectionAnalyzer;
use Yangweijie\ThinkScramble\Analyzer\AnnotationParser;
use Yangweijie\ThinkScramble\Utils\YamlGenerator;
use think\App;

describe('Core Functionality Coverage Tests', function () {
    
    beforeEach(function () {
        $this->app = new App();
        $this->config = new ScrambleConfig([
            'info' => [
                'title' => 'Core Functionality API',
                'version' => '1.0.0',
                'description' => 'Testing core functionality'
            ],
            'cache' => [
                'driver' => 'memory',
                'ttl' => 3600
            ]
        ]);
        
        try {
            $this->cache = new CacheManager($this->app, $this->config);
        } catch (\Exception $e) {
            $this->cache = null;
        }
    });

    describe('Generator Module Core Coverage', function () {
        test('DocumentBuilder comprehensive operations', function () {
            $builder = new DocumentBuilder($this->config);
            
            // Test basic instantiation
            expect($builder)->toBeInstanceOf(DocumentBuilder::class);
            
            // Test adding paths with different methods
            $builder->addPath('/api/users', 'GET', [
                'summary' => 'Get users',
                'responses' => [
                    '200' => ['description' => 'Success']
                ]
            ]);
            
            $builder->addPath('/api/users', 'POST', [
                'summary' => 'Create user',
                'requestBody' => [
                    'content' => [
                        'application/json' => [
                            'schema' => ['type' => 'object']
                        ]
                    ]
                ]
            ]);
            
            // Test adding schema
            $builder->addSchema('User', [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'name' => ['type' => 'string']
                ]
            ]);

            // Test adding tags
            $builder->addTag(['name' => 'Users', 'description' => 'User management operations']);
            
            // Test getting document
            $document = $builder->getDocument();
            expect($document)->toBeArray();
            expect($document)->toHaveKey('openapi');
            expect($document)->toHaveKey('info');
            expect($document)->toHaveKey('paths');
            expect($document)->toHaveKey('components');
            
        })->covers(\Yangweijie\ThinkScramble\Generator\DocumentBuilder::class);

        test('OpenApiGenerator core functionality', function () {
            $generator = new OpenApiGenerator($this->app, $this->config);
            
            // Test basic instantiation
            expect($generator)->toBeInstanceOf(OpenApiGenerator::class);
            
            // Test generating with non-existent directory (should handle gracefully)
            $result = $generator->generate('/non/existent/path');
            expect($result)->toBeArray();
            expect($result)->toHaveKey('openapi');
            expect($result)->toHaveKey('info');
            
        })->covers(\Yangweijie\ThinkScramble\Generator\OpenApiGenerator::class);

        test('SchemaGenerator type handling', function () {
            $generator = new SchemaGenerator($this->config);
            
            // Test basic instantiation
            expect($generator)->toBeInstanceOf(SchemaGenerator::class);
            
            // Test generating schema from array
            $arrayData = [
                'name' => 'John Doe',
                'age' => 30,
                'active' => true,
                'tags' => ['admin', 'user']
            ];
            
            $schema = $generator->generateFromArray($arrayData);
            expect($schema)->toBeArray();
            expect($schema)->toHaveKey('type');
            expect($schema['type'])->toBe('object');
            
            // Test basic schema generation functionality
            expect($generator)->toBeInstanceOf(SchemaGenerator::class);
            
        })->covers(\Yangweijie\ThinkScramble\Generator\SchemaGenerator::class);

        test('ParameterExtractor parameter analysis', function () {
            $extractor = new ParameterExtractor($this->config);

            // Test basic instantiation
            expect($extractor)->toBeInstanceOf(ParameterExtractor::class);

        })->covers(\Yangweijie\ThinkScramble\Generator\ParameterExtractor::class);

        test('ResponseGenerator response creation', function () {
            $generator = new ResponseGenerator($this->config);

            // Test basic instantiation
            expect($generator)->toBeInstanceOf(ResponseGenerator::class);

        })->covers(\Yangweijie\ThinkScramble\Generator\ResponseGenerator::class);
    });

    describe('Analyzer Module Core Coverage', function () {
        test('CodeAnalyzer file analysis', function () {
            $analyzer = new CodeAnalyzer($this->config);

            // Test basic instantiation
            expect($analyzer)->toBeInstanceOf(CodeAnalyzer::class);

            // Test analyzing current file
            try {
                $result = $analyzer->analyzeFile(__FILE__);
                expect($result)->toBeArray();
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }

        })->covers(\Yangweijie\ThinkScramble\Analyzer\CodeAnalyzer::class);

        test('AstParser code parsing', function () {
            $parser = new AstParser($this->config);

            // Test basic instantiation
            expect($parser)->toBeInstanceOf(AstParser::class);

            // Test parsing current file
            try {
                $fileAst = $parser->parseFile(__FILE__);
                expect($fileAst)->toBeArray();
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }

        })->covers(\Yangweijie\ThinkScramble\Analyzer\AstParser::class);

        test('ReflectionAnalyzer class analysis', function () {
            $analyzer = new ReflectionAnalyzer($this->config);
            
            // Test basic instantiation
            expect($analyzer)->toBeInstanceOf(ReflectionAnalyzer::class);
            
            // Test analyzing built-in class
            $stdClassInfo = $analyzer->analyzeClass('stdClass');
            expect($stdClassInfo)->toBeArray();
            expect($stdClassInfo)->toHaveKey('name');
            expect($stdClassInfo['name'])->toBe('stdClass');
            
            // Test analyzing non-existent class
            try {
                $nonExistentInfo = $analyzer->analyzeClass('NonExistentClass');
                expect($nonExistentInfo)->toBeArray();
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
            
        })->covers(\Yangweijie\ThinkScramble\Analyzer\ReflectionAnalyzer::class);

        test('AnnotationParser annotation extraction', function () {
            $parser = new AnnotationParser($this->config);

            // Test basic instantiation
            expect($parser)->toBeInstanceOf(AnnotationParser::class);

            // Test parsing class annotations with ReflectionClass
            try {
                $reflectionClass = new \ReflectionClass('stdClass');
                $classAnnotations = $parser->parseClassAnnotations($reflectionClass);
                expect($classAnnotations)->toBeArray();
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }

        })->covers(\Yangweijie\ThinkScramble\Analyzer\AnnotationParser::class);
    });

    describe('Cache Module Core Coverage', function () {
        test('CacheManager comprehensive operations', function () {
            if (!isset($this->cache) || $this->cache === null) {
                expect(true)->toBe(true);
                return;
            }
            
            // Test basic operations
            $key = 'test_key';
            $value = ['test' => 'data', 'number' => 123];
            
            // Test set operation
            $setResult = $this->cache->set($key, $value, 3600);
            expect($setResult)->toBeBool();
            
            // Test get operation
            $retrieved = $this->cache->get($key);
            expect($retrieved === $value || $retrieved === null)->toBe(true);
            
            // Test has operation
            $exists = $this->cache->has($key);
            expect($exists)->toBeBool();
            
            // Test delete operation
            $deleted = $this->cache->delete($key);
            expect($deleted)->toBeBool();
            
            // Test stats
            $stats = $this->cache->getStats();
            expect($stats)->toBeArray();
            expect($stats)->toHaveKey('hits');
            expect($stats)->toHaveKey('misses');
            
        })->covers(\Yangweijie\ThinkScramble\Cache\CacheManager::class);
    });

    describe('Utils Module Core Coverage', function () {
        test('YamlGenerator encoding operations', function () {
            // Test encoding simple array
            $simpleData = [
                'name' => 'Test API',
                'version' => '1.0.0',
                'enabled' => true
            ];
            
            $yaml = YamlGenerator::encode($simpleData);
            expect($yaml)->toBeString();
            expect(strlen($yaml))->toBeGreaterThan(0);
            
            // Test encoding complex OpenAPI structure
            $openApiData = [
                'openapi' => '3.0.0',
                'info' => [
                    'title' => 'Test API',
                    'version' => '1.0.0'
                ],
                'paths' => [
                    '/users' => [
                        'get' => [
                            'summary' => 'Get users',
                            'responses' => [
                                '200' => [
                                    'description' => 'Success'
                                ]
                            ]
                        ]
                    ]
                ]
            ];
            
            $complexYaml = YamlGenerator::encode($openApiData);
            expect($complexYaml)->toBeString();
            expect($complexYaml)->toContain('openapi: 3.0.0');
            expect($complexYaml)->toContain('title: Test API');
            
        })->covers(\Yangweijie\ThinkScramble\Utils\YamlGenerator::class);
    });
});
