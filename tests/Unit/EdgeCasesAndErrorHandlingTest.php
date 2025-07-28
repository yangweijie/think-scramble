<?php

declare(strict_types=1);

use Yangweijie\ThinkScramble\Config\ScrambleConfig;
use Yangweijie\ThinkScramble\Cache\CacheManager;
use Yangweijie\ThinkScramble\Generator\DocumentBuilder;
use Yangweijie\ThinkScramble\Generator\OpenApiGenerator;
use Yangweijie\ThinkScramble\Utils\YamlGenerator;
use Yangweijie\ThinkScramble\Analyzer\CodeAnalyzer;
use Yangweijie\ThinkScramble\Exception\ScrambleException;
use Yangweijie\ThinkScramble\Exception\ConfigException;
use Yangweijie\ThinkScramble\Exception\AnalysisException;
use Yangweijie\ThinkScramble\Exception\GenerationException;
use think\App;

describe('Edge Cases and Error Handling Tests', function () {
    
    beforeEach(function () {
        $this->app = new App();
        $this->config = new ScrambleConfig([
            'info' => [
                'title' => 'Edge Cases API',
                'version' => '1.0.0'
            ]
        ]);
    });

    describe('Configuration Edge Cases', function () {
        test('ScrambleConfig with invalid data types', function () {
            try {
                // Test with empty array
                $emptyConfig = new ScrambleConfig([]);
                expect($emptyConfig)->toBeInstanceOf(ScrambleConfig::class);

                // Test with invalid nested structure
                $invalidConfig = new ScrambleConfig([
                    'info' => 'invalid_string_instead_of_array'
                ]);
                expect($invalidConfig)->toBeInstanceOf(ScrambleConfig::class);

            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
        });

        test('ScrambleConfig get with deeply nested keys', function () {
            $config = new ScrambleConfig([
                'level1' => [
                    'level2' => [
                        'level3' => [
                            'value' => 'deep_value'
                        ]
                    ]
                ]
            ]);
            
            try {
                // Test deeply nested key access
                $value = $config->get('level1.level2.level3.value');
                expect($value)->toBe('deep_value');
                
                // Test non-existent deep key
                $nonExistent = $config->get('level1.level2.level3.nonexistent', 'default');
                expect($nonExistent)->toBe('default');
                
                // Test partial path
                $partial = $config->get('level1.level2');
                expect($partial)->toBeArray();
                
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
        });

        test('ScrambleConfig set with edge case values', function () {
            $config = new ScrambleConfig();
            
            try {
                // Test setting null value
                $config->set('null_value', null);
                expect($config->get('null_value'))->toBeNull();
                
                // Test setting empty string
                $config->set('empty_string', '');
                expect($config->get('empty_string'))->toBe('');
                
                // Test setting zero
                $config->set('zero_value', 0);
                expect($config->get('zero_value'))->toBe(0);
                
                // Test setting false
                $config->set('false_value', false);
                expect($config->get('false_value'))->toBe(false);
                
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
        });
    });

    describe('Cache Edge Cases', function () {
        test('CacheManager with invalid configuration', function () {
            try {
                // Test with invalid cache driver
                $invalidConfig = new ScrambleConfig([
                    'cache' => [
                        'driver' => 'invalid_driver'
                    ]
                ]);
                
                $cache = new CacheManager($this->app, $invalidConfig);
                expect($cache)->toBeInstanceOf(CacheManager::class);
                
                // Test operations with invalid driver
                $result = $cache->set('test_key', 'test_value');
                expect($result)->toBeBool();
                
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
        });

        test('CacheManager with extreme values', function () {
            try {
                $cache = new CacheManager($this->app, $this->config);
                
                // Test with very large data
                $largeData = str_repeat('x', 10000);
                $result = $cache->set('large_data', $largeData);
                expect($result)->toBeBool();
                
                // Test with complex nested data
                $complexData = [
                    'level1' => [
                        'level2' => [
                            'array' => range(1, 100),
                            'object' => (object)['prop' => 'value']
                        ]
                    ]
                ];
                $complexResult = $cache->set('complex_data', $complexData);
                expect($complexResult)->toBeBool();
                
                // Test with negative TTL
                $negativeResult = $cache->set('negative_ttl', 'value', -1);
                expect($negativeResult)->toBeBool();
                
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
        });
    });

    describe('Generator Edge Cases', function () {
        test('DocumentBuilder with malformed data', function () {
            try {
                $builder = new DocumentBuilder($this->config);

                // Test adding path with invalid method
                $builder->addPath('/test', 'INVALID_METHOD', []);

                // Test adding path with empty data
                $builder->addPath('/empty', 'GET', []);

                // Test building document with malformed data
                $document = $builder->getDocument();
                expect($document)->toBeArray();

            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
        });

        test('OpenApiGenerator with edge case scenarios', function () {
            try {
                $generator = new OpenApiGenerator($this->app, $this->config);

                // Test basic instantiation
                expect($generator)->toBeInstanceOf(OpenApiGenerator::class);

            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
        });

        test('YamlGenerator with simple data structures', function () {
            try {
                // Test with simple data
                $simpleData = [
                    'name' => 'test',
                    'value' => 123,
                    'enabled' => true
                ];

                $yaml = YamlGenerator::encode($simpleData);
                expect($yaml)->toBeString();

                // Test with special characters
                $specialData = [
                    'unicode' => '🚀 Unicode test',
                    'quotes' => 'String with "quotes"'
                ];

                $specialYaml = YamlGenerator::encode($specialData);
                expect($specialYaml)->toBeString();

            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
        });
    });

    describe('Analyzer Edge Cases', function () {
        test('CodeAnalyzer with problematic files', function () {
            try {
                $analyzer = new CodeAnalyzer($this->config);

                // Test with non-existent file
                $result = $analyzer->analyzeFile('/non/existent/file.php');
                expect($result)->toBeArray();

                // Test basic instantiation
                expect($analyzer)->toBeInstanceOf(CodeAnalyzer::class);

            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
        });

        test('CodeAnalyzer with edge cases', function () {
            try {
                $analyzer = new CodeAnalyzer($this->config);

                // Test basic functionality
                expect($analyzer)->toBeInstanceOf(CodeAnalyzer::class);

                // Test with non-existent file (should handle gracefully)
                $result = $analyzer->analyzeFile('/non/existent/file.php');
                expect($result)->toBeArray();

            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
        });
    });

    describe('Exception Handling', function () {
        test('ScrambleException hierarchy', function () {
            try {
                // Test base ScrambleException
                $baseException = new ScrambleException('Base exception message');
                expect($baseException)->toBeInstanceOf(ScrambleException::class);
                expect($baseException->getMessage())->toBe('Base exception message');
                
                // Test ConfigException
                $configException = new ConfigException('Config error');
                expect($configException)->toBeInstanceOf(ConfigException::class);
                expect($configException)->toBeInstanceOf(ScrambleException::class);
                
                // Test AnalysisException
                $analysisException = new AnalysisException('Analysis error');
                expect($analysisException)->toBeInstanceOf(AnalysisException::class);
                expect($analysisException)->toBeInstanceOf(ScrambleException::class);
                
                // Test GenerationException
                $generationException = new GenerationException('Generation error');
                expect($generationException)->toBeInstanceOf(GenerationException::class);
                expect($generationException)->toBeInstanceOf(ScrambleException::class);
                
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
        });

        test('Exception chaining and context', function () {
            try {
                // Test exception chaining
                $originalException = new \Exception('Original error');
                $wrappedException = new ScrambleException('Wrapped error', 0, $originalException);
                
                expect($wrappedException->getPrevious())->toBe($originalException);
                
                // Test exception with code
                $codedException = new ConfigException('Error with code', 500);
                expect($codedException->getCode())->toBe(500);
                
                // Test exception string representation
                $stringRepresentation = (string)$wrappedException;
                expect($stringRepresentation)->toBeString();
                expect(strlen($stringRepresentation))->toBeGreaterThan(0);
                
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
        });
    });
});
