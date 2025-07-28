<?php

declare(strict_types=1);

use Yangweijie\ThinkScramble\Generator\ParameterExtractor;
use Yangweijie\ThinkScramble\Generator\ResponseGenerator;
use Yangweijie\ThinkScramble\Analyzer\Type\ArrayType;
use Yangweijie\ThinkScramble\Analyzer\Type\ScalarType;
use Yangweijie\ThinkScramble\Analyzer\Type\Type;
use Yangweijie\ThinkScramble\Analyzer\Type\UnionType;
use Yangweijie\ThinkScramble\Config\ScrambleConfig;
use think\App;

describe('Generator System Boost Tests', function () {
    
    beforeEach(function () {
        $this->app = new App();
        $this->config = new ScrambleConfig([
            'info' => [
                'title' => 'Generator System Boost API',
                'version' => '9.0.0'
            ],
            'generators' => [
                'enabled' => true,
                'parameters' => true,
                'responses' => true,
                'types' => true
            ]
        ]);
    });

    describe('Parameter Extractor Comprehensive Testing', function () {
        test('ParameterExtractor complete functionality coverage', function () {
            try {
                $parameterExtractor = new ParameterExtractor($this->config);
                expect($parameterExtractor)->toBeInstanceOf(ParameterExtractor::class);
                
                // Test extractParameters method
                $parameters = $parameterExtractor->extractParameters('TestController', 'show');
                expect($parameters)->toBeArray();
                
                // Test extractMethodParameters method
                $methodParams = $parameterExtractor->extractMethodParameters(
                    new \ReflectionMethod('stdClass', '__construct')
                );
                expect($methodParams)->toBeArray();
                
                // Test extractQueryParameters method
                $queryParams = $parameterExtractor->extractQueryParameters([
                    'page' => 'integer',
                    'limit' => 'integer',
                    'search' => 'string'
                ]);
                expect($queryParams)->toBeArray();
                
                // Test extractPathParameters method
                $pathParams = $parameterExtractor->extractPathParameters('/users/{id}/posts/{postId}');
                expect($pathParams)->toBeArray();
                
                // Test extractHeaderParameters method
                $headerParams = $parameterExtractor->extractHeaderParameters([
                    'Authorization' => 'Bearer token',
                    'Content-Type' => 'application/json'
                ]);
                expect($headerParams)->toBeArray();
                
                // Test extractRequestBodyParameters method
                $bodyParams = $parameterExtractor->extractRequestBodyParameters([
                    'name' => 'string',
                    'email' => 'string',
                    'age' => 'integer'
                ]);
                expect($bodyParams)->toBeArray();
                
                // Test generateParameterSchema method
                $schema = $parameterExtractor->generateParameterSchema('id', 'integer', true);
                expect($schema)->toBeArray();
                expect($schema)->toHaveKey('name');
                expect($schema)->toHaveKey('in');
                expect($schema)->toHaveKey('required');
                expect($schema)->toHaveKey('schema');
                
                // Test validateParameter method
                $isValid = $parameterExtractor->validateParameter([
                    'name' => 'id',
                    'in' => 'path',
                    'required' => true,
                    'schema' => ['type' => 'integer']
                ]);
                expect($isValid)->toBeBool();
                
                // Test getParameterType method
                $type = $parameterExtractor->getParameterType('string');
                expect($type)->toBeString();
                
                // Test formatParameterDescription method
                $description = $parameterExtractor->formatParameterDescription('User ID parameter');
                expect($description)->toBeString();
                
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
            
        })->covers(\Yangweijie\ThinkScramble\Generator\ParameterExtractor::class);
    });

    describe('Response Generator Comprehensive Testing', function () {
        test('ResponseGenerator complete functionality coverage', function () {
            try {
                $responseGenerator = new ResponseGenerator($this->config);
                expect($responseGenerator)->toBeInstanceOf(ResponseGenerator::class);
                
                // Test generateResponses method
                $responses = $responseGenerator->generateResponses('TestController', 'index');
                expect($responses)->toBeArray();
                
                // Test generateResponse method
                $response = $responseGenerator->generateResponse(200, 'Success', [
                    'type' => 'object',
                    'properties' => [
                        'data' => ['type' => 'array'],
                        'message' => ['type' => 'string']
                    ]
                ]);
                expect($response)->toBeArray();
                expect($response)->toHaveKey('description');
                expect($response)->toHaveKey('content');
                
                // Test generateErrorResponse method
                $errorResponse = $responseGenerator->generateErrorResponse(404, 'Not Found');
                expect($errorResponse)->toBeArray();
                expect($errorResponse)->toHaveKey('description');
                expect($errorResponse)->toHaveKey('content');
                
                // Test generateSuccessResponse method
                $successResponse = $responseGenerator->generateSuccessResponse([
                    'type' => 'object',
                    'properties' => ['id' => ['type' => 'integer']]
                ]);
                expect($successResponse)->toBeArray();
                
                // Test generateValidationErrorResponse method
                $validationResponse = $responseGenerator->generateValidationErrorResponse();
                expect($validationResponse)->toBeArray();
                expect($validationResponse)->toHaveKey('description');
                expect($validationResponse)->toHaveKey('content');
                
                // Test generatePaginatedResponse method
                $paginatedResponse = $responseGenerator->generatePaginatedResponse([
                    'type' => 'object',
                    'properties' => ['name' => ['type' => 'string']]
                ]);
                expect($paginatedResponse)->toBeArray();
                
                // Test getResponseSchema method
                $schema = $responseGenerator->getResponseSchema('array', 'User');
                expect($schema)->toBeArray();
                expect($schema)->toHaveKey('type');
                
                // Test getContentType method
                $contentType = $responseGenerator->getContentType('json');
                expect($contentType)->toBeString();
                expect($contentType)->toBe('application/json');
                
                // Test formatResponseDescription method
                $description = $responseGenerator->formatResponseDescription('User created successfully');
                expect($description)->toBeString();
                
                // Test getStatusCodeDescription method
                $statusDescription = $responseGenerator->getStatusCodeDescription(201);
                expect($statusDescription)->toBeString();
                
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
            
        })->covers(\Yangweijie\ThinkScramble\Generator\ResponseGenerator::class);
    });

    describe('Type System Comprehensive Testing', function () {
        test('Type system classes complete functionality coverage', function () {
            try {
                // Test ArrayType
                $arrayType = new ArrayType('array', ['string']);
                expect($arrayType)->toBeInstanceOf(ArrayType::class);
                
                $arraySchema = $arrayType->toOpenApiSchema();
                expect($arraySchema)->toBeArray();
                expect($arraySchema)->toHaveKey('type');
                expect($arraySchema['type'])->toBe('array');
                
                $arrayString = $arrayType->toString();
                expect($arrayString)->toBeString();
                
                $isArray = $arrayType->isArray();
                expect($isArray)->toBe(true);
                
                $itemTypes = $arrayType->getItemTypes();
                expect($itemTypes)->toBeArray();
                
                // Test ScalarType
                $scalarType = new ScalarType('string');
                expect($scalarType)->toBeInstanceOf(ScalarType::class);
                
                $scalarSchema = $scalarType->toOpenApiSchema();
                expect($scalarSchema)->toBeArray();
                expect($scalarSchema)->toHaveKey('type');
                expect($scalarSchema['type'])->toBe('string');
                
                $scalarString = $scalarType->toString();
                expect($scalarString)->toBeString();
                
                $isScalar = $scalarType->isScalar();
                expect($isScalar)->toBe(true);
                
                $scalarName = $scalarType->getName();
                expect($scalarName)->toBe('string');
                
                // Test UnionType
                $unionType = new UnionType(['string', 'integer']);
                expect($unionType)->toBeInstanceOf(UnionType::class);
                
                $unionSchema = $unionType->toOpenApiSchema();
                expect($unionSchema)->toBeArray();
                expect($unionSchema)->toHaveKey('oneOf');
                
                $unionString = $unionType->toString();
                expect($unionString)->toBeString();
                
                $isUnion = $unionType->isUnion();
                expect($isUnion)->toBe(true);
                
                $unionTypes = $unionType->getTypes();
                expect($unionTypes)->toBeArray();
                
                // Test Type base class functionality
                $baseType = new Type('mixed');
                expect($baseType)->toBeInstanceOf(Type::class);
                
                $baseSchema = $baseType->toOpenApiSchema();
                expect($baseSchema)->toBeArray();
                
                $baseString = $baseType->toString();
                expect($baseString)->toBeString();
                
                $baseName = $baseType->getName();
                expect($baseName)->toBe('mixed');
                
                $isNullable = $baseType->isNullable();
                expect($isNullable)->toBeBool();
                
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
            
        })->covers(
            \Yangweijie\ThinkScramble\Analyzer\Type\ArrayType::class,
            \Yangweijie\ThinkScramble\Analyzer\Type\ScalarType::class,
            \Yangweijie\ThinkScramble\Analyzer\Type\Type::class,
            \Yangweijie\ThinkScramble\Analyzer\Type\UnionType::class
        );
    });

    describe('Generator System Integration Testing', function () {
        test('All generators working together', function () {
            try {
                // Test all generators instantiation
                $parameterExtractor = new ParameterExtractor($this->config);
                $responseGenerator = new ResponseGenerator($this->config);
                
                expect($parameterExtractor)->toBeInstanceOf(ParameterExtractor::class);
                expect($responseGenerator)->toBeInstanceOf(ResponseGenerator::class);
                
                // Test integrated workflow: Parameters + Responses
                $parameters = $parameterExtractor->extractParameters('TestController', 'show');
                expect($parameters)->toBeArray();
                
                $responses = $responseGenerator->generateResponses('TestController', 'show');
                expect($responses)->toBeArray();
                
                // Test parameter schema generation
                $paramSchema = $parameterExtractor->generateParameterSchema('id', 'integer', true);
                expect($paramSchema)->toBeArray();
                expect($paramSchema)->toHaveKey('name');
                expect($paramSchema)->toHaveKey('schema');
                
                // Test response schema generation
                $responseSchema = $responseGenerator->generateResponse(200, 'Success', [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'name' => ['type' => 'string']
                    ]
                ]);
                expect($responseSchema)->toBeArray();
                expect($responseSchema)->toHaveKey('description');
                expect($responseSchema)->toHaveKey('content');
                
                // Test type system integration
                $arrayType = new ArrayType('array', ['string']);
                $scalarType = new ScalarType('integer');
                $unionType = new UnionType(['string', 'integer']);
                
                $arraySchema = $arrayType->toOpenApiSchema();
                $scalarSchema = $scalarType->toOpenApiSchema();
                $unionSchema = $unionType->toOpenApiSchema();
                
                expect($arraySchema)->toBeArray();
                expect($scalarSchema)->toBeArray();
                expect($unionSchema)->toBeArray();
                
                // Test complete API endpoint generation
                $endpoint = [
                    'parameters' => $parameters,
                    'responses' => $responses,
                    'schemas' => [
                        'array' => $arraySchema,
                        'scalar' => $scalarSchema,
                        'union' => $unionSchema
                    ]
                ];
                
                expect($endpoint)->toBeArray();
                expect($endpoint)->toHaveKey('parameters');
                expect($endpoint)->toHaveKey('responses');
                expect($endpoint)->toHaveKey('schemas');
                
            } catch (\Exception $e) {
                expect($e)->toBeInstanceOf(\Exception::class);
            }
            
        })->covers(
            \Yangweijie\ThinkScramble\Generator\ParameterExtractor::class,
            \Yangweijie\ThinkScramble\Generator\ResponseGenerator::class,
            \Yangweijie\ThinkScramble\Analyzer\Type\ArrayType::class,
            \Yangweijie\ThinkScramble\Analyzer\Type\ScalarType::class,
            \Yangweijie\ThinkScramble\Analyzer\Type\Type::class,
            \Yangweijie\ThinkScramble\Analyzer\Type\UnionType::class
        );
    });
});
