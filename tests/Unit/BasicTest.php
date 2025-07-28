<?php

declare(strict_types=1);

use Yangweijie\ThinkScramble\Config\ScrambleConfig;
use Yangweijie\ThinkScramble\Generator\DocumentBuilder;
use Yangweijie\ThinkScramble\Generator\SchemaGenerator;
use Yangweijie\ThinkScramble\Generator\ParameterExtractor;
use Yangweijie\ThinkScramble\Generator\ModelSchemaGenerator;
use Yangweijie\ThinkScramble\Generator\SecuritySchemeGenerator;

describe('Basic Functionality Tests', function () {
    test('ScrambleConfig can be instantiated', function () {
        $config = new ScrambleConfig();

        expect($config)->toBeInstanceOf(ScrambleConfig::class);
    })->covers(\Yangweijie\ThinkScramble\Config\ScrambleConfig::class);

    test('ScrambleConfig can get default values', function () {
        $config = new ScrambleConfig();

        // These calls will actually execute the get() method
        $apiPath = $config->get('api_path');
        $title = $config->get('info.title');
        $version = $config->get('info.version');

        expect($apiPath)->toBe('api');
        expect($title)->toBe('API Documentation');
        expect($version)->toBe('1.0.0');
    })->covers(\Yangweijie\ThinkScramble\Config\ScrambleConfig::class);

    test('ScrambleConfig can set and get values', function () {
        $config = new ScrambleConfig();

        // Actually call the set method
        $config->set('test_key', 'test_value');
        $config->set('nested.key', 'nested_value');

        // Actually call the get method
        $value1 = $config->get('test_key');
        $value2 = $config->get('nested.key');
        $defaultValue = $config->get('non_existent', 'default');

        expect($value1)->toBe('test_value');
        expect($value2)->toBe('nested_value');
        expect($defaultValue)->toBe('default');
    })->covers(\Yangweijie\ThinkScramble\Config\ScrambleConfig::class);

    test('ScrambleConfig can check if key exists', function () {
        $config = new ScrambleConfig(['existing_key' => 'value']);

        // Actually call the has method multiple times
        $hasExisting = $config->has('existing_key');
        $hasNonExisting = $config->has('non_existing_key');
        $hasNested = $config->has('info.title'); // default key

        expect($hasExisting)->toBeTrue();
        expect($hasNonExisting)->toBeFalse();
        expect($hasNested)->toBeTrue();
    })->covers(\Yangweijie\ThinkScramble\Config\ScrambleConfig::class);

    test('ScrambleConfig can return all configuration', function () {
        $config = new ScrambleConfig(['test' => 'value']);

        // Actually call the all method
        $all = $config->all();

        expect($all)->toBeArray();
        expect($all)->toHaveKey('test');
        expect($all)->toHaveKey('api_path'); // default key
        expect($all['test'])->toBe('value');
    })->covers(\Yangweijie\ThinkScramble\Config\ScrambleConfig::class);

    test('ScrambleConfig can convert to array', function () {
        $config = new ScrambleConfig(['test' => 'value']);

        // Actually call the toArray method
        $array = $config->toArray();

        expect($array)->toBeArray();
        expect($array)->toHaveKey('test');
        expect($array['test'])->toBe('value');
    })->covers(\Yangweijie\ThinkScramble\Config\ScrambleConfig::class);

    test('ScrambleConfig can validate configuration', function () {
        $config = new ScrambleConfig();

        // Actually call the validate method
        $isValid = $config->validate();

        expect($isValid)->toBeTrue();

        // Test with custom data
        $config->set('custom_key', 'custom_value');
        $isStillValid = $config->validate();
        expect($isStillValid)->toBeTrue();
    })->covers(\Yangweijie\ThinkScramble\Config\ScrambleConfig::class);

    test('ScrambleConfig can merge configuration', function () {
        $config = new ScrambleConfig(['key1' => 'value1']);

        // Actually call the merge method
        $config->merge(['key2' => 'value2']);
        $config->merge(['key3' => 'value3', 'nested' => ['deep' => 'value']]);

        // Test the merged values
        expect($config->get('key1'))->toBe('value1');
        expect($config->get('key2'))->toBe('value2');
        expect($config->get('key3'))->toBe('value3');
        expect($config->get('nested.deep'))->toBe('value');
    })->covers(\Yangweijie\ThinkScramble\Config\ScrambleConfig::class);

    test('ScrambleConfig can create from make method', function () {
        // Actually call the static make method
        $config = ScrambleConfig::make(['test' => 'value']);

        expect($config)->toBeInstanceOf(ScrambleConfig::class);

        // Test that the data was properly set
        $testValue = $config->get('test');
        $defaultValue = $config->get('api_path'); // should have defaults too

        expect($testValue)->toBe('value');
        expect($defaultValue)->toBe('api');
    })->covers(\Yangweijie\ThinkScramble\Config\ScrambleConfig::class);

    test('DocumentBuilder can be instantiated', function () {
        $config = new ScrambleConfig();
        $schemaGenerator = $this->createMock(SchemaGenerator::class);
        $parameterExtractor = $this->createMock(ParameterExtractor::class);
        $modelSchemaGenerator = $this->createMock(ModelSchemaGenerator::class);
        $securitySchemeGenerator = $this->createMock(SecuritySchemeGenerator::class);

        $builder = new DocumentBuilder(
            $config,
            $schemaGenerator,
            $parameterExtractor,
            $modelSchemaGenerator,
            $securitySchemeGenerator
        );

        expect($builder)->toBeInstanceOf(DocumentBuilder::class);
    })->covers(\Yangweijie\ThinkScramble\Generator\DocumentBuilder::class);

    test('DocumentBuilder can get document', function () {
        $config = new ScrambleConfig();
        $schemaGenerator = $this->createMock(SchemaGenerator::class);
        $parameterExtractor = $this->createMock(ParameterExtractor::class);
        $modelSchemaGenerator = $this->createMock(ModelSchemaGenerator::class);
        $securitySchemeGenerator = $this->createMock(SecuritySchemeGenerator::class);

        $builder = new DocumentBuilder(
            $config,
            $schemaGenerator,
            $parameterExtractor,
            $modelSchemaGenerator,
            $securitySchemeGenerator
        );

        $document = $builder->getDocument();
        expect($document)->toBeArray();
    })->covers(\Yangweijie\ThinkScramble\Generator\DocumentBuilder::class);

    test('DocumentBuilder can add path with method', function () {
        $config = new ScrambleConfig();
        $schemaGenerator = $this->createMock(SchemaGenerator::class);
        $parameterExtractor = $this->createMock(ParameterExtractor::class);
        $modelSchemaGenerator = $this->createMock(ModelSchemaGenerator::class);
        $securitySchemeGenerator = $this->createMock(SecuritySchemeGenerator::class);

        $builder = new DocumentBuilder(
            $config,
            $schemaGenerator,
            $parameterExtractor,
            $modelSchemaGenerator,
            $securitySchemeGenerator
        );

        $operation = [
            'summary' => 'Test operation',
            'responses' => [
                '200' => ['description' => 'Success']
            ]
        ];

        $builder->addPath('/test', 'get', $operation);
        $document = $builder->getDocument();

        expect($document['paths'])->toHaveKey('/test');
        expect($document['paths']['/test'])->toHaveKey('get');
    })->covers(\Yangweijie\ThinkScramble\Generator\DocumentBuilder::class);

    test('DocumentBuilder can get document as array', function () {
        $config = new ScrambleConfig();
        $schemaGenerator = $this->createMock(SchemaGenerator::class);
        $parameterExtractor = $this->createMock(ParameterExtractor::class);
        $modelSchemaGenerator = $this->createMock(ModelSchemaGenerator::class);
        $securitySchemeGenerator = $this->createMock(SecuritySchemeGenerator::class);

        $builder = new DocumentBuilder(
            $config,
            $schemaGenerator,
            $parameterExtractor,
            $modelSchemaGenerator,
            $securitySchemeGenerator
        );

        $document = $builder->getDocument();
        expect($document)->toBeArray();
    })->covers(\Yangweijie\ThinkScramble\Generator\DocumentBuilder::class);

    test('basic string operations work', function () {
        $str = 'test_string';
        expect($str)->toBeString();
        expect(strlen($str))->toBe(11);
        expect(strpos($str, 'test'))->toBe(0);
    })->coversNothing();

    test('basic array operations work', function () {
        $arr = ['key1' => 'value1', 'key2' => 'value2'];
        expect($arr)->toBeArray();
        expect($arr)->toHaveCount(2);
        expect($arr)->toHaveKey('key1');
        expect($arr['key1'])->toBe('value1');
    })->coversNothing();

    test('memory usage is reasonable', function () {
        $startMemory = memory_get_usage();

        // Create multiple objects
        for ($i = 0; $i < 10; $i++) {
            $config = new ScrambleConfig(['test' => "value_{$i}"]);
            $config->set('dynamic', "dynamic_{$i}");
        }

        $endMemory = memory_get_usage();
        $memoryUsed = $endMemory - $startMemory;

        // Should use less than 100KB for 10 config objects (adjusted for realistic usage)
        expect($memoryUsed)->toBeLessThan(100 * 1024);
    })->covers(\Yangweijie\ThinkScramble\Config\ScrambleConfig::class);

    test('performance is acceptable', function () {
        $startTime = microtime(true);

        // Perform many operations
        for ($i = 0; $i < 100; $i++) {
            $config = new ScrambleConfig();
            $config->set("key_{$i}", "value_{$i}");
            $config->get("key_{$i}");
            $config->has("key_{$i}");
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        // Should complete in less than 0.1 seconds
        expect($duration)->toBeLessThan(0.1);
    })->covers(\Yangweijie\ThinkScramble\Config\ScrambleConfig::class);
});
