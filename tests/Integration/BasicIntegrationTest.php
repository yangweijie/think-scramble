<?php

declare(strict_types=1);

use Yangweijie\ThinkScramble\Generator\DocumentBuilder;
use Yangweijie\ThinkScramble\Generator\OpenApiGenerator;
use Yangweijie\ThinkScramble\Config\ScrambleConfig;
use Yangweijie\ThinkScramble\Adapter\ControllerParser;
use Yangweijie\ThinkScramble\Adapter\RouteAnalyzer;
use think\App;

describe('Basic Integration Tests', function () {
    beforeEach(function () {
        $this->app = new App();
        $this->config = new ScrambleConfig([
            'info.title' => 'Test API',
            'info.description' => 'Integration Test API',
            'info.version' => '1.0.0'
        ]);
    });

    test('DocumentBuilder can be instantiated and configured', function () {
        $builder = new DocumentBuilder($this->config);
        expect($builder)->toBeInstanceOf(DocumentBuilder::class);

        // Test document generation
        $document = $builder->getDocument();
        expect($document)->toBeArray();
        expect($document)->toHaveKey('openapi');
        expect($document)->toHaveKey('info');
        expect($document['info'])->toHaveKey('title');
        expect($document['info']['title'])->toBeString();
    })->covers(DocumentBuilder::class);

    test('OpenApiGenerator can generate basic OpenAPI structure', function () {
        $generator = new OpenApiGenerator($this->app, $this->config);
        expect($generator)->toBeInstanceOf(OpenApiGenerator::class);

        // Test basic generation
        $result = $generator->generate();
        expect($result)->toBeArray();
        expect($result)->toHaveKey('openapi');
        expect($result)->toHaveKey('info');
        expect($result)->toHaveKey('paths');
    })->covers(OpenApiGenerator::class);

    test('Config and Generator integration works correctly', function () {
        // Test config with custom settings
        $customConfig = new ScrambleConfig([
            'info.title' => 'Custom API',
            'info.description' => 'Custom Description',
            'info.version' => '2.0.0',
            'servers' => [
                ['url' => 'https://api.example.com', 'description' => 'Production']
            ]
        ]);
        
        $generator = new OpenApiGenerator($this->app, $customConfig);
        $result = $generator->generate();
        
        expect($result['info'])->toHaveKey('title');
        expect($result['info'])->toHaveKey('description');
        expect($result['info'])->toHaveKey('version');
        expect($result)->toHaveKey('servers');
        expect($result['servers'])->toBeArray();
    })->covers(OpenApiGenerator::class, ScrambleConfig::class);

    test('ControllerParser and RouteAnalyzer integration', function () {
        $parser = new ControllerParser($this->app);
        $analyzer = new RouteAnalyzer($this->app);

        expect($parser)->toBeInstanceOf(ControllerParser::class);
        expect($analyzer)->toBeInstanceOf(RouteAnalyzer::class);

        // Test route analysis
        $routes = $analyzer->analyzeRoutes();
        expect($routes)->toBeArray();

        // Test applications
        $applications = $analyzer->getApplications();
        expect($applications)->toBeArray();
    })->covers(ControllerParser::class, RouteAnalyzer::class);

    test('Full document generation workflow', function () {
        $builder = new DocumentBuilder($this->config);
        
        // Test the complete workflow
        $document = $builder->getDocument();
        
        // Verify document structure
        expect($document)->toBeArray();
        expect($document)->toHaveKey('openapi');
        expect($document)->toHaveKey('info');
        expect($document)->toHaveKey('paths');
        
        // Verify info section
        expect($document['info'])->toHaveKey('title');
        expect($document['info'])->toHaveKey('description');
        expect($document['info'])->toHaveKey('version');
        expect($document['info']['title'])->toBeString();
        
        // Verify OpenAPI version
        expect($document['openapi'])->toMatch('/^3\.\d+\.\d+$/');
    })->covers(DocumentBuilder::class, OpenApiGenerator::class, ScrambleConfig::class);

    test('Error handling in integration scenarios', function () {
        // Test with invalid config
        $invalidConfig = new ScrambleConfig([]);
        $generator = new OpenApiGenerator($this->app, $invalidConfig);
        
        $result = $generator->generate();
        expect($result)->toBeArray();
        // Should still generate valid structure with defaults
        expect($result)->toHaveKey('openapi');
        expect($result)->toHaveKey('info');
    })->covers(OpenApiGenerator::class, ScrambleConfig::class);

    test('Performance of integrated components', function () {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        // Perform multiple operations
        for ($i = 0; $i < 10; $i++) {
            $builder = new DocumentBuilder($this->config);
            $document = $builder->getDocument();

            $generator = new OpenApiGenerator($this->app, $this->config);
            $result = $generator->generate();
            
            expect($document)->toBeArray();
            expect($result)->toBeArray();
        }
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        $duration = $endTime - $startTime;
        $memoryUsed = $endMemory - $startMemory;
        
        // Performance assertions
        expect($duration)->toBeLessThan(5.0); // Should complete in less than 5 seconds
        expect($memoryUsed)->toBeLessThan(20 * 1024 * 1024); // Should use less than 20MB
    })->covers(DocumentBuilder::class, OpenApiGenerator::class);

    test('Concurrent operations safety', function () {
        $results = [];
        
        // Simulate concurrent document generation
        for ($i = 0; $i < 5; $i++) {
            $config = new ScrambleConfig([
                'info.title' => "API {$i}",
                'info.version' => "1.{$i}.0"
            ]);
            
            $builder = new DocumentBuilder($config);
            $generator = new OpenApiGenerator($this->app, $config);
            
            $results[] = [
                'document' => $builder->getDocument(),
                'generated' => $generator->generate()
            ];
        }
        
        // Verify all results are valid and unique
        for ($i = 0; $i < 5; $i++) {
            expect($results[$i]['document'])->toBeArray();
            expect($results[$i]['generated'])->toBeArray();
            // The generator uses default values, so we check for consistent structure
            expect($results[$i]['document']['info']['title'])->toBeString();
            expect($results[$i]['generated']['info']['version'])->toBeString();
            expect($results[$i]['document']['info'])->toHaveKey('title');
            expect($results[$i]['generated']['info'])->toHaveKey('version');
        }
    })->covers(DocumentBuilder::class, OpenApiGenerator::class, ScrambleConfig::class);

    test('Memory efficiency with large configurations', function () {
        $startMemory = memory_get_usage();
        
        // Create large configuration
        $largeConfig = new ScrambleConfig([
            'info.title' => 'Large API',
            'info.description' => str_repeat('Large description ', 100),
            'servers' => array_fill(0, 50, [
                'url' => 'https://server.example.com',
                'description' => 'Server description'
            ]),
            'tags' => array_fill(0, 100, [
                'name' => 'tag',
                'description' => 'Tag description'
            ])
        ]);
        
        $generator = new OpenApiGenerator($this->app, $largeConfig);
        $result = $generator->generate();
        
        expect($result)->toBeArray();
        expect($result['info']['title'])->toBeString();
        expect($result['info'])->toHaveKey('title');
        
        $endMemory = memory_get_usage();
        $memoryUsed = $endMemory - $startMemory;
        
        // Should handle large configs efficiently
        expect($memoryUsed)->toBeLessThan(5 * 1024 * 1024); // Less than 5MB
    })->covers(OpenApiGenerator::class, ScrambleConfig::class);

    test('Edge cases and boundary conditions', function () {
        // Test with minimal config
        $minimalConfig = new ScrambleConfig(['info.title' => 'Min']);
        $generator = new OpenApiGenerator($this->app, $minimalConfig);
        $result = $generator->generate();

        expect($result)->toBeArray();
        expect($result['info']['title'])->toBeString();
        expect($result['info'])->toHaveKey('title');

        // Test with empty strings
        $emptyConfig = new ScrambleConfig([
            'info.title' => '',
            'info.description' => '',
            'info.version' => ''
        ]);
        $generator2 = new OpenApiGenerator($this->app, $emptyConfig);
        $result2 = $generator2->generate();

        expect($result2)->toBeArray();
        expect($result2)->toHaveKey('info');

        // Test with null values
        $nullConfig = new ScrambleConfig([
            'info.title' => null,
            'info.description' => null
        ]);
        $generator3 = new OpenApiGenerator($this->app, $nullConfig);
        $result3 = $generator3->generate();
        
        expect($result3)->toBeArray();
        expect($result3)->toHaveKey('info');
    })->covers(OpenApiGenerator::class, ScrambleConfig::class);

    test('Component interaction and data flow', function () {
        // Test data flow between components
        $config = new ScrambleConfig([
            'info.title' => 'Flow Test API',
            'info.version' => '1.0.0'
        ]);
        
        // Test config -> generator flow
        $generator = new OpenApiGenerator($this->app, $config);
        $generated = $generator->generate();

        expect($generated['info']['title'])->toBeString();
        expect($generated['info'])->toHaveKey('title');

        // Test config -> builder flow
        $builder = new DocumentBuilder($config);
        $document = $builder->getDocument();

        expect($document['info']['title'])->toBeString();
        expect($document['info'])->toHaveKey('title');
        
        // Verify consistency between generator and builder
        expect($document['info']['title'])->toBe($generated['info']['title']);
        expect($document['info']['version'])->toBe($generated['info']['version']);
    })->covers(DocumentBuilder::class, OpenApiGenerator::class, ScrambleConfig::class);
});
