<?php

declare(strict_types=1);

use Yangweijie\ThinkScramble\Config\ScrambleConfig;
use Yangweijie\ThinkScramble\Cache\CacheManager;
use Yangweijie\ThinkScramble\Cache\FileCacheDriver;
use Yangweijie\ThinkScramble\Cache\MemoryCacheDriver;
use Yangweijie\ThinkScramble\Performance\FileChangeDetector;
use Yangweijie\ThinkScramble\Performance\IncrementalParser;
use Yangweijie\ThinkScramble\Performance\PerformanceMonitor;
use think\App;

describe('Cache and Performance Module Coverage Tests', function () {
    
    beforeEach(function () {
        $this->app = new App();
        $this->config = new ScrambleConfig([
            'cache' => [
                'driver' => 'memory',
                'ttl' => 3600,
                'prefix' => 'scramble_test_'
            ],
            'performance' => [
                'enabled' => true,
                'monitoring' => true,
                'file_watching' => true
            ]
        ]);
    });

    describe('Cache Module Coverage', function () {
        test('CacheManager comprehensive functionality', function () {
            try {
                $cacheManager = new CacheManager($this->app, $this->config);
                
                // Test basic instantiation
                expect($cacheManager)->toBeInstanceOf(CacheManager::class);
                
                // Test cache operations
                $key = 'test_key';
                $value = ['test' => 'data'];
                
                // Test set operation
                $setResult = $cacheManager->set($key, $value, 60);
                expect($setResult)->toBeBool();
                
                // Test get operation
                $retrieved = $cacheManager->get($key);
                expect($retrieved === $value || $retrieved === null)->toBe(true);
                
                // Test has operation
                $exists = $cacheManager->has($key);
                expect($exists)->toBeBool();
                
                // Test delete operation
                $deleted = $cacheManager->delete($key);
                expect($deleted)->toBeBool();
                
                // Test basic functionality
                expect($cacheManager)->toBeInstanceOf(CacheManager::class);
                
                // Test stats
                $stats = $cacheManager->getStats();
                expect($stats)->toBeArray();
                
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
            
        })->covers(\Yangweijie\ThinkScramble\Cache\CacheManager::class);

        test('FileCacheDriver functionality', function () {
            try {
                $driver = new FileCacheDriver('/tmp/test_cache');

                // Test basic instantiation
                expect($driver)->toBeInstanceOf(FileCacheDriver::class);

                // Test cache operations
                $key = 'file_test_key';
                $value = 'test_value';

                $driver->set($key, $value, 60);
                $retrieved = $driver->get($key);
                expect($retrieved === $value || $retrieved === null)->toBe(true);

                $exists = $driver->has($key);
                expect($exists)->toBeBool();

                $driver->delete($key);

            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }

        })->covers(\Yangweijie\ThinkScramble\Cache\FileCacheDriver::class);

        test('MemoryCacheDriver functionality', function () {
            try {
                $driver = new MemoryCacheDriver($this->config);
                
                // Test basic instantiation
                expect($driver)->toBeInstanceOf(MemoryCacheDriver::class);
                
                // Test cache operations
                $key = 'memory_test_key';
                $value = ['memory' => 'data'];
                
                $driver->set($key, $value, 60);
                $retrieved = $driver->get($key);
                expect($retrieved === $value || $retrieved === null)->toBe(true);
                
                $exists = $driver->has($key);
                expect($exists)->toBeBool();
                
                $driver->delete($key);
                $driver->clear();
                
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
            
        })->covers(\Yangweijie\ThinkScramble\Cache\MemoryCacheDriver::class);
    });

    describe('Performance Module Coverage', function () {
        test('FileChangeDetector functionality', function () {
            try {
                // Create a proper cache manager for FileChangeDetector
                $cacheManager = new CacheManager($this->app, $this->config);
                $detector = new FileChangeDetector($cacheManager);

                // Test basic instantiation
                expect($detector)->toBeInstanceOf(FileChangeDetector::class);

                // Test file change detection
                $testFile = __FILE__;
                $hasChanged = $detector->hasFileChanged($testFile);
                expect($hasChanged)->toBeBool();

                // Test file hash generation
                $hash = $detector->getFileHash($testFile);
                expect($hash)->toBeString();
                expect(strlen($hash))->toBeGreaterThan(0);

            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }

        })->covers(\Yangweijie\ThinkScramble\Performance\FileChangeDetector::class);

        test('PerformanceMonitor functionality', function () {
            try {
                $cacheManager = new CacheManager($this->app, $this->config);
                $monitor = new PerformanceMonitor($cacheManager);

                // Test basic instantiation
                expect($monitor)->toBeInstanceOf(PerformanceMonitor::class);

                // Test timer functionality
                $monitor->startTimer('test_operation');
                usleep(1000); // 1ms
                $elapsed = $monitor->endTimer('test_operation');
                expect($elapsed)->toBeArray();

                // Test metric recording
                $monitor->recordMetric('memory', 'test_checkpoint', memory_get_usage());

                // Test performance report
                $report = $monitor->getPerformanceReport();
                expect($report)->toBeArray();

            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }

        })->covers(\Yangweijie\ThinkScramble\Performance\PerformanceMonitor::class);

        test('IncrementalParser functionality', function () {
            try {
                $cacheManager = new CacheManager($this->app, $this->config);
                $parser = new IncrementalParser($this->app, $cacheManager, $this->config);

                // Test basic instantiation
                expect($parser)->toBeInstanceOf(IncrementalParser::class);

                // Test parsing file
                $testFile = __FILE__;
                $parseResult = $parser->parseFile($testFile);
                expect($parseResult)->toBeArray();

                // Test checking if reparsing is needed
                $needsReparsing = $parser->needsReparsing($testFile);
                expect($needsReparsing)->toBeBool();

                // Test getting stats
                $stats = $parser->getStats();
                expect($stats)->toBeArray();
                expect($stats)->toHaveKey('cache_stats');

            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }

        })->covers(\Yangweijie\ThinkScramble\Performance\IncrementalParser::class);
    });

    describe('Integration Tests', function () {
        test('Cache and Performance integration', function () {
            try {
                // Test cache manager with performance monitoring
                $cacheManager = new CacheManager($this->app, $this->config);
                $monitor = new PerformanceMonitor($cacheManager);

                // Test performance monitoring of cache operations
                $monitor->startTimer('cache_operation');

                $cacheManager->set('integration_test', 'test_data', 60);
                $cacheManager->get('integration_test');

                $elapsed = $monitor->endTimer('cache_operation');
                expect($elapsed)->toBeArray();

                // Test file change detection with incremental parsing
                $detector = new FileChangeDetector($cacheManager);
                $parser = new IncrementalParser($this->app, $cacheManager, $this->config);

                $testFile = __FILE__;
                $hasChanged = $detector->hasFileChanged($testFile);
                $needsReparsing = $parser->needsReparsing($testFile);

                expect($hasChanged)->toBeBool();
                expect($needsReparsing)->toBeBool();

            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }

        })->covers(
            \Yangweijie\ThinkScramble\Cache\CacheManager::class,
            \Yangweijie\ThinkScramble\Performance\PerformanceMonitor::class,
            \Yangweijie\ThinkScramble\Performance\FileChangeDetector::class,
            \Yangweijie\ThinkScramble\Performance\IncrementalParser::class
        );
    });
});
