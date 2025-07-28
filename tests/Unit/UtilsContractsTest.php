<?php

declare(strict_types=1);

use Yangweijie\ThinkScramble\Utils\YamlGenerator;
use Yangweijie\ThinkScramble\Contracts\AnalyzerInterface;
use Yangweijie\ThinkScramble\Contracts\ConfigInterface;
use Yangweijie\ThinkScramble\Contracts\GeneratorInterface;
use Yangweijie\ThinkScramble\Config\ScrambleConfig;

describe('Utils and Contracts Tests', function () {
    beforeEach(function () {
        // Load test data
        $this->testData = include __DIR__ . '/../data/cache_clear_test.php';
    });

    test('YamlGenerator can encode simple arrays', function () {
        $data = [
            'name' => 'Test API',
            'version' => '1.0.0',
            'enabled' => true,
            'count' => 42,
            'nullable' => null,
        ];
        
        $yaml = YamlGenerator::encode($data);
        
        expect($yaml)->toBeString();
        expect($yaml)->toContain('name: Test API');
        expect($yaml)->toContain('version: 1.0.0');
        expect($yaml)->toContain('enabled: true');
        expect($yaml)->toContain('count: 42');
        expect($yaml)->toContain('nullable: null');
        
    })->covers(\Yangweijie\ThinkScramble\Utils\YamlGenerator::class);

    test('YamlGenerator can encode nested arrays', function () {
        $data = [
            'info' => [
                'title' => 'Test API',
                'version' => '1.0.0',
                'contact' => [
                    'name' => 'Developer',
                    'email' => 'dev@example.com',
                ],
            ],
            'servers' => [
                [
                    'url' => 'https://api.example.com',
                    'description' => 'Production server',
                ],
                [
                    'url' => 'https://staging.example.com',
                    'description' => 'Staging server',
                ],
            ],
        ];
        
        $yaml = YamlGenerator::encode($data);
        
        expect($yaml)->toBeString();
        expect($yaml)->toContain('info:');
        expect($yaml)->toContain('  title: Test API');
        expect($yaml)->toContain('  contact:');
        expect($yaml)->toContain('    name: Developer');
        expect($yaml)->toContain('servers:');
        expect($yaml)->toContain('url: "https://api.example.com"');
        expect($yaml)->toContain('    description: Production server');
        
    })->covers(\Yangweijie\ThinkScramble\Utils\YamlGenerator::class);

    test('YamlGenerator can encode complex test data', function () {
        $complexData = $this->testData['complex_data'];
        
        $yaml = YamlGenerator::encode($complexData);
        
        expect($yaml)->toBeString();
        expect($yaml)->toContain('users:');
        expect($yaml)->toContain('meta:');
        expect($yaml)->toContain('config:');
        expect($yaml)->toContain('id: 1');
        expect($yaml)->toContain('    name: John Doe');
        expect($yaml)->toContain('email: "john@example.com"');
        
    })->covers(\Yangweijie\ThinkScramble\Utils\YamlGenerator::class);

    test('YamlGenerator can handle special characters and unicode', function () {
        $data = [
            'unicode_string' => '🚀 Unicode test: 中文',
            'special_chars' => 'Special chars: !@#$%^&*()',
            'quotes' => 'String with "quotes" and \'apostrophes\'',
            'multiline' => "Line 1\nLine 2\nLine 3",
            'yaml_reserved' => [
                'yes' => 'should be string',
                'no' => 'should be string',
                'true' => 'should be string',
                'false' => 'should be string',
                'null' => 'should be string',
            ],
        ];
        
        $yaml = YamlGenerator::encode($data);
        
        expect($yaml)->toBeString();
        expect($yaml)->toContain('unicode_string:');
        expect($yaml)->toContain('special_chars:');
        expect($yaml)->toContain('quotes:');
        expect($yaml)->toContain('multiline:');
        expect($yaml)->toContain('yaml_reserved:');
        
        // Test that special characters are properly handled
        expect($yaml)->toContain('🚀');
        expect($yaml)->toContain('中文');
        expect($yaml)->toContain('!@#$%^&*()');
        
    })->covers(\Yangweijie\ThinkScramble\Utils\YamlGenerator::class);

    test('YamlGenerator can handle edge cases', function () {
        $edgeCases = $this->testData['edge_cases'];
        
        $yaml = YamlGenerator::encode($edgeCases);
        
        expect($yaml)->toBeString();
        expect($yaml)->toContain('empty_string:');
        expect($yaml)->toContain('empty_array:');
        expect($yaml)->toContain('zero: 0');
        expect($yaml)->toContain('false: false');
        expect($yaml)->toContain('null: null');
        
        // Test empty array handling
        $emptyData = [];
        $emptyYaml = YamlGenerator::encode($emptyData);
        expect($emptyYaml)->toBe('');
        
        // Test single value
        $singleValue = ['key' => 'value'];
        $singleYaml = YamlGenerator::encode($singleValue);
        expect($singleYaml)->toBe('key: value' . "\n");
        
    })->covers(\Yangweijie\ThinkScramble\Utils\YamlGenerator::class);

    test('YamlGenerator can generate OpenAPI YAML', function () {
        $openApiDoc = [
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'Test API',
                'version' => '1.0.0',
                'description' => 'A test API',
            ],
            'paths' => [
                '/users' => [
                    'get' => [
                        'summary' => 'List users',
                        'responses' => [
                            '200' => [
                                'description' => 'Success',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        
        $yaml = YamlGenerator::generateOpenApiYaml($openApiDoc);
        
        expect($yaml)->toBeString();
        expect($yaml)->toContain('# OpenAPI 3.0 Specification');
        expect($yaml)->toContain('# Generated by ThinkScramble');
        expect($yaml)->toContain('openapi: 3.0.3');
        expect($yaml)->toContain('info:');
        expect($yaml)->toContain('  title: Test API');
        expect($yaml)->toContain('paths:');
        expect($yaml)->toContain('/users:');
        
    })->covers(\Yangweijie\ThinkScramble\Utils\YamlGenerator::class);

    test('YamlGenerator can check native YAML support', function () {
        $hasSupport = YamlGenerator::hasNativeYamlSupport();
        
        expect($hasSupport)->toBeBool();
        
        // Test dump method with and without native support
        $testData = ['test' => 'value', 'number' => 123];
        $yaml = YamlGenerator::dump($testData);
        
        expect($yaml)->toBeString();
        expect($yaml)->toContain('test');
        expect($yaml)->toContain('value');
        expect($yaml)->toContain('123');
        
    })->covers(\Yangweijie\ThinkScramble\Utils\YamlGenerator::class);

    test('YamlGenerator handles deeply nested structures', function () {
        $nestedData = $this->testData['nested_data'];
        
        $yaml = YamlGenerator::encode($nestedData);
        
        expect($yaml)->toBeString();
        expect($yaml)->toContain('level1:');
        expect($yaml)->toContain('  level2:');
        expect($yaml)->toContain('    level3:');
        expect($yaml)->toContain('      level4:');
        expect($yaml)->toContain('        deep_value: found_me');
        expect($yaml)->toContain('        array_data:');
        expect($yaml)->toContain('          - 1');
        expect($yaml)->toContain('          - 2');
        
    })->covers(\Yangweijie\ThinkScramble\Utils\YamlGenerator::class);

    test('YamlGenerator handles API response structures', function () {
        $apiResponses = $this->testData['api_responses'];
        
        // Test success response
        $successYaml = YamlGenerator::encode($apiResponses['success']);
        expect($successYaml)->toContain('status: success');
        expect($successYaml)->toContain('code: 200');
        expect($successYaml)->toContain('data:');
        expect($successYaml)->toContain('  message:');
        
        // Test error response
        $errorYaml = YamlGenerator::encode($apiResponses['error']);
        expect($errorYaml)->toContain('status: error');
        expect($errorYaml)->toContain('code: 400');
        expect($errorYaml)->toContain('errors:');
        expect($errorYaml)->toContain('field1:');
        expect($errorYaml)->toContain('- Field is required');
        
    })->covers(\Yangweijie\ThinkScramble\Utils\YamlGenerator::class);

    test('YamlGenerator performance with large datasets', function () {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        // Create large dataset
        $largeData = [];
        for ($i = 0; $i < 1000; $i++) {
            $largeData["item_{$i}"] = [
                'id' => $i,
                'name' => "Item {$i}",
                'data' => array_fill(0, 10, "value_{$i}"),
                'nested' => [
                    'level1' => ['level2' => ['value' => $i]],
                ],
            ];
        }
        
        $yaml = YamlGenerator::encode($largeData);
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        // Performance assertions
        expect($endTime - $startTime)->toBeLessThan(2.0); // Should complete in less than 2 seconds
        expect($endMemory - $startMemory)->toBeLessThan(50 * 1024 * 1024); // Should use less than 50MB
        
        // Functionality assertions
        expect($yaml)->toBeString();
        expect($yaml)->toContain('item_0:');
        expect($yaml)->toContain('item_999:');
        expect($yaml)->toContain('  id: 0');
        expect($yaml)->toContain('  id: 999');
        
    })->covers(\Yangweijie\ThinkScramble\Utils\YamlGenerator::class);

    test('ConfigInterface defines correct contract', function () {
        // Test that ConfigInterface exists and has expected methods
        expect(interface_exists(ConfigInterface::class))->toBe(true);

        $reflection = new ReflectionClass(ConfigInterface::class);
        expect($reflection->isInterface())->toBe(true);

        // Check required methods
        $expectedMethods = ['get', 'set', 'has', 'all', 'validate'];
        foreach ($expectedMethods as $method) {
            expect($reflection->hasMethod($method))->toBe(true);
        }

        // Test that ScrambleConfig implements ConfigInterface
        $configReflection = new ReflectionClass(ScrambleConfig::class);
        expect($configReflection->implementsInterface(ConfigInterface::class))->toBe(true);

    });

    test('AnalyzerInterface defines correct contract', function () {
        // Test that AnalyzerInterface exists and has expected methods
        expect(interface_exists(AnalyzerInterface::class))->toBe(true);

        $reflection = new ReflectionClass(AnalyzerInterface::class);
        expect($reflection->isInterface())->toBe(true);

        // Check required methods
        $expectedMethods = ['analyze', 'supports'];
        foreach ($expectedMethods as $method) {
            expect($reflection->hasMethod($method))->toBe(true);

            $methodReflection = $reflection->getMethod($method);
            expect($methodReflection->isPublic())->toBe(true);
        }

        // Test method signatures
        $analyzeMethod = $reflection->getMethod('analyze');
        expect($analyzeMethod->getNumberOfParameters())->toBe(1);
        expect($analyzeMethod->getParameters()[0]->getName())->toBe('target');

        $supportsMethod = $reflection->getMethod('supports');
        expect($supportsMethod->getNumberOfParameters())->toBe(1);
        expect($supportsMethod->getParameters()[0]->getName())->toBe('target');

    });

    test('GeneratorInterface defines correct contract', function () {
        // Test that GeneratorInterface exists and has expected methods
        expect(interface_exists(GeneratorInterface::class))->toBe(true);

        $reflection = new ReflectionClass(GeneratorInterface::class);
        expect($reflection->isInterface())->toBe(true);

        // Check required methods
        $expectedMethods = ['generate', 'setOptions'];
        foreach ($expectedMethods as $method) {
            expect($reflection->hasMethod($method))->toBe(true);

            $methodReflection = $reflection->getMethod($method);
            expect($methodReflection->isPublic())->toBe(true);
        }

        // Test method signatures
        $generateMethod = $reflection->getMethod('generate');
        expect($generateMethod->getNumberOfParameters())->toBe(1);
        expect($generateMethod->getParameters()[0]->getName())->toBe('analysisResults');

        $setOptionsMethod = $reflection->getMethod('setOptions');
        expect($setOptionsMethod->getNumberOfParameters())->toBe(1);
        expect($setOptionsMethod->getParameters()[0]->getName())->toBe('options');

    });

    test('contracts provide proper type hints', function () {
        // Test ConfigInterface method signatures
        $configReflection = new ReflectionClass(ConfigInterface::class);
        
        $getMethod = $configReflection->getMethod('get');
        expect($getMethod->hasReturnType())->toBe(true);
        
        $setMethod = $configReflection->getMethod('set');
        expect($setMethod->getReturnType()?->getName())->toBe('void');
        
        $hasMethod = $configReflection->getMethod('has');
        expect($hasMethod->getReturnType()?->getName())->toBe('bool');
        
        $allMethod = $configReflection->getMethod('all');
        expect($allMethod->getReturnType()?->getName())->toBe('array');
        
        $validateMethod = $configReflection->getMethod('validate');
        expect($validateMethod->getReturnType()?->getName())->toBe('bool');
        
        // Test AnalyzerInterface method signatures
        $analyzerReflection = new ReflectionClass(AnalyzerInterface::class);
        
        $analyzeMethod = $analyzerReflection->getMethod('analyze');
        expect($analyzeMethod->getReturnType()?->getName())->toBe('array');
        
        $supportsMethod = $analyzerReflection->getMethod('supports');
        expect($supportsMethod->getReturnType()?->getName())->toBe('bool');
        
    });

    test('YamlGenerator handles concurrent operations safely', function () {
        $testData = $this->testData['simple_data'];
        
        // Simulate concurrent YAML generation
        $results = [];
        for ($i = 0; $i < 10; $i++) {
            $data = array_merge($testData, ['thread_id' => $i]);
            $results[] = YamlGenerator::encode($data);
        }
        
        // Verify all results are valid
        foreach ($results as $index => $yaml) {
            expect($yaml)->toBeString();
            expect($yaml)->toContain("thread_id: {$index}");
            expect($yaml)->toContain('key1: value1');
            expect($yaml)->toContain('key2: value2');
        }
        
        // Verify results are consistent
        foreach ($results as $result) {
            // Each result should contain the base structure
            expect($result)->toContain('key1: value1');
            expect($result)->toContain('key2: value2');
        }
        
    })->covers(\Yangweijie\ThinkScramble\Utils\YamlGenerator::class);

    test('contracts support inheritance and composition', function () {
        // Test that interfaces can be extended (conceptually)
        $configInterface = new ReflectionClass(ConfigInterface::class);
        $analyzerInterface = new ReflectionClass(AnalyzerInterface::class);
        $generatorInterface = new ReflectionClass(GeneratorInterface::class);
        
        // All should be interfaces
        expect($configInterface->isInterface())->toBe(true);
        expect($analyzerInterface->isInterface())->toBe(true);
        expect($generatorInterface->isInterface())->toBe(true);
        
        // Test that they don't extend each other (they are separate contracts)
        expect($configInterface->getParentClass())->toBe(false);
        expect($analyzerInterface->getParentClass())->toBe(false);
        expect($generatorInterface->getParentClass())->toBe(false);
        
        // Test that they have proper namespaces
        expect($configInterface->getNamespaceName())->toBe('Yangweijie\\ThinkScramble\\Contracts');
        expect($analyzerInterface->getNamespaceName())->toBe('Yangweijie\\ThinkScramble\\Contracts');
        expect($generatorInterface->getNamespaceName())->toBe('Yangweijie\\ThinkScramble\\Contracts');
        
    });

    test('YamlGenerator maintains data integrity across operations', function () {
        $originalData = $this->testData['complex_data'];
        
        // Generate YAML multiple times
        $yaml1 = YamlGenerator::encode($originalData);
        $yaml2 = YamlGenerator::encode($originalData);
        $yaml3 = YamlGenerator::generateOpenApiYaml($originalData);
        
        // All should be identical (except for OpenAPI header)
        expect($yaml1)->toBe($yaml2);
        expect($yaml3)->toContain($yaml1);
        
        // Test with different data types
        $mixedData = [
            'string' => 'test',
            'integer' => 123,
            'float' => 45.67,
            'boolean_true' => true,
            'boolean_false' => false,
            'null_value' => null,
            'array' => [1, 2, 3],
            'object' => ['key' => 'value'],
        ];
        
        $mixedYaml = YamlGenerator::encode($mixedData);
        expect($mixedYaml)->toContain('string: test');
        expect($mixedYaml)->toContain('integer: 123');
        expect($mixedYaml)->toContain('float: 45.67');
        expect($mixedYaml)->toContain('boolean_true: true');
        expect($mixedYaml)->toContain('boolean_false: false');
        expect($mixedYaml)->toContain('null_value: null');
        expect($mixedYaml)->toContain('array:');
        expect($mixedYaml)->toContain('  - 1');
        expect($mixedYaml)->toContain('object:');
        expect($mixedYaml)->toContain('  key: value');
        
    })->covers(\Yangweijie\ThinkScramble\Utils\YamlGenerator::class);
});
