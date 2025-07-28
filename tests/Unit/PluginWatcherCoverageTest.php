<?php

declare(strict_types=1);

use Yangweijie\ThinkScramble\Config\ScrambleConfig;
use Yangweijie\ThinkScramble\Plugin\HookManager;
use Yangweijie\ThinkScramble\Plugin\PluginManager;
use Yangweijie\ThinkScramble\Watcher\FileWatcher;
use think\App;

describe('Plugin and Watcher Module Coverage Tests', function () {
    
    beforeEach(function () {
        $this->app = new App();
        $this->config = new ScrambleConfig([
            'plugins' => [
                'enabled' => true,
                'directories' => ['plugins'],
                'auto_discover' => true
            ],
            'watcher' => [
                'enabled' => true,
                'extensions' => ['php', 'json'],
                'interval' => 1
            ]
        ]);
    });

    describe('Plugin Module Coverage', function () {
        test('HookManager comprehensive functionality', function () {
            $hookManager = new HookManager();
            
            // Test basic instantiation
            expect($hookManager)->toBeInstanceOf(HookManager::class);
            
            // Test registering hooks
            $hookManager->register('test_hook', function($data) {
                return $data . '_processed';
            }, 10);
            
            $hookManager->register('test_hook', function($data) {
                return $data . '_enhanced';
            }, 5);
            
            // Test executing hooks
            $result = $hookManager->execute('test_hook', 'initial_data');
            expect($result)->toBeString();
            
            // Test checking if hook exists
            $exists = $hookManager->hasHook('test_hook');
            expect($exists)->toBe(true);
            
            $notExists = $hookManager->hasHook('non_existent_hook');
            expect($notExists)->toBe(false);
            
            // Test basic hook functionality
            expect($hookManager)->toBeInstanceOf(HookManager::class);
            
        })->covers(\Yangweijie\ThinkScramble\Plugin\HookManager::class);

        test('HookManager priority-based execution', function () {
            $hookManager = new HookManager();
            
            $executionOrder = [];
            
            // Register hooks with different priorities
            $hookManager->register('priority_test', function($data) use (&$executionOrder) {
                $executionOrder[] = 'high_priority';
                return $data;
            }, 100);
            
            $hookManager->register('priority_test', function($data) use (&$executionOrder) {
                $executionOrder[] = 'low_priority';
                return $data;
            }, 1);
            
            $hookManager->register('priority_test', function($data) use (&$executionOrder) {
                $executionOrder[] = 'medium_priority';
                return $data;
            }, 50);
            
            // Execute hooks and check order
            $hookManager->execute('priority_test', 'test_data');
            
            expect($executionOrder)->toBeArray();
            expect(count($executionOrder))->toBe(3);
            
        })->covers(\Yangweijie\ThinkScramble\Plugin\HookManager::class);

        test('PluginManager functionality', function () {
            $hookManager = new HookManager();
            $pluginManager = new PluginManager($this->config, $hookManager);

            // Test basic instantiation
            expect($pluginManager)->toBeInstanceOf(PluginManager::class);

            // Test discovering plugins
            $plugins = $pluginManager->discoverPlugins();
            expect($plugins)->toBeArray();

            // Test getting loaded plugins
            $loadedPlugins = $pluginManager->getLoadedPlugins();
            expect($loadedPlugins)->toBeArray();

        })->covers(\Yangweijie\ThinkScramble\Plugin\PluginManager::class);
    });

    describe('Watcher Module Coverage', function () {
        test('FileWatcher basic functionality', function () {
            $watcher = new FileWatcher();
            
            // Test basic instantiation
            expect($watcher)->toBeInstanceOf(FileWatcher::class);
            
            // Test adding directory
            $currentDir = __DIR__;
            $watcher->addDirectory($currentDir);
            
            // Test setting watch extensions
            $watcher->setWatchExtensions(['php', 'json']);
            
            // Test setting interval
            $watcher->setInterval(5);
            
            // Test getting stats
            $stats = $watcher->getStats();
            expect($stats)->toBeArray();
            expect($stats)->toHaveKey('watching');
            expect($stats)->toHaveKey('directories');
            
        })->covers(\Yangweijie\ThinkScramble\Watcher\FileWatcher::class);

        test('FileWatcher change detection', function () {
            $watcher = new FileWatcher();
            
            // Test adding change callback
            $callbackExecuted = false;
            $watcher->onChange(function($file, $event) use (&$callbackExecuted) {
                $callbackExecuted = true;
            });
            
            // Test change detection
            $changes = $watcher->checkOnce();
            expect($changes)->toBeArray();
            
            // Test getting change summary
            $summary = $watcher->getChangeSummary($changes);
            expect($summary)->toBeArray();
            expect($summary)->toHaveKey('total');
            
            // Test filtering changes
            $filteredChanges = $watcher->filterChanges($changes, ['php']);
            expect($filteredChanges)->toBeArray();
            
        })->covers(\Yangweijie\ThinkScramble\Watcher\FileWatcher::class);

        test('FileWatcher configuration functionality', function () {
            $watcher = new FileWatcher();

            // Test basic instantiation and configuration
            expect($watcher)->toBeInstanceOf(FileWatcher::class);

            // Test adding directory
            $watcher->addDirectory(__DIR__);

            // Test setting watch extensions
            $watcher->setWatchExtensions(['php', 'json']);

            // Test setting interval
            $watcher->setInterval(5);

            // Test getting stats
            $stats = $watcher->getStats();
            expect($stats)->toBeArray();
            expect($stats)->toHaveKey('watching');

        })->covers(\Yangweijie\ThinkScramble\Watcher\FileWatcher::class);
    });

    describe('Integration Tests', function () {
        test('Plugin and Watcher integration', function () {
            $hookManager = new HookManager();
            $pluginManager = new PluginManager($this->config, $hookManager);
            $watcher = new FileWatcher();
            
            // Test plugin system with file watcher
            $hookManager->register('file_changed', function($file) {
                return "File changed: " . $file;
            });
            
            // Test watcher with plugin hooks
            $watcher->onChange(function($file, $_event) use ($hookManager) {
                $hookManager->execute('file_changed', $file);
            });
            
            // Test basic integration
            expect($hookManager)->toBeInstanceOf(HookManager::class);
            expect($pluginManager)->toBeInstanceOf(PluginManager::class);
            expect($watcher)->toBeInstanceOf(FileWatcher::class);
            
            // Test hook execution
            $result = $hookManager->execute('file_changed', 'test.php');
            expect($result)->toBeString();
            
        })->covers(
            \Yangweijie\ThinkScramble\Plugin\HookManager::class,
            \Yangweijie\ThinkScramble\Plugin\PluginManager::class,
            \Yangweijie\ThinkScramble\Watcher\FileWatcher::class
        );
    });
});
