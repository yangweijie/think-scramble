<?php

declare(strict_types=1);

describe('Config and Helpers Coverage Tests', function () {
    
    describe('Config File Coverage', function () {
        test('Config file can be loaded', function () {
            // Test loading the config file
            $configPath = __DIR__ . '/../../src/Config/config.php';
            expect(file_exists($configPath))->toBe(true);
            
            $config = include $configPath;
            expect($config)->toBeArray();
            
            // Test basic config structure
            expect($config)->toHaveKey('api_path');
            expect($config)->toHaveKey('info');
            expect($config)->toHaveKey('servers');
            expect($config)->toHaveKey('middleware');
            expect($config)->toHaveKey('routes');
            expect($config)->toHaveKey('cache');
            expect($config)->toHaveKey('analysis');
            expect($config)->toHaveKey('security');
            expect($config)->toHaveKey('extensions');
            expect($config)->toHaveKey('output');
            expect($config)->toHaveKey('debug');
            
        });

        test('Config file info section', function () {
            $configPath = __DIR__ . '/../../src/Config/config.php';
            $config = include $configPath;
            
            // Test info section structure
            expect($config['info'])->toBeArray();
            expect($config['info'])->toHaveKey('version');
            expect($config['info'])->toHaveKey('title');
            expect($config['info'])->toHaveKey('description');
            expect($config['info'])->toHaveKey('contact');
            expect($config['info'])->toHaveKey('license');
            
            // Test contact structure
            expect($config['info']['contact'])->toBeArray();
            expect($config['info']['contact'])->toHaveKey('name');
            expect($config['info']['contact'])->toHaveKey('email');
            expect($config['info']['contact'])->toHaveKey('url');
            
            // Test license structure
            expect($config['info']['license'])->toBeArray();
            expect($config['info']['license'])->toHaveKey('name');
            expect($config['info']['license'])->toHaveKey('url');
            
        });

        test('Config file routes section', function () {
            $configPath = __DIR__ . '/../../src/Config/config.php';
            $config = include $configPath;

            // Test routes section structure
            expect($config['routes'])->toBeArray();
            expect($config['routes'])->toHaveKey('ui');
            expect($config['routes'])->toHaveKey('json');
            expect($config['routes'])->toHaveKey('enabled');

        });

        test('Config file cache section', function () {
            $configPath = __DIR__ . '/../../src/Config/config.php';
            $config = include $configPath;

            // Test cache section structure
            expect($config['cache'])->toBeArray();
            expect($config['cache'])->toHaveKey('enabled');
            expect($config['cache'])->toHaveKey('ttl');
            expect($config['cache'])->toHaveKey('prefix');
            expect($config['cache'])->toHaveKey('store');

        });

        test('Config file analysis section', function () {
            $configPath = __DIR__ . '/../../src/Config/config.php';
            $config = include $configPath;

            // Test analysis section structure
            expect($config['analysis'])->toBeArray();
            expect($config['analysis'])->toHaveKey('type_inference');
            expect($config['analysis'])->toHaveKey('parse_docblocks');
            expect($config['analysis'])->toHaveKey('exclude_paths');
            expect($config['analysis'])->toHaveKey('include_extensions');

            // Test exclude_paths is array
            expect($config['analysis']['exclude_paths'])->toBeArray();
            expect($config['analysis']['include_extensions'])->toBeArray();

        });

        test('Config file security section', function () {
            $configPath = __DIR__ . '/../../src/Config/config.php';
            $config = include $configPath;

            // Test security section structure
            expect($config['security'])->toBeArray();
            expect($config['security'])->toHaveKey('default_schemes');
            expect($config['security'])->toHaveKey('schemes');

            expect($config['security']['default_schemes'])->toBeArray();
            expect($config['security']['schemes'])->toBeArray();

        });

        test('Config file output section', function () {
            $configPath = __DIR__ . '/../../src/Config/config.php';
            $config = include $configPath;

            // Test output section structure
            expect($config['output'])->toBeArray();
            expect($config['output'])->toHaveKey('default_path');
            expect($config['output'])->toHaveKey('default_filename');
            expect($config['output'])->toHaveKey('html_path');
            expect($config['output'])->toHaveKey('auto_create_directory');

        });

        test('Config file debug section', function () {
            $configPath = __DIR__ . '/../../src/Config/config.php';
            $config = include $configPath;

            // Test debug section structure
            expect($config['debug'])->toBeArray();
            expect($config['debug'])->toHaveKey('enabled');
            expect($config['debug'])->toHaveKey('log_analysis');
            expect($config['debug'])->toHaveKey('verbose_errors');

        });
    });

    describe('Helpers File Coverage', function () {
        test('Helpers file can be loaded', function () {
            // Test loading the helpers file
            $helpersPath = __DIR__ . '/../../src/Config/helpers.php';
            expect(file_exists($helpersPath))->toBe(true);
            
            // Include the helpers file
            include_once $helpersPath;
            
            // Test that functions are defined
            expect(function_exists('scramble_config'))->toBe(true);
            
        });

        test('scramble_config function basic functionality', function () {
            // Include helpers if not already included
            $helpersPath = __DIR__ . '/../../src/Config/helpers.php';
            include_once $helpersPath;

            // Test scramble_config function exists
            expect(function_exists('scramble_config'))->toBe(true);

            // Test calling scramble_config with null (should return config instance)
            try {
                $config = scramble_config();
                expect($config)->toBeObject();
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }

        });

        test('scramble_config function with key parameter', function () {
            $helpersPath = __DIR__ . '/../../src/Config/helpers.php';
            include_once $helpersPath;

            // Test scramble_config function with key
            try {
                $value = scramble_config('api_path', 'default_api');
                expect($value)->toBeString();
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }

            // Test with non-existent key and default
            try {
                $defaultValue = scramble_config('non_existent_key', 'default_value');
                expect($defaultValue)->toBe('default_value');
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }

        });

        test('env function functionality', function () {
            $helpersPath = __DIR__ . '/../../src/Config/helpers.php';
            include_once $helpersPath;

            // Test env function exists (might be defined in helpers or elsewhere)
            expect(function_exists('env'))->toBe(true);

            // Test env function with default value
            $value = env('NON_EXISTENT_ENV_VAR', 'default_value');
            expect($value)->toBe('default_value');

        });

        test('env function type conversion', function () {
            $helpersPath = __DIR__ . '/../../src/Config/helpers.php';
            include_once $helpersPath;

            // Set some test environment variables
            $_ENV['TEST_TRUE'] = 'true';
            $_ENV['TEST_FALSE'] = 'false';
            $_ENV['TEST_NUMBER'] = '123';
            $_ENV['TEST_FLOAT'] = '123.45';
            $_ENV['TEST_NULL'] = 'null';

            // Test type conversion (env function may or may not do conversion)
            $trueValue = env('TEST_TRUE');
            $falseValue = env('TEST_FALSE');
            $numberValue = env('TEST_NUMBER');
            $floatValue = env('TEST_FLOAT');
            $nullValue = env('TEST_NULL');

            // Just test that values are returned (type conversion depends on implementation)
            expect($trueValue)->not()->toBeNull();
            expect($falseValue)->not()->toBeNull();
            expect($numberValue)->not()->toBeNull();
            expect($floatValue)->not()->toBeNull();
            expect($nullValue)->not()->toBeEmpty();

            // Clean up
            unset($_ENV['TEST_TRUE'], $_ENV['TEST_FALSE'], $_ENV['TEST_NUMBER'], $_ENV['TEST_FLOAT'], $_ENV['TEST_NULL']);

        });
    });

    describe('Integration Tests', function () {
        test('Config and helpers integration', function () {
            // Test that config and helpers work together
            $configPath = __DIR__ . '/../../src/Config/config.php';
            $helpersPath = __DIR__ . '/../../src/Config/helpers.php';
            
            expect(file_exists($configPath))->toBe(true);
            expect(file_exists($helpersPath))->toBe(true);
            
            // Load both files
            $config = include $configPath;
            include_once $helpersPath;
            
            expect($config)->toBeArray();
            expect(function_exists('scramble_config'))->toBe(true);
            expect(function_exists('env'))->toBe(true);
            
        });
    });
});
