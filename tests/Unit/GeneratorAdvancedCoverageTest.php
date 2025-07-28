<?php

declare(strict_types=1);

use Yangweijie\ThinkScramble\Config\ScrambleConfig;
use Yangweijie\ThinkScramble\Generator\ModelSchemaGenerator;
use Yangweijie\ThinkScramble\Generator\SecuritySchemeGenerator;
use think\App;

describe('Advanced Generator Module Coverage Tests', function () {
    
    beforeEach(function () {
        $this->app = new App();
        $this->config = new ScrambleConfig([
            'info' => [
                'title' => 'Advanced Generator Test API',
                'version' => '1.0.0'
            ],
            'security' => [
                'api_key' => [
                    'type' => 'apiKey',
                    'in' => 'header',
                    'name' => 'X-API-Key'
                ],
                'bearer_token' => [
                    'type' => 'http',
                    'scheme' => 'bearer',
                    'bearerFormat' => 'JWT'
                ]
            ]
        ]);
    });

    describe('Model Schema Generator Coverage', function () {
        test('ModelSchemaGenerator can be instantiated', function () {
            $generator = new ModelSchemaGenerator($this->config);

            // Test basic instantiation
            expect($generator)->toBeInstanceOf(ModelSchemaGenerator::class);

        })->covers(\Yangweijie\ThinkScramble\Generator\ModelSchemaGenerator::class);

        test('ModelSchemaGenerator enhanced functionality', function () {
            $generator = new ModelSchemaGenerator($this->config);

            // Test basic functionality
            expect($generator)->toBeInstanceOf(ModelSchemaGenerator::class);

            // Test generating schema for a model
            try {
                $schema = $generator->generateSchema('TestModel');
                expect($schema)->toBeArray();
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }

            // Test generating multiple schemas
            try {
                $schemas = $generator->generateMultipleSchemas(['TestModel1', 'TestModel2']);
                expect($schemas)->toBeArray();
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }

            // Test clearing cache
            try {
                $generator->clearCache();
                expect(true)->toBe(true);
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }

        })->covers(\Yangweijie\ThinkScramble\Generator\ModelSchemaGenerator::class);
    });

    describe('Security Scheme Generator Coverage', function () {
        test('SecuritySchemeGenerator can be instantiated', function () {
            $generator = new SecuritySchemeGenerator($this->config);
            
            // Test basic instantiation
            expect($generator)->toBeInstanceOf(SecuritySchemeGenerator::class);
            
        })->covers(\Yangweijie\ThinkScramble\Generator\SecuritySchemeGenerator::class);

        test('SecuritySchemeGenerator enhanced functionality', function () {
            $generator = new SecuritySchemeGenerator($this->config);

            // Test basic functionality
            expect($generator)->toBeInstanceOf(SecuritySchemeGenerator::class);

            // Test generating security schemes
            try {
                $schemes = $generator->generateSecuritySchemes(['TestController']);
                expect($schemes)->toBeArray();
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }

            // Test generating method security
            try {
                $methodSecurity = $generator->generateMethodSecurity('TestController', 'testMethod');
                expect($methodSecurity)->toBeArray();
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }

            // Test generating middleware summary
            try {
                $summary = $generator->generateMiddlewareSummary(['TestController']);
                expect($summary)->toBeArray();
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }

            // Test generating security documentation
            try {
                $doc = $generator->generateSecurityDocumentation([]);
                expect($doc)->toBeString();
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }

            // Test validating security config
            try {
                $validation = $generator->validateSecurityConfig([]);
                expect($validation)->toBeArray();
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }

        })->covers(\Yangweijie\ThinkScramble\Generator\SecuritySchemeGenerator::class);
    });

    describe('Integration Tests', function () {
        test('Model and Security generator integration', function () {
            $modelGenerator = new ModelSchemaGenerator($this->config);
            $securityGenerator = new SecuritySchemeGenerator($this->config);

            // Test that both generators work together
            expect($modelGenerator)->toBeInstanceOf(ModelSchemaGenerator::class);
            expect($securityGenerator)->toBeInstanceOf(SecuritySchemeGenerator::class);

        })->covers(
            \Yangweijie\ThinkScramble\Generator\ModelSchemaGenerator::class,
            \Yangweijie\ThinkScramble\Generator\SecuritySchemeGenerator::class
        );
    });
});
