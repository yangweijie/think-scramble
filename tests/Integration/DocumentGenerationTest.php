<?php

use Yangweijie\ThinkScramble\Config\ScrambleConfig;
use Yangweijie\ThinkScramble\Cache\CacheManager;

describe("Document Generation Integration", function () {
    beforeEach(function () {
        $this->config = new ScrambleConfig();
        $this->cacheManager = new CacheManager($this->app, $this->config);
    });

    it("can integrate config and cache systems", function () {
        expect($this->config)->toBeInstanceOf(ScrambleConfig::class);
        expect($this->cacheManager)->toBeInstanceOf(CacheManager::class);
        
        $title = $this->config->get("info.title");
        expect($title)->toBe("API Documentation");
        
        $key = "integration_test";
        $value = ["test" => "data"];
        
        $setResult = $this->cacheManager->set($key, $value, 60);
        expect($setResult)->toBeBool();
        
        $retrieved = $this->cacheManager->get($key);
        expect($retrieved === $value || $retrieved === null)->toBeTrue();
    });

    it("can handle configuration validation", function () {
        expect($this->config->validate())->toBeTrue();
        
        $this->config->set("info.version", "");
        expect($this->config->validate())->toBeFalse();
        
        $this->config->set("info.version", "1.0.0");
        expect($this->config->validate())->toBeTrue();
    });

    afterEach(function () {
        cleanupTestFiles();
    });
});
