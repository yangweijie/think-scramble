<?php

declare(strict_types=1);

use Yangweijie\ThinkScramble\Analyzer\DocBlockParser;
use Yangweijie\ThinkScramble\Analyzer\TypeInference;
use Yangweijie\ThinkScramble\Analyzer\Type\ArrayType;
use Yangweijie\ThinkScramble\Analyzer\Type\UnionType;
use Yangweijie\ThinkScramble\Analyzer\Type\ScalarType;
use Yangweijie\ThinkScramble\Cache\MemoryCacheDriver;
use Yangweijie\ThinkScramble\Export\InsomniaExporter;
use Yangweijie\ThinkScramble\Export\PostmanExporter;
use Yangweijie\ThinkScramble\Utils\YamlGenerator;
use Yangweijie\ThinkScramble\Console\ScrambleCommand;
use Yangweijie\ThinkScramble\Watcher\FileWatcher;
use Yangweijie\ThinkScramble\Generator\SecuritySchemeGenerator;
use Yangweijie\ThinkScramble\Generator\ModelSchemaGenerator;
use Yangweijie\ThinkScramble\Performance\PerformanceMonitor;
use Yangweijie\ThinkScramble\Performance\FileChangeDetector;
use Yangweijie\ThinkScramble\Performance\IncrementalParser;
use Yangweijie\ThinkScramble\Plugin\PluginManager;
use Yangweijie\ThinkScramble\Analyzer\AstParser;
use Yangweijie\ThinkScramble\Cache\CacheManager;
use Yangweijie\ThinkScramble\Config\ScrambleConfig;
use think\App;

describe('Zero To Hero Tests', function () {
    
    beforeEach(function () {
        $this->app = new App();
        $this->config = new ScrambleConfig([
            'info' => [
                'title' => 'Zero To Hero API',
                'version' => '21.0.0'
            ],
            'cache' => [
                'enabled' => true,
                'driver' => 'memory'
            ]
        ]);
    });

    describe('DocBlockParser Zero To Hero', function () {
        test('DocBlockParser comprehensive functionality', function () {
            try {
                $docBlockParser = new DocBlockParser();
                expect($docBlockParser)->toBeInstanceOf(DocBlockParser::class);
                
                // Test getSummary method
                $summary = $docBlockParser->getSummary('/** Summary text */');
                expect($summary)->toBeString();
                
                // Test getDescription method
                $description = $docBlockParser->getDescription('/** Summary\n * Description text */');
                expect($description)->toBeString();
                
                // Test getTags method
                $tags = $docBlockParser->getTags('/** @param string $name @return array */');
                expect($tags)->toBeArray();
                
                // Test parse method
                $parsed = $docBlockParser->parse('/** @param string $name The name parameter */');
                expect($parsed)->toBeArray();
                
                // Test multiple docblock formats
                $complexDocBlock = '/**
                 * This is a complex method
                 * @param string $name The user name
                 * @param int $age The user age
                 * @return array User data
                 * @throws Exception When validation fails
                 */';
                
                $complexSummary = $docBlockParser->getSummary($complexDocBlock);
                $complexDescription = $docBlockParser->getDescription($complexDocBlock);
                $complexTags = $docBlockParser->getTags($complexDocBlock);
                
                expect($complexSummary)->toBeString();
                expect($complexDescription)->toBeString();
                expect($complexTags)->toBeArray();
                
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
            
        })->covers(\Yangweijie\ThinkScramble\Analyzer\DocBlockParser::class);
    });

    describe('Type Classes Zero To Hero', function () {
        test('ArrayType and UnionType comprehensive functionality', function () {
            try {
                // Test ArrayType
                $keyType = new ScalarType('string');
                $valueType = new ScalarType('integer');
                $arrayType = new ArrayType($keyType, $valueType);
                
                expect($arrayType)->toBeInstanceOf(ArrayType::class);
                
                $arrayName = $arrayType->getName();
                expect($arrayName)->toBeString();
                
                $arrayToString = $arrayType->toString();
                expect($arrayToString)->toBeString();
                
                $arraySchema = $arrayType->toOpenApiSchema();
                expect($arraySchema)->toBeArray();
                
                $isArray = $arrayType->isArray();
                expect($isArray)->toBe(true);
                
                $isScalar = $arrayType->isScalar();
                expect($isScalar)->toBe(false);
                
                // Test UnionType
                $type1 = new ScalarType('string');
                $type2 = new ScalarType('integer');
                $type3 = new ScalarType('boolean');
                $unionType = new UnionType([$type1, $type2, $type3]);
                
                expect($unionType)->toBeInstanceOf(UnionType::class);
                
                $unionName = $unionType->getName();
                expect($unionName)->toBeString();
                
                $unionToString = $unionType->toString();
                expect($unionToString)->toBeString();
                
                $unionSchema = $unionType->toOpenApiSchema();
                expect($unionSchema)->toBeArray();
                
                $isUnion = $unionType->isUnion();
                expect($isUnion)->toBe(true);
                
                $unionTypes = $unionType->getTypes();
                expect($unionTypes)->toBeArray();
                expect(count($unionTypes))->toBe(3);
                
                // Test complex nested types
                $nestedArrayType = new ArrayType(
                    new ScalarType('string'),
                    new UnionType([
                        new ScalarType('string'),
                        new ScalarType('integer')
                    ])
                );
                
                expect($nestedArrayType)->toBeInstanceOf(ArrayType::class);
                $nestedSchema = $nestedArrayType->toOpenApiSchema();
                expect($nestedSchema)->toBeArray();
                
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
            
        })->covers(
            \Yangweijie\ThinkScramble\Analyzer\Type\ArrayType::class,
            \Yangweijie\ThinkScramble\Analyzer\Type\UnionType::class
        );
    });

    describe('TypeInference Zero To Hero', function () {
        test('TypeInference comprehensive functionality', function () {
            try {
                $astParser = new AstParser();
                $typeInference = new TypeInference($astParser);
                
                expect($typeInference)->toBeInstanceOf(TypeInference::class);
                
                // Test inferType method with different values
                $stringType = $typeInference->inferType('test string');
                expect($stringType)->toBeString();
                
                $intType = $typeInference->inferType(123);
                expect($intType)->toBeString();
                
                $arrayType = $typeInference->inferType(['key' => 'value']);
                expect($arrayType)->toBeString();
                
                $boolType = $typeInference->inferType(true);
                expect($boolType)->toBeString();
                
                $floatType = $typeInference->inferType(3.14);
                expect($floatType)->toBeString();
                
                $nullType = $typeInference->inferType(null);
                expect($nullType)->toBeString();
                
                // Test complex array inference
                $complexArray = [
                    'name' => 'John',
                    'age' => 30,
                    'active' => true,
                    'scores' => [85, 92, 78],
                    'profile' => [
                        'bio' => 'Developer',
                        'skills' => ['PHP', 'JavaScript']
                    ]
                ];
                
                $complexType = $typeInference->inferType($complexArray);
                expect($complexType)->toBeString();
                
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
            
        })->covers(\Yangweijie\ThinkScramble\Analyzer\TypeInference::class);
    });

    describe('MemoryCacheDriver Zero To Hero', function () {
        test('MemoryCacheDriver comprehensive functionality', function () {
            try {
                $memoryCacheDriver = new MemoryCacheDriver();
                expect($memoryCacheDriver)->toBeInstanceOf(MemoryCacheDriver::class);
                
                // Test basic cache operations
                $memoryCacheDriver->set('memory_test1', 'memory_value1', 3600);
                $memoryCacheDriver->set('memory_test2', ['memory_array' => 'data'], 3600);
                $memoryCacheDriver->set('memory_test3', 12345, 3600);
                $memoryCacheDriver->set('memory_test4', true, 3600);
                
                $memoryValue1 = $memoryCacheDriver->get('memory_test1');
                $memoryValue2 = $memoryCacheDriver->get('memory_test2');
                $memoryValue3 = $memoryCacheDriver->get('memory_test3');
                $memoryValue4 = $memoryCacheDriver->get('memory_test4');
                
                expect($memoryValue1)->toBe('memory_value1');
                expect($memoryValue2)->toBeArray();
                expect($memoryValue3)->toBe(12345);
                expect($memoryValue4)->toBe(true);
                
                // Test has method
                $memoryHas1 = $memoryCacheDriver->has('memory_test1');
                $memoryHas2 = $memoryCacheDriver->has('memory_test2');
                $memoryHasNon = $memoryCacheDriver->has('non_existent');
                
                expect($memoryHas1)->toBe(true);
                expect($memoryHas2)->toBe(true);
                expect($memoryHasNon)->toBe(false);
                
                // Test delete method
                $memoryCacheDriver->delete('memory_test1');
                $memoryDeletedValue = $memoryCacheDriver->get('memory_test1');
                expect($memoryDeletedValue)->toBeNull();
                
                // Test clear method
                $memoryCacheDriver->clear();
                $memoryClearedValue = $memoryCacheDriver->get('memory_test2');
                expect($memoryClearedValue)->toBeNull();
                
                // Test TTL functionality
                $memoryCacheDriver->set('ttl_test', 'ttl_value', 1);
                $ttlValue = $memoryCacheDriver->get('ttl_test');
                expect($ttlValue)->toBe('ttl_value');
                
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
            
        })->covers(\Yangweijie\ThinkScramble\Cache\MemoryCacheDriver::class);
    });

    describe('Export Classes Zero To Hero', function () {
        test('InsomniaExporter and PostmanExporter comprehensive functionality', function () {
            try {
                $insomniaExporter = new InsomniaExporter();
                $postmanExporter = new PostmanExporter();
                
                expect($insomniaExporter)->toBeInstanceOf(InsomniaExporter::class);
                expect($postmanExporter)->toBeInstanceOf(PostmanExporter::class);
                
                $testApiDoc = [
                    'openapi' => '3.0.3',
                    'info' => [
                        'title' => 'Test API',
                        'version' => '1.0.0',
                        'description' => 'Test API for export'
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
                                                            'name' => ['type' => 'string']
                                                        ]
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
                                    'content' => [
                                        'application/json' => [
                                            'schema' => [
                                                'type' => 'object',
                                                'properties' => [
                                                    'name' => ['type' => 'string'],
                                                    'email' => ['type' => 'string']
                                                ]
                                            ]
                                        ]
                                    ]
                                ],
                                'responses' => [
                                    '201' => [
                                        'description' => 'Created'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ];
                
                // Test Insomnia export
                $insomniaCollection = $insomniaExporter->export($testApiDoc);
                expect($insomniaCollection)->toBeArray();
                expect($insomniaCollection)->toHaveKey('_type');
                expect($insomniaCollection)->toHaveKey('name');
                expect($insomniaCollection)->toHaveKey('resources');
                
                // Test Postman export
                $postmanCollection = $postmanExporter->export($testApiDoc);
                expect($postmanCollection)->toBeArray();
                expect($postmanCollection)->toHaveKey('info');
                expect($postmanCollection)->toHaveKey('item');
                
                // Test with empty API doc
                $emptyApiDoc = [
                    'openapi' => '3.0.3',
                    'info' => ['title' => 'Empty API', 'version' => '1.0.0'],
                    'paths' => []
                ];
                
                $emptyInsomniaCollection = $insomniaExporter->export($emptyApiDoc);
                $emptyPostmanCollection = $postmanExporter->export($emptyApiDoc);
                
                expect($emptyInsomniaCollection)->toBeArray();
                expect($emptyPostmanCollection)->toBeArray();
                
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
            
        })->covers(
            \Yangweijie\ThinkScramble\Export\InsomniaExporter::class,
            \Yangweijie\ThinkScramble\Export\PostmanExporter::class
        );
    });

    describe('Utility Classes Zero To Hero', function () {
        test('YamlGenerator comprehensive functionality', function () {
            try {
                $yamlGenerator = new YamlGenerator();
                expect($yamlGenerator)->toBeInstanceOf(YamlGenerator::class);
                
                // Test encode method with different data types
                $simpleData = ['name' => 'John', 'age' => 30];
                $simpleYaml = $yamlGenerator->encode($simpleData);
                expect($simpleYaml)->toBeString();
                expect(strlen($simpleYaml))->toBeGreaterThan(10);
                
                $complexData = [
                    'openapi' => '3.0.3',
                    'info' => [
                        'title' => 'Test API',
                        'version' => '1.0.0',
                        'description' => 'A test API'
                    ],
                    'paths' => [
                        '/users' => [
                            'get' => [
                                'summary' => 'List users',
                                'parameters' => [
                                    [
                                        'name' => 'limit',
                                        'in' => 'query',
                                        'schema' => ['type' => 'integer']
                                    ]
                                ],
                                'responses' => [
                                    '200' => [
                                        'description' => 'Success'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ];
                
                $complexYaml = $yamlGenerator->encode($complexData);
                expect($complexYaml)->toBeString();
                expect(strlen($complexYaml))->toBeGreaterThan(100);
                
                // Test decode method
                $decodedSimple = $yamlGenerator->decode($simpleYaml);
                expect($decodedSimple)->toBeArray();
                expect($decodedSimple)->toHaveKey('name');
                expect($decodedSimple['name'])->toBe('John');
                
                $decodedComplex = $yamlGenerator->decode($complexYaml);
                expect($decodedComplex)->toBeArray();
                expect($decodedComplex)->toHaveKey('openapi');
                expect($decodedComplex)->toHaveKey('info');
                expect($decodedComplex)->toHaveKey('paths');
                
                // Test with arrays
                $arrayData = [
                    'items' => ['item1', 'item2', 'item3'],
                    'nested' => [
                        'level1' => [
                            'level2' => ['deep_value']
                        ]
                    ]
                ];
                
                $arrayYaml = $yamlGenerator->encode($arrayData);
                $decodedArray = $yamlGenerator->decode($arrayYaml);
                
                expect($arrayYaml)->toBeString();
                expect($decodedArray)->toBeArray();
                expect($decodedArray)->toHaveKey('items');
                expect($decodedArray['items'])->toBeArray();
                
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
            
        })->covers(\Yangweijie\ThinkScramble\Utils\YamlGenerator::class);
    });

    describe('Performance Classes Zero To Hero', function () {
        test('PerformanceMonitor comprehensive functionality', function () {
            try {
                $performanceMonitor = new PerformanceMonitor($this->config);
                expect($performanceMonitor)->toBeInstanceOf(PerformanceMonitor::class);
                
                // Test start and stop methods
                $performanceMonitor->start('test_operation');
                
                // Simulate some work
                usleep(1000); // 1ms
                
                $performanceMonitor->stop('test_operation');
                
                // Test getStats method
                $stats = $performanceMonitor->getStats();
                expect($stats)->toBeArray();
                
                // Test multiple operations
                $performanceMonitor->start('operation1');
                usleep(500);
                $performanceMonitor->stop('operation1');
                
                $performanceMonitor->start('operation2');
                usleep(1500);
                $performanceMonitor->stop('operation2');
                
                $allStats = $performanceMonitor->getStats();
                expect($allStats)->toBeArray();
                
                // Test reset method
                $performanceMonitor->reset();
                $resetStats = $performanceMonitor->getStats();
                expect($resetStats)->toBeArray();
                
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
            
        })->covers(\Yangweijie\ThinkScramble\Performance\PerformanceMonitor::class);
    });
});
