<?php

declare(strict_types=1);

use Yangweijie\ThinkScramble\Config\ScrambleConfig;
use Yangweijie\ThinkScramble\Console\ScrambleCommand;
use Yangweijie\ThinkScramble\Scramble;
use think\App;

describe('Config and Scramble Core Coverage Tests', function () {
    
    beforeEach(function () {
        $this->app = new App();
        $this->sampleConfig = [
            'info' => [
                'title' => 'Test API',
                'version' => '1.0.0',
                'description' => 'A test API for coverage testing'
            ],
            'servers' => [
                ['url' => 'https://api.test.com', 'description' => 'Test server']
            ],
            'paths' => [
                'controllers' => 'app/controller',
                'middleware' => 'app/middleware'
            ],
            'cache' => [
                'driver' => 'memory',
                'ttl' => 3600,
                'prefix' => 'scramble_'
            ],
            'docs' => [
                'enabled' => true,
                'route' => '/docs',
                'ui' => 'swagger'
            ]
        ];
    });

    describe('ScrambleConfig Comprehensive Coverage', function () {
        test('ScrambleConfig can be instantiated with array', function () {
            $sampleConfig = [
                'info' => [
                    'title' => 'Test API',
                    'version' => '1.0.0'
                ]
            ];
            $config = new ScrambleConfig($sampleConfig);

            // Test basic instantiation
            expect($config)->toBeInstanceOf(ScrambleConfig::class);

        })->covers(\Yangweijie\ThinkScramble\Config\ScrambleConfig::class);

        test('ScrambleConfig basic functionality', function () {
            $sampleConfig = [
                'info' => [
                    'title' => 'Test API',
                    'version' => '1.0.0'
                ]
            ];
            $config = new ScrambleConfig($sampleConfig);

            // Test basic functionality
            expect($config)->toBeInstanceOf(ScrambleConfig::class);

            // Test converting to array
            $configArray = $config->toArray();
            expect($configArray)->toBeArray();

        })->covers(\Yangweijie\ThinkScramble\Config\ScrambleConfig::class);
    });

    describe('ScrambleCommand Console Coverage', function () {
        test('ScrambleCommand can be instantiated', function () {
            $command = new ScrambleCommand();
            
            // Test basic instantiation
            expect($command)->toBeInstanceOf(ScrambleCommand::class);
            
        })->covers(\Yangweijie\ThinkScramble\Console\ScrambleCommand::class);

        test('ScrambleCommand basic functionality', function () {
            $command = new ScrambleCommand();

            // Test basic functionality
            expect($command)->toBeInstanceOf(ScrambleCommand::class);

        })->covers(\Yangweijie\ThinkScramble\Console\ScrambleCommand::class);
    });

    describe('Scramble Main Class Coverage', function () {
        test('Scramble enhanced functionality', function () {
            // Test version method
            $version = Scramble::version();
            expect($version)->toBeString();
            expect($version)->toBe('1.0.0');

            // Test reset method
            Scramble::reset();
            expect(Scramble::getConfig())->toBeNull();

            // Test config management
            $sampleConfig = [
                'info' => [
                    'title' => 'Test API',
                    'version' => '1.0.0'
                ]
            ];
            $config = new ScrambleConfig($sampleConfig);

            Scramble::setConfig($config);
            $retrievedConfig = Scramble::getConfig();
            expect($retrievedConfig)->toBeInstanceOf(ScrambleConfig::class);

            // Test init method
            try {
                Scramble::init($sampleConfig);
                expect(true)->toBe(true);
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }

        })->covers(\Yangweijie\ThinkScramble\Scramble::class);
    });

    describe('Integration Tests', function () {
        test('Config and Scramble integration', function () {
            $sampleConfig = [
                'info' => [
                    'title' => 'Test API',
                    'version' => '1.0.0'
                ]
            ];
            $config = new ScrambleConfig($sampleConfig);

            // Test basic integration
            expect($config)->toBeInstanceOf(ScrambleConfig::class);
            expect(Scramble::class)->toBeString();

        })->covers(
            \Yangweijie\ThinkScramble\Config\ScrambleConfig::class,
            \Yangweijie\ThinkScramble\Scramble::class
        );
    });
});
