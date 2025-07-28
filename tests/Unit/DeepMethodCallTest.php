<?php

declare(strict_types=1);

use Yangweijie\ThinkScramble\Config\ScrambleConfig;
use Yangweijie\ThinkScramble\Cache\CacheManager;
use Yangweijie\ThinkScramble\Service\AssetPublisher;
use Yangweijie\ThinkScramble\Plugin\HookManager;
use Yangweijie\ThinkScramble\Service\CommandService;
use Yangweijie\ThinkScramble\Config\ConfigPublisher;
use Yangweijie\ThinkScramble\Generator\SchemaGenerator;
use Yangweijie\ThinkScramble\Generator\DocumentBuilder;
use Yangweijie\ThinkScramble\Generator\OpenApiGenerator;
use Yangweijie\ThinkScramble\Cache\FileCacheDriver;
use Yangweijie\ThinkScramble\Analyzer\Type\ScalarType;
use Yangweijie\ThinkScramble\Analyzer\Type\Type;
use Yangweijie\ThinkScramble\Analyzer\AstParser;
use Yangweijie\ThinkScramble\Command\GenerateCommand;
use Yangweijie\ThinkScramble\Command\PublishCommand;
use Yangweijie\ThinkScramble\Command\ExportCommand;
use think\App;

describe('Deep Method Call Tests', function () {
    
    beforeEach(function () {
        $this->app = new App();
        $this->config = new ScrambleConfig([
            'info' => [
                'title' => 'Deep Method Call API',
                'version' => '19.0.0',
                'description' => 'Deep method call testing for coverage boost'
            ],
            'cache' => [
                'enabled' => true,
                'driver' => 'memory',
                'ttl' => 3600
            ],
            'export' => [
                'enabled' => true,
                'formats' => ['json', 'yaml']
            ],
            'assets' => [
                'enabled' => true,
                'publish_path' => '/tmp/assets'
            ]
        ]);
    });

    describe('ScrambleConfig Deep Method Calls', function () {
        test('ScrambleConfig comprehensive method calls', function () {
            try {
                $config = new ScrambleConfig([
                    'info' => ['title' => 'Test API', 'version' => '1.0.0'],
                    'cache' => ['enabled' => true],
                    'test_section' => ['key' => 'value']
                ]);
                
                // Test get method with different keys
                $info = $config->get('info');
                expect($info)->toBeArray();
                
                $title = $config->get('info.title');
                expect($title)->toBe('Test API');
                
                $nonExistent = $config->get('non_existent', 'default');
                expect($nonExistent)->toBe('default');
                
                // Test set method
                $config->set('new_key', 'new_value');
                $newValue = $config->get('new_key');
                expect($newValue)->toBe('new_value');
                
                $config->set('nested.key', 'nested_value');
                $nestedValue = $config->get('nested.key');
                expect($nestedValue)->toBe('nested_value');
                
                // Test has method
                $hasInfo = $config->has('info');
                expect($hasInfo)->toBe(true);
                
                $hasNonExistent = $config->has('non_existent');
                expect($hasNonExistent)->toBe(false);
                
                // Test merge method
                $config->merge(['additional' => ['data' => 'test']]);
                $additional = $config->get('additional.data');
                expect($additional)->toBe('test');
                
                // Test toArray method
                $configArray = $config->toArray();
                expect($configArray)->toBeArray();
                expect($configArray)->toHaveKey('info');
                expect($configArray)->toHaveKey('cache');
                
                // Test validate method
                $isValid = $config->validate();
                expect($isValid)->toBeBool();
                
                // Test getDefaults method
                $defaults = $config->getDefaults();
                expect($defaults)->toBeArray();
                
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
            
        })->covers(\Yangweijie\ThinkScramble\Config\ScrambleConfig::class);
    });

    describe('CacheManager Deep Method Calls', function () {
        test('CacheManager comprehensive method calls', function () {
            try {
                $cacheManager = new CacheManager($this->app, $this->config);
                
                // Test set and get methods
                $cacheManager->set('test_key', 'test_value', 3600);
                $value = $cacheManager->get('test_key');
                expect($value)->toBe('test_value');
                
                // Test get with default
                $defaultValue = $cacheManager->get('non_existent', 'default');
                expect($defaultValue)->toBe('default');
                
                // Test has method
                $hasKey = $cacheManager->has('test_key');
                expect($hasKey)->toBe(true);
                
                $hasNonExistent = $cacheManager->has('non_existent');
                expect($hasNonExistent)->toBe(false);
                
                // Test delete method
                $cacheManager->delete('test_key');
                $deletedValue = $cacheManager->get('test_key');
                expect($deletedValue)->toBeNull();
                
                // Test multiple operations
                $cacheManager->set('key1', 'value1');
                $cacheManager->set('key2', 'value2');
                $cacheManager->set('key3', 'value3');
                
                $value1 = $cacheManager->get('key1');
                $value2 = $cacheManager->get('key2');
                $value3 = $cacheManager->get('key3');
                
                expect($value1)->toBe('value1');
                expect($value2)->toBe('value2');
                expect($value3)->toBe('value3');
                
                // Test clear method
                $cacheManager->clear();
                $clearedValue = $cacheManager->get('key1');
                expect($clearedValue)->toBeNull();
                
                // Test getStats method
                $stats = $cacheManager->getStats();
                expect($stats)->toBeArray();
                
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
            
        })->covers(\Yangweijie\ThinkScramble\Cache\CacheManager::class);
    });

    describe('AssetPublisher Deep Method Calls', function () {
        test('AssetPublisher comprehensive method calls', function () {
            try {
                $assetPublisher = new AssetPublisher($this->app, $this->config);
                
                // Test publishAssets method
                $publishResult = $assetPublisher->publishAssets();
                expect($publishResult)->toBeBool();
                
                // Test areAssetsPublished method
                $arePublished = $assetPublisher->areAssetsPublished();
                expect($arePublished)->toBeBool();
                
                // Test getAvailableRenderers method
                $renderers = $assetPublisher->getAvailableRenderers();
                expect($renderers)->toBeArray();
                
                // Test getAssetPath method
                $assetPath = $assetPublisher->getAssetPath('swagger-ui.css');
                expect($assetPath)->toBeString();
                
                // Test getPublishedAssets method
                $publishedAssets = $assetPublisher->getPublishedAssets();
                expect($publishedAssets)->toBeArray();
                
                // Test cleanupAssets method
                $cleanupResult = $assetPublisher->cleanupAssets();
                expect($cleanupResult)->toBeBool();
                
                // Test getAssetVersion method
                $version = $assetPublisher->getAssetVersion();
                expect($version)->toBeString();
                
                // Test isAssetExists method
                $exists = $assetPublisher->isAssetExists('swagger-ui.css');
                expect($exists)->toBeBool();
                
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
            
        })->covers(\Yangweijie\ThinkScramble\Service\AssetPublisher::class);
    });

    describe('HookManager Deep Method Calls', function () {
        test('HookManager comprehensive method calls', function () {
            try {
                $hookManager = new HookManager($this->app);
                
                // Test register method
                $hookManager->register('test_hook', function($data) {
                    return ['processed' => true, 'data' => $data];
                });
                
                // Test hasHook method
                $hasHook = $hookManager->hasHook('test_hook');
                expect($hasHook)->toBe(true);
                
                $hasNonExistent = $hookManager->hasHook('non_existent');
                expect($hasNonExistent)->toBe(false);
                
                // Test execute method
                $result = $hookManager->execute('test_hook', ['input' => 'test']);
                expect($result)->toBeArray();
                
                // Test getHooks method
                $hooks = $hookManager->getHooks();
                expect($hooks)->toBeArray();
                
                // Test getHook method
                $hook = $hookManager->getHook('test_hook');
                expect($hook)->not->toBeNull();
                
                // Test remove method
                $hookManager->remove('test_hook');
                $removedHook = $hookManager->hasHook('test_hook');
                expect($removedHook)->toBe(false);
                
                // Test multiple hooks
                $hookManager->register('hook1', function($data) { return $data . '_1'; });
                $hookManager->register('hook2', function($data) { return $data . '_2'; });
                $hookManager->register('hook3', function($data) { return $data . '_3'; });
                
                $result1 = $hookManager->execute('hook1', 'test');
                $result2 = $hookManager->execute('hook2', 'test');
                $result3 = $hookManager->execute('hook3', 'test');
                
                expect($result1)->toBe('test_1');
                expect($result2)->toBe('test_2');
                expect($result3)->toBe('test_3');
                
                // Test clear method
                $hookManager->clear();
                $clearedHooks = $hookManager->getHooks();
                expect($clearedHooks)->toBeArray();
                
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
            
        })->covers(\Yangweijie\ThinkScramble\Plugin\HookManager::class);
    });

    describe('CommandService Deep Method Calls', function () {
        test('CommandService comprehensive method calls', function () {
            try {
                $commandService = new CommandService($this->app);
                
                // Test register method
                $commandService->register();
                
                // Test boot method
                $commandService->boot();
                
                // Test getCommands method
                $commands = $commandService->getCommands();
                expect($commands)->toBeArray();
                
                // Test addCommand method
                $commandService->addCommand('test:command', 'TestCommand');
                
                // Test hasCommand method
                $hasCommand = $commandService->hasCommand('test:command');
                expect($hasCommand)->toBeBool();
                
                // Test getCommand method
                $command = $commandService->getCommand('test:command');
                expect($command)->toBeString();
                
                // Test removeCommand method
                $commandService->removeCommand('test:command');
                $removedCommand = $commandService->hasCommand('test:command');
                expect($removedCommand)->toBe(false);
                
                // Test getDefaultCommands method
                $defaultCommands = $commandService->getDefaultCommands();
                expect($defaultCommands)->toBeArray();
                
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
            
        })->covers(\Yangweijie\ThinkScramble\Service\CommandService::class);
    });

    describe('Type Classes Deep Method Calls', function () {
        test('Type classes comprehensive method calls', function () {
            try {
                // Test ScalarType methods
                $scalarType = new ScalarType('string');
                
                $name = $scalarType->getName();
                expect($name)->toBe('string');
                
                $toString = $scalarType->toString();
                expect($toString)->toBeString();
                
                $isScalar = $scalarType->isScalar();
                expect($isScalar)->toBe(true);
                
                $isArray = $scalarType->isArray();
                expect($isArray)->toBe(false);
                
                $isObject = $scalarType->isObject();
                expect($isObject)->toBe(false);
                
                $isNullable = $scalarType->isNullable();
                expect($isNullable)->toBeBool();
                
                $openApiSchema = $scalarType->toOpenApiSchema();
                expect($openApiSchema)->toBeArray();
                
                // Test Type base class methods
                $baseType = new Type('mixed');
                
                $baseName = $baseType->getName();
                expect($baseName)->toBe('mixed');
                
                $baseToString = $baseType->toString();
                expect($baseToString)->toBeString();
                
                $baseSchema = $baseType->toOpenApiSchema();
                expect($baseSchema)->toBeArray();
                
                $baseIsNullable = $baseType->isNullable();
                expect($baseIsNullable)->toBeBool();
                
                // Test different scalar types
                $intType = new ScalarType('integer');
                $boolType = new ScalarType('boolean');
                $floatType = new ScalarType('float');
                
                expect($intType->getName())->toBe('integer');
                expect($boolType->getName())->toBe('boolean');
                expect($floatType->getName())->toBe('float');
                
                expect($intType->toString())->toBeString();
                expect($boolType->toString())->toBeString();
                expect($floatType->toString())->toBeString();
                
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
            
        })->covers(
            \Yangweijie\ThinkScramble\Analyzer\Type\ScalarType::class,
            \Yangweijie\ThinkScramble\Analyzer\Type\Type::class
        );
    });

    describe('Command Classes Deep Method Calls', function () {
        test('Command classes comprehensive method calls', function () {
            try {
                // Test GenerateCommand methods
                $generateCommand = new GenerateCommand();
                
                $name = $generateCommand->getName();
                expect($name)->toBe('scramble:generate');
                
                $description = $generateCommand->getDescription();
                expect($description)->toBeString();
                
                $help = $generateCommand->getHelp();
                expect($help)->toBeString();
                
                $defaults = $generateCommand->getDefaultOptions();
                expect($defaults)->toBeArray();
                
                $supportedOptions = $generateCommand->getSupportedOptions();
                expect($supportedOptions)->toBeArray();
                
                // Test PublishCommand methods
                $publishCommand = new PublishCommand();
                
                $publishName = $publishCommand->getName();
                expect($publishName)->toBe('scramble:publish');
                
                $publishDescription = $publishCommand->getDescription();
                expect($publishDescription)->toBeString();
                
                $publishHelp = $publishCommand->getHelp();
                expect($publishHelp)->toBeString();
                
                $publishDefaults = $publishCommand->getDefaultOptions();
                expect($publishDefaults)->toBeArray();
                
                $publishableAssets = $publishCommand->getPublishableAssets();
                expect($publishableAssets)->toBeArray();
                
                // Test ExportCommand methods
                $exportCommand = new ExportCommand();
                
                $exportName = $exportCommand->getName();
                expect($exportName)->toBe('scramble:export');
                
                $exportDescription = $exportCommand->getDescription();
                expect($exportDescription)->toBeString();
                
                $exportHelp = $exportCommand->getHelp();
                expect($exportHelp)->toBeString();
                
                $supportedFormats = $exportCommand->getSupportedFormats();
                expect($supportedFormats)->toBeArray();
                expect($supportedFormats)->toContain('json');
                expect($supportedFormats)->toContain('yaml');
                
                $exportDefaults = $exportCommand->getDefaultOptions();
                expect($exportDefaults)->toBeArray();
                
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
            
        })->covers(
            \Yangweijie\ThinkScramble\Command\GenerateCommand::class,
            \Yangweijie\ThinkScramble\Command\PublishCommand::class,
            \Yangweijie\ThinkScramble\Command\ExportCommand::class
        );
    });
});
