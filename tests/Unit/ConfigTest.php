<?php

use Yangweijie\ThinkScramble\Config\ScrambleConfig;

describe('ScrambleConfig', function () {
    beforeEach(function () {
        $this->config = new ScrambleConfig();
    });

    it('has default configuration values', function () {
        expect($this->config->get('info.version'))->toBe('1.0.0')
            ->and($this->config->get('info.title'))->toBe('API Documentation')
            ->and($this->config->get('cache.enabled'))->toBeTrue()
            ->and($this->config->get('cache.ttl'))->toBe(3600);
    });

    it('can set and get configuration values', function () {
        $this->config->set('test.key', 'test_value');

        expect($this->config->get('test.key'))->toBe('test_value');
    });

    it('can handle nested configuration', function () {
        $this->config->set('nested.deep.key', 'nested_value');

        expect($this->config->get('nested.deep.key'))->toBe('nested_value');
    });

    it('can merge custom configuration', function () {
        $customConfig = [
            'info' => [
                'title' => 'Custom API',
                'description' => 'Custom Description'
            ],
            'custom' => [
                'setting' => 'value'
            ]
        ];

        $config = new ScrambleConfig($customConfig);

        expect($config->get('info.version'))->toBe('1.0.0') // 保留默认值
            ->and($config->get('info.title'))->toBe('Custom API') // 使用自定义值
            ->and($config->get('info.description'))->toBe('Custom Description')
            ->and($config->get('custom.setting'))->toBe('value');
    });

    it('can check if configuration exists', function () {
        expect($this->config->has('info.version'))->toBeTrue()
            ->and($this->config->has('non.existent.key'))->toBeFalse();

        $this->config->set('test.exists', true);
        expect($this->config->has('test.exists'))->toBeTrue();
    });

    it('returns default value for non-existent keys', function () {
        expect($this->config->get('non.existent', 'default'))->toBe('default')
            ->and($this->config->get('non.existent'))->toBeNull();

        // 存在的配置不应返回默认值
        expect($this->config->get('info.version', 'default'))->not->toBe('default');
    });

    it('can get all configuration', function () {
        $all = $this->config->all();

        expect($all)->toBeArray()
            ->and($all)->toHaveKey('info')
            ->and($all)->toHaveKey('cache');
    });

    it('can validate configuration', function () {
        expect($this->config->validate())->toBeTrue();

        // 测试无效配置
        $this->config->set('info.version', ''); // 空版本号应该无效
        expect($this->config->validate())->toBeFalse();
    });

    it('can handle environment specific config', function () {
        $this->config->set('app.debug', true);
        expect($this->config->isDebugMode())->toBeTrue();

        $this->config->set('app.debug', false);
        expect($this->config->isDebugMode())->toBeFalse();
    });

    it('can serialize configuration', function () {
        $this->config->set('test.data', ['array' => 'value']);
        $serialized = $this->config->toArray();

        expect($serialized)->toBeArray()
            ->and($serialized['test']['data']['array'])->toBe('value');
    });

    it('can create configuration from array', function () {
        $configArray = [
            'info' => [
                'title' => 'Test API',
                'version' => '2.0.0'
            ],
            'paths' => [
                'include' => ['/api/*'],
                'exclude' => ['/api/internal/*']
            ]
        ];

        $config = ScrambleConfig::fromArray($configArray);

        expect($config->get('info.title'))->toBe('Test API')
            ->and($config->get('info.version'))->toBe('2.0.0')
            ->and($config->get('paths.include'))->toBe(['/api/*']);
    });

    it('can clone configuration', function () {
        $this->config->set('original.value', 'test');
        $cloned = clone $this->config;

        // 修改克隆的配置不应影响原配置
        $cloned->set('original.value', 'modified');

        expect($this->config->get('original.value'))->toBe('test')
            ->and($cloned->get('original.value'))->toBe('modified');
    });

    it('can reset configuration', function () {
        $this->config->set('test.reset', 'value');
        expect($this->config->has('test.reset'))->toBeTrue();

        $this->config->reset();
        expect($this->config->has('test.reset'))->toBeFalse()
            ->and($this->config->has('info.version'))->toBeTrue(); // 默认配置应该仍然存在
    });

    it('has good performance for configuration operations', function () {
        $startTime = microtime(true);

        // 执行大量配置操作
        for ($i = 0; $i < 1000; $i++) {
            $this->config->set("performance.test.{$i}", "value_{$i}");
            $this->config->get("performance.test.{$i}");
        }

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000; // 转换为毫秒

        expect($duration)->toHavePerformanceWithin(100, 'Configuration operations');
    });

    it('uses memory efficiently', function () {
        $initialMemory = memory_get_usage(true);

        // 创建大量配置
        for ($i = 0; $i < 1000; $i++) {
            $this->config->set("memory.test.{$i}", str_repeat('x', 100));
        }

        $finalMemory = memory_get_usage(true);
        $memoryUsed = $finalMemory - $initialMemory;

        // 内存使用应该合理（小于5MB）
        expect($memoryUsed)->toBeLessThan(5 * 1024 * 1024);
    });
});
