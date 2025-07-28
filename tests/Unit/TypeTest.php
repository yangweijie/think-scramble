<?php

declare(strict_types=1);

use Yangweijie\ThinkScramble\Analyzer\Type\Type;
use Yangweijie\ThinkScramble\Analyzer\Type\ScalarType;
use Yangweijie\ThinkScramble\Analyzer\Type\UnionType;
use Yangweijie\ThinkScramble\Analyzer\Type\ArrayType;

describe('Type Classes Tests', function () {
    test('Type can be instantiated', function () {
        $type = new Type('string');
        
        expect($type)->toBeInstanceOf(Type::class);
        expect($type->getName())->toBe('string');
        expect($type->isNullable())->toBeFalse();
    })->covers(\Yangweijie\ThinkScramble\Analyzer\Type\Type::class);

    test('Type can be nullable', function () {
        $type = new Type('string', true);
        
        expect($type->isNullable())->toBeTrue();
        expect($type->getName())->toBe('string');
        
        // Test setting nullable
        $type2 = new Type('int');
        $type2->setNullable(true);
        expect($type2->isNullable())->toBeTrue();
    })->covers(\Yangweijie\ThinkScramble\Analyzer\Type\Type::class);

    test('Type can be created using static method', function () {
        $type = Type::create('boolean', true);
        
        expect($type)->toBeInstanceOf(Type::class);
        expect($type->getName())->toBe('boolean');
        expect($type->isNullable())->toBeTrue();
    })->covers(\Yangweijie\ThinkScramble\Analyzer\Type\Type::class);

    test('Type can check compatibility', function () {
        $type1 = new Type('string');
        $type2 = new Type('string');
        $type3 = new Type('int');
        $mixedType = new Type('mixed');
        
        expect($type1->isCompatibleWith($type2))->toBeTrue();
        expect($type1->isCompatibleWith($type3))->toBeFalse();
        expect($type1->isCompatibleWith($mixedType))->toBeTrue();
        expect($mixedType->isCompatibleWith($type1))->toBeTrue();
    })->covers(\Yangweijie\ThinkScramble\Analyzer\Type\Type::class);

    test('Type can be converted to string', function () {
        $type = new Type('string');
        $nullableType = new Type('int', true);
        
        $stringRepresentation = (string) $type;
        $nullableStringRepresentation = (string) $nullableType;
        
        expect($stringRepresentation)->toBeString();
        expect($nullableStringRepresentation)->toBeString();
        expect($stringRepresentation)->toContain('string');
        expect($nullableStringRepresentation)->toContain('int');
    })->covers(\Yangweijie\ThinkScramble\Analyzer\Type\Type::class);

    test('ScalarType can be instantiated with valid types', function () {
        $intType = new ScalarType('int');
        $floatType = new ScalarType('float');
        $stringType = new ScalarType('string');
        $boolType = new ScalarType('bool');
        
        expect($intType)->toBeInstanceOf(ScalarType::class);
        expect($floatType)->toBeInstanceOf(ScalarType::class);
        expect($stringType)->toBeInstanceOf(ScalarType::class);
        expect($boolType)->toBeInstanceOf(ScalarType::class);
        
        expect($intType->getName())->toBe('int');
        expect($floatType->getName())->toBe('float');
        expect($stringType->getName())->toBe('string');
        expect($boolType->getName())->toBe('bool');
    })->covers(\Yangweijie\ThinkScramble\Analyzer\Type\ScalarType::class);

    test('ScalarType throws exception for invalid types', function () {
        expect(function () {
            new ScalarType('invalid');
        })->toThrow(\InvalidArgumentException::class);
        
        expect(function () {
            new ScalarType('array');
        })->toThrow(\InvalidArgumentException::class);
    })->covers(\Yangweijie\ThinkScramble\Analyzer\Type\ScalarType::class);

    test('ScalarType has static factory methods', function () {
        $intType = ScalarType::int();
        $floatType = ScalarType::float();
        $stringType = ScalarType::string();
        $boolType = ScalarType::bool();
        
        expect($intType)->toBeInstanceOf(ScalarType::class);
        expect($floatType)->toBeInstanceOf(ScalarType::class);
        expect($stringType)->toBeInstanceOf(ScalarType::class);
        expect($boolType)->toBeInstanceOf(ScalarType::class);
        
        expect($intType->getName())->toBe('int');
        expect($floatType->getName())->toBe('float');
        expect($stringType->getName())->toBe('string');
        expect($boolType->getName())->toBe('bool');
        
        // Test nullable versions
        $nullableInt = ScalarType::int(true);
        expect($nullableInt->isNullable())->toBeTrue();
    })->covers(\Yangweijie\ThinkScramble\Analyzer\Type\ScalarType::class);

    test('UnionType can be instantiated with multiple types', function () {
        $stringType = new ScalarType('string');
        $intType = new ScalarType('int');
        
        $unionType = new UnionType([$stringType, $intType]);
        
        expect($unionType)->toBeInstanceOf(UnionType::class);
        expect($unionType->getTypes())->toHaveCount(2);
        expect($unionType->getTypes()[0])->toBe($stringType);
        expect($unionType->getTypes()[1])->toBe($intType);
    })->covers(\Yangweijie\ThinkScramble\Analyzer\Type\UnionType::class);

    test('UnionType throws exception for insufficient types', function () {
        $stringType = new ScalarType('string');
        
        expect(function () use ($stringType) {
            new UnionType([$stringType]);
        })->toThrow(\InvalidArgumentException::class);
        
        expect(function () {
            new UnionType([]);
        })->toThrow(\InvalidArgumentException::class);
    })->covers(\Yangweijie\ThinkScramble\Analyzer\Type\UnionType::class);

    test('ArrayType can be instantiated', function () {
        $arrayType = new ArrayType();
        
        expect($arrayType)->toBeInstanceOf(ArrayType::class);
        expect($arrayType->getName())->toBe('array');
    })->covers(\Yangweijie\ThinkScramble\Analyzer\Type\ArrayType::class);

    test('ArrayType can be configured', function () {
        $stringType = new ScalarType('string');
        $arrayType = new ArrayType($stringType);

        expect($arrayType)->toBeInstanceOf(ArrayType::class);
        expect($arrayType->getName())->toBe('array');

        $arrayTypeWithoutItem = new ArrayType();
        expect($arrayTypeWithoutItem)->toBeInstanceOf(ArrayType::class);
        expect($arrayTypeWithoutItem->getName())->toBe('array');
    })->covers(\Yangweijie\ThinkScramble\Analyzer\Type\ArrayType::class);



    test('types use memory efficiently', function () {
        $startMemory = memory_get_usage();
        
        // Create many type instances
        for ($i = 0; $i < 100; $i++) {
            $type = new Type("type_{$i}");
            $scalarType = new ScalarType('string');
            $arrayType = new ArrayType();
        }
        
        $endMemory = memory_get_usage();
        $memoryUsed = $endMemory - $startMemory;
        
        // Should use less than 5MB for 100 sets of types
        expect($memoryUsed)->toBeLessThan(5 * 1024 * 1024);
    })->covers(
        \Yangweijie\ThinkScramble\Analyzer\Type\Type::class,
        \Yangweijie\ThinkScramble\Analyzer\Type\ScalarType::class,
        \Yangweijie\ThinkScramble\Analyzer\Type\ArrayType::class
    );

    test('types have good performance', function () {
        $startTime = microtime(true);
        
        // Create many type instances and perform operations
        for ($i = 0; $i < 200; $i++) {
            $type1 = new ScalarType('string');
            $type2 = new ScalarType('int');
            $unionType = new UnionType([$type1, $type2]);
            
            // Perform some operations
            $type1->isCompatibleWith($type2);
            $unionType->getTypes();
        }
        
        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        
        // Should complete in less than 0.5 seconds
        expect($duration)->toBeLessThan(0.5);
    })->covers(
        \Yangweijie\ThinkScramble\Analyzer\Type\ScalarType::class,
        \Yangweijie\ThinkScramble\Analyzer\Type\UnionType::class
    );

    test('types handle edge cases gracefully', function () {
        // Test with empty string type name
        $emptyType = new Type('');
        expect($emptyType->getName())->toBe('');
        
        // Test with very long type name
        $longTypeName = str_repeat('a', 1000);
        $longType = new Type($longTypeName);
        expect($longType->getName())->toBe($longTypeName);
        
        // Test nullable operations
        $type = new Type('test');
        expect($type->isNullable())->toBeFalse();
        
        $type->setNullable(true);
        expect($type->isNullable())->toBeTrue();
        
        $type->setNullable(false);
        expect($type->isNullable())->toBeFalse();
    })->covers(\Yangweijie\ThinkScramble\Analyzer\Type\Type::class);

    test('complex type operations work correctly', function () {
        // Create complex type hierarchy
        $stringType = ScalarType::string();
        $intType = ScalarType::int();
        $boolType = ScalarType::bool(true); // nullable bool

        $unionType = new UnionType([$stringType, $intType]);
        $arrayType = new ArrayType($stringType);

        // Test type operations
        expect($unionType->getTypes())->toHaveCount(2);
        expect($arrayType)->toBeInstanceOf(ArrayType::class);
        expect($boolType->isNullable())->toBeTrue();

        // Test compatibility
        expect($stringType->isCompatibleWith($stringType))->toBeTrue();
        expect($stringType->isCompatibleWith($intType))->toBeFalse();

        // Test string representations
        $stringRep = (string) $unionType;
        expect($stringRep)->toBeString();
        expect(strlen($stringRep))->toBeGreaterThan(0);
    })->covers(
        \Yangweijie\ThinkScramble\Analyzer\Type\ScalarType::class,
        \Yangweijie\ThinkScramble\Analyzer\Type\UnionType::class,
        \Yangweijie\ThinkScramble\Analyzer\Type\ArrayType::class
    );

    test('type inheritance works correctly', function () {
        $scalarType = new ScalarType('string');
        $arrayType = new ArrayType();
        $unionType = new UnionType([
            new ScalarType('string'),
            new ScalarType('int')
        ]);

        // All should be instances of Type
        expect($scalarType)->toBeInstanceOf(Type::class);
        expect($arrayType)->toBeInstanceOf(Type::class);
        expect($unionType)->toBeInstanceOf(Type::class);

        // But also instances of their specific types
        expect($scalarType)->toBeInstanceOf(ScalarType::class);
        expect($arrayType)->toBeInstanceOf(ArrayType::class);
        expect($unionType)->toBeInstanceOf(UnionType::class);
    })->covers(
        \Yangweijie\ThinkScramble\Analyzer\Type\Type::class,
        \Yangweijie\ThinkScramble\Analyzer\Type\ScalarType::class,
        \Yangweijie\ThinkScramble\Analyzer\Type\ArrayType::class,
        \Yangweijie\ThinkScramble\Analyzer\Type\UnionType::class
    );
});
