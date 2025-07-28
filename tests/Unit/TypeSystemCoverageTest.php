<?php

declare(strict_types=1);

use Yangweijie\ThinkScramble\Config\ScrambleConfig;
use Yangweijie\ThinkScramble\Analyzer\Type\Type;
use Yangweijie\ThinkScramble\Analyzer\Type\ArrayType;
use Yangweijie\ThinkScramble\Analyzer\Type\ScalarType;
use Yangweijie\ThinkScramble\Analyzer\Type\UnionType;

describe('Type System Coverage Tests', function () {
    
    beforeEach(function () {
        $this->config = new ScrambleConfig([
            'info' => [
                'title' => 'Type System Test API',
                'version' => '1.0.0'
            ]
        ]);
    });

    describe('Base Type Coverage', function () {
        test('Type can be instantiated', function () {
            $type = new Type('string');
            
            // Test basic instantiation
            expect($type)->toBeInstanceOf(Type::class);
            
        })->covers(\Yangweijie\ThinkScramble\Analyzer\Type\Type::class);

        test('Type can handle basic operations', function () {
            $type = new Type('string');
            
            // Test getting type name
            $name = $type->getName();
            expect($name)->toBe('string');
            
            // Test string representation
            $string = $type->toString();
            expect($string)->toBeString();
            
            // Test basic functionality
            expect($type)->toBeInstanceOf(Type::class);
            
        })->covers(\Yangweijie\ThinkScramble\Analyzer\Type\Type::class);

        test('Type can handle different type names', function () {
            $stringType = new Type('string');
            $intType = new Type('int');
            $boolType = new Type('bool');

            expect($stringType->getName())->toBe('string');
            expect($intType->getName())->toBe('int');
            expect($boolType->getName())->toBe('bool');

        })->covers(\Yangweijie\ThinkScramble\Analyzer\Type\Type::class);
    });

    describe('Scalar Type Coverage', function () {
        test('ScalarType can be instantiated', function () {
            $scalarType = new ScalarType('string');
            
            // Test basic instantiation
            expect($scalarType)->toBeInstanceOf(ScalarType::class);
            expect($scalarType)->toBeInstanceOf(Type::class);
            
        })->covers(\Yangweijie\ThinkScramble\Analyzer\Type\ScalarType::class);

        test('ScalarType can handle scalar types', function () {
            $stringType = new ScalarType('string');
            $intType = new ScalarType('int');
            $floatType = new ScalarType('float');
            $boolType = new ScalarType('bool');

            // Test all scalar types
            expect($stringType->getName())->toBe('string');
            expect($intType->getName())->toBe('int');
            expect($floatType->getName())->toBe('float');
            expect($boolType->getName())->toBe('bool');

        })->covers(\Yangweijie\ThinkScramble\Analyzer\Type\ScalarType::class);

        test('ScalarType basic functionality', function () {
            $stringType = new ScalarType('string');
            $intType = new ScalarType('int');

            // Test basic functionality
            expect($stringType)->toBeInstanceOf(ScalarType::class);
            expect($intType)->toBeInstanceOf(ScalarType::class);

        })->covers(\Yangweijie\ThinkScramble\Analyzer\Type\ScalarType::class);
    });

    describe('Array Type Coverage', function () {
        test('ArrayType can be instantiated', function () {
            $itemType = new ScalarType('string');
            $arrayType = new ArrayType($itemType);
            
            // Test basic instantiation
            expect($arrayType)->toBeInstanceOf(ArrayType::class);
            expect($arrayType)->toBeInstanceOf(Type::class);
            
        })->covers(\Yangweijie\ThinkScramble\Analyzer\Type\ArrayType::class);

        test('ArrayType basic functionality', function () {
            $stringItemType = new ScalarType('string');
            $intItemType = new ScalarType('int');

            $stringArrayType = new ArrayType($stringItemType);
            $intArrayType = new ArrayType($intItemType);

            // Test basic functionality
            expect($stringArrayType)->toBeInstanceOf(ArrayType::class);
            expect($intArrayType)->toBeInstanceOf(ArrayType::class);

        })->covers(\Yangweijie\ThinkScramble\Analyzer\Type\ArrayType::class);
    });

    describe('Union Type Coverage', function () {
        test('UnionType can be instantiated', function () {
            $stringType = new ScalarType('string');
            $intType = new ScalarType('int');
            $unionType = new UnionType([$stringType, $intType]);
            
            // Test basic instantiation
            expect($unionType)->toBeInstanceOf(UnionType::class);
            expect($unionType)->toBeInstanceOf(Type::class);
            
        })->covers(\Yangweijie\ThinkScramble\Analyzer\Type\UnionType::class);

        test('UnionType basic functionality', function () {
            $stringType = new ScalarType('string');
            $intType = new ScalarType('int');
            $boolType = new ScalarType('bool');

            $unionType = new UnionType([$stringType, $intType, $boolType]);

            // Test basic functionality
            expect($unionType)->toBeInstanceOf(UnionType::class);

        })->covers(\Yangweijie\ThinkScramble\Analyzer\Type\UnionType::class);
    });

    describe('Type System Integration Tests', function () {
        test('Complex type combinations', function () {
            // Create complex nested types
            $stringType = new ScalarType('string');
            $intType = new ScalarType('int');

            // Array of strings
            $stringArrayType = new ArrayType($stringType);

            // Union of string and integer
            $stringIntUnionType = new UnionType([$stringType, $intType]);

            // Array of union types
            $unionArrayType = new ArrayType($stringIntUnionType);

            // Test all types are properly instantiated
            expect($stringArrayType)->toBeInstanceOf(ArrayType::class);
            expect($stringIntUnionType)->toBeInstanceOf(UnionType::class);
            expect($unionArrayType)->toBeInstanceOf(ArrayType::class);

        })->covers(
            \Yangweijie\ThinkScramble\Analyzer\Type\Type::class,
            \Yangweijie\ThinkScramble\Analyzer\Type\ScalarType::class,
            \Yangweijie\ThinkScramble\Analyzer\Type\ArrayType::class,
            \Yangweijie\ThinkScramble\Analyzer\Type\UnionType::class
        );

        test('Type basic integration', function () {
            $stringType1 = new ScalarType('string');
            $stringType2 = new ScalarType('string');
            $intType = new ScalarType('int');

            // Test basic functionality
            expect($stringType1)->toBeInstanceOf(ScalarType::class);
            expect($stringType2)->toBeInstanceOf(ScalarType::class);
            expect($intType)->toBeInstanceOf(ScalarType::class);

            // Test array type creation
            $stringArray1 = new ArrayType($stringType1);
            $stringArray2 = new ArrayType($stringType2);
            $intArray = new ArrayType($intType);

            expect($stringArray1)->toBeInstanceOf(ArrayType::class);
            expect($stringArray2)->toBeInstanceOf(ArrayType::class);
            expect($intArray)->toBeInstanceOf(ArrayType::class);

        })->covers(
            \Yangweijie\ThinkScramble\Analyzer\Type\Type::class,
            \Yangweijie\ThinkScramble\Analyzer\Type\ScalarType::class,
            \Yangweijie\ThinkScramble\Analyzer\Type\ArrayType::class
        );
    });
});
