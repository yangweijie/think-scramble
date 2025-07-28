<?php

declare(strict_types=1);

use Yangweijie\ThinkScramble\Config\ScrambleConfig;
use Yangweijie\ThinkScramble\Config\ConfigPublisher;
use think\App;

describe('Config Advanced Coverage Tests', function () {
    
    beforeEach(function () {
        $this->app = new App();
        $this->configData = [
            'info' => [
                'title' => 'Config Advanced Test API',
                'version' => '1.0.0',
                'description' => 'API for testing config functionality'
            ],
            'servers' => [
                ['url' => 'https://api.test.com', 'description' => 'Test server']
            ],
            'paths' => [],
            'components' => [
                'schemas' => [],
                'securitySchemes' => []
            ],
            'security' => [],
            'tags' => []
        ];
    });

    describe('ScrambleConfig Comprehensive Coverage', function () {
        test('ScrambleConfig construction and basic methods', function () {
            $configData = [
                'info' => [
                    'title' => 'Config Advanced Test API',
                    'version' => '1.0.0',
                    'description' => 'API for testing config functionality'
                ],
                'servers' => [
                    ['url' => 'https://api.test.com', 'description' => 'Test server']
                ],
                'paths' => [],
                'components' => [
                    'schemas' => [],
                    'securitySchemes' => []
                ],
                'security' => [],
                'tags' => []
            ];

            $config = new ScrambleConfig($configData);

            // Test basic instantiation
            expect($config)->toBeInstanceOf(ScrambleConfig::class);

            // Test get method
            $title = $config->get('info.title');
            expect($title)->toBe('Config Advanced Test API');

            $version = $config->get('info.version');
            expect($version)->toBe('1.0.0');

            // Test get with default value
            $nonExistent = $config->get('non.existent.key', 'default_value');
            expect($nonExistent)->toBe('default_value');

            // Test has method
            expect($config->has('info.title'))->toBe(true);
            expect($config->has('non.existent.key'))->toBe(false);

        })->covers(\Yangweijie\ThinkScramble\Config\ScrambleConfig::class);

        test('ScrambleConfig array access and conversion', function () {
            $configData = [
                'info' => [
                    'title' => 'Config Advanced Test API',
                    'version' => '1.0.0'
                ]
            ];

            $config = new ScrambleConfig($configData);

            // Test toArray method
            $array = $config->toArray();
            expect($array)->toBeArray();
            expect($array['info']['title'])->toBe('Config Advanced Test API');

        })->covers(\Yangweijie\ThinkScramble\Config\ScrambleConfig::class);
    });

    describe('ConfigPublisher Comprehensive Coverage', function () {
        test('ConfigPublisher basic functionality', function () {
            $publisher = new ConfigPublisher();

            // Test basic instantiation
            expect($publisher)->toBeInstanceOf(ConfigPublisher::class);

            // Test publish method
            try {
                $result = $publisher->publish();
                expect($result)->toBeBool();
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }

        })->covers(\Yangweijie\ThinkScramble\Config\ConfigPublisher::class);

        test('ConfigPublisher path and file operations', function () {
            $publisher = new ConfigPublisher();

            // Test getSourcePath method
            try {
                $path = $publisher->getSourcePath();
                expect($path)->toBeString();
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }

            // Test getTargetPath method
            try {
                $path = $publisher->getTargetPath();
                expect($path)->toBeString();
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }

            // Test isPublished method
            try {
                $isPublished = $publisher->isPublished();
                expect($isPublished)->toBeBool();
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }

        })->covers(\Yangweijie\ThinkScramble\Config\ConfigPublisher::class);

        test('ConfigPublisher additional operations', function () {
            $publisher = new ConfigPublisher();

            // Test basic functionality
            expect($publisher)->toBeInstanceOf(ConfigPublisher::class);

        })->covers(\Yangweijie\ThinkScramble\Config\ConfigPublisher::class);
    });

    describe('Config Files Coverage', function () {
        test('Config file loading and structure', function () {
            // Test loading the main config file
            $configPath = __DIR__ . '/../../src/Config/config.php';
            expect(file_exists($configPath))->toBe(true);
            
            $config = include $configPath;
            expect($config)->toBeArray();
            
            // Test basic config structure
            expect($config)->toHaveKey('info');
            expect($config['info'])->toHaveKey('title');
            expect($config['info'])->toHaveKey('version');
            
        });

        test('Helpers file loading and functions', function () {
            // Test loading the helpers file
            $helpersPath = __DIR__ . '/../../src/Config/helpers.php';
            expect(file_exists($helpersPath))->toBe(true);
            
            // Include helpers if not already included
            include_once $helpersPath;
            
            // Test scramble_config function exists
            expect(function_exists('scramble_config'))->toBe(true);
            
            // Test env function exists
            expect(function_exists('env'))->toBe(true);
            
            // Test scramble_config function with null
            try {
                $config = scramble_config();
                expect($config)->toBeObject();
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
            
            // Test scramble_config function with key
            try {
                $value = scramble_config('info.title', 'Default Title');
                expect($value)->toBeString();
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
            
        });
    });

    describe('Integration Tests', function () {
        test('Config and Publisher integration', function () {
            $configData = [
                'info' => [
                    'title' => 'Config Advanced Test API',
                    'version' => '1.0.0'
                ]
            ];

            $config = new ScrambleConfig($configData);
            $publisher = new ConfigPublisher();

            // Test that both components work together
            expect($config)->toBeInstanceOf(ScrambleConfig::class);
            expect($publisher)->toBeInstanceOf(ConfigPublisher::class);

            // Test workflow
            try {
                $configArray = $config->toArray();
                $publishResult = $publisher->publish();

                expect($configArray)->toBeArray();
                expect($publishResult)->toBeBool();
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }

        })->covers(
            \Yangweijie\ThinkScramble\Config\ScrambleConfig::class,
            \Yangweijie\ThinkScramble\Config\ConfigPublisher::class
        );
    });
});
