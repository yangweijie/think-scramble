<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Analyzer;

use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionType;
use ReflectionNamedType;
use ReflectionUnionType;
use ReflectionIntersectionType;
use Yangweijie\ThinkScramble\Analyzer\Type\Type;
use Yangweijie\ThinkScramble\Analyzer\Type\ScalarType;
use Yangweijie\ThinkScramble\Analyzer\Type\ArrayType;
use Yangweijie\ThinkScramble\Analyzer\Type\UnionType;
use Yangweijie\ThinkScramble\Exception\AnalysisException;

/**
 * 反射分析器
 * 
 * 结合 PHP 反射机制获取运行时类型信息
 */
class ReflectionAnalyzer
{
    /**
     * 反射缓存
     */
    protected array $reflectionCache = [];

    /**
     * 分析类
     *
     * @param string $className 类名
     * @return array
     * @throws AnalysisException
     */
    public function analyzeClass(string $className): array
    {
        try {
            $reflection = $this->getClassReflection($className);
            
            return [
                'name' => $reflection->getName(),
                'namespace' => $reflection->getNamespaceName(),
                'short_name' => $reflection->getShortName(),
                'is_abstract' => $reflection->isAbstract(),
                'is_final' => $reflection->isFinal(),
                'is_interface' => $reflection->isInterface(),
                'is_trait' => $reflection->isTrait(),
                'parent_class' => $reflection->getParentClass() ? $reflection->getParentClass()->getName() : null,
                'interfaces' => array_keys($reflection->getInterfaces()),
                'traits' => array_keys($reflection->getTraits()),
                'methods' => $this->analyzeMethods($reflection),
                'properties' => $this->analyzeProperties($reflection),
                'constants' => $reflection->getConstants(),
                'doc_comment' => $reflection->getDocComment() ?: null,
            ];
        } catch (\ReflectionException $e) {
            throw AnalysisException::reflectionFailed($className, $e->getMessage());
        }
    }

    /**
     * 分析方法
     *
     * @param string $className 类名
     * @param string $methodName 方法名
     * @return array
     * @throws AnalysisException
     */
    public function analyzeMethod(string $className, string $methodName): array
    {
        try {
            $classReflection = $this->getClassReflection($className);
            $methodReflection = $classReflection->getMethod($methodName);
            
            return $this->getMethodInfo($methodReflection);
        } catch (\ReflectionException $e) {
            throw AnalysisException::reflectionFailed("{$className}::{$methodName}", $e->getMessage());
        }
    }

    /**
     * 分析函数
     *
     * @param string $functionName 函数名
     * @return array
     * @throws AnalysisException
     */
    public function analyzeFunction(string $functionName): array
    {
        try {
            $reflection = new \ReflectionFunction($functionName);
            
            return [
                'name' => $reflection->getName(),
                'parameters' => $this->analyzeParameters($reflection->getParameters()),
                'return_type' => $this->analyzeReturnType($reflection),
                'is_variadic' => $reflection->isVariadic(),
                'doc_comment' => $reflection->getDocComment() ?: null,
            ];
        } catch (\ReflectionException $e) {
            throw AnalysisException::reflectionFailed($functionName, $e->getMessage());
        }
    }

    /**
     * 分析类的所有方法
     *
     * @param ReflectionClass $reflection
     * @return array
     */
    protected function analyzeMethods(ReflectionClass $reflection): array
    {
        $methods = [];
        
        foreach ($reflection->getMethods() as $method) {
            $methods[$method->getName()] = $this->getMethodInfo($method);
        }
        
        return $methods;
    }

    /**
     * 获取方法信息
     *
     * @param ReflectionMethod $method
     * @return array
     */
    protected function getMethodInfo(ReflectionMethod $method): array
    {
        return [
            'name' => $method->getName(),
            'class' => $method->getDeclaringClass()->getName(),
            'visibility' => $this->getVisibility($method),
            'is_static' => $method->isStatic(),
            'is_abstract' => $method->isAbstract(),
            'is_final' => $method->isFinal(),
            'is_constructor' => $method->isConstructor(),
            'is_destructor' => $method->isDestructor(),
            'parameters' => $this->analyzeParameters($method->getParameters()),
            'return_type' => $this->analyzeReturnType($method),
            'doc_comment' => $method->getDocComment() ?: null,
        ];
    }

    /**
     * 分析类的所有属性
     *
     * @param ReflectionClass $reflection
     * @return array
     */
    protected function analyzeProperties(ReflectionClass $reflection): array
    {
        $properties = [];
        
        foreach ($reflection->getProperties() as $property) {
            $properties[$property->getName()] = [
                'name' => $property->getName(),
                'class' => $property->getDeclaringClass()->getName(),
                'visibility' => $this->getPropertyVisibility($property),
                'is_static' => $property->isStatic(),
                'type' => $this->analyzePropertyType($property),
                'default_value' => $property->isDefault() ? $this->getPropertyDefaultValue($property) : null,
                'doc_comment' => $property->getDocComment() ?: null,
            ];
        }
        
        return $properties;
    }

    /**
     * 分析参数
     *
     * @param ReflectionParameter[] $parameters
     * @return array
     */
    protected function analyzeParameters(array $parameters): array
    {
        $result = [];
        
        foreach ($parameters as $parameter) {
            $result[] = [
                'name' => $parameter->getName(),
                'position' => $parameter->getPosition(),
                'type' => $this->analyzeParameterType($parameter),
                'is_optional' => $parameter->isOptional(),
                'is_variadic' => $parameter->isVariadic(),
                'is_passed_by_reference' => $parameter->isPassedByReference(),
                'default_value' => $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null,
                'allows_null' => $parameter->allowsNull(),
            ];
        }
        
        return $result;
    }

    /**
     * 分析返回类型
     *
     * @param ReflectionMethod|\ReflectionFunction $reflection
     * @return Type|null
     */
    protected function analyzeReturnType($reflection): ?Type
    {
        $returnType = $reflection->getReturnType();
        
        if ($returnType === null) {
            return null;
        }
        
        return $this->convertReflectionType($returnType);
    }

    /**
     * 分析参数类型
     *
     * @param ReflectionParameter $parameter
     * @return Type|null
     */
    protected function analyzeParameterType(ReflectionParameter $parameter): ?Type
    {
        $type = $parameter->getType();
        
        if ($type === null) {
            return null;
        }
        
        return $this->convertReflectionType($type);
    }

    /**
     * 分析属性类型
     *
     * @param ReflectionProperty $property
     * @return Type|null
     */
    protected function analyzePropertyType(ReflectionProperty $property): ?Type
    {
        if (!$property->hasType()) {
            return null;
        }
        
        $type = $property->getType();
        return $this->convertReflectionType($type);
    }

    /**
     * 转换反射类型为内部类型
     *
     * @param ReflectionType $reflectionType
     * @return Type
     */
    protected function convertReflectionType(ReflectionType $reflectionType): Type
    {
        if ($reflectionType instanceof ReflectionNamedType) {
            $typeName = $reflectionType->getName();
            $nullable = $reflectionType->allowsNull();
            
            // 标量类型
            if (in_array($typeName, ['int', 'float', 'string', 'bool'])) {
                return new ScalarType($typeName, $nullable);
            }
            
            // 数组类型
            if ($typeName === 'array') {
                return ArrayType::simple($nullable);
            }
            
            // 其他类型
            return new Type($typeName, $nullable);
        }
        
        if ($reflectionType instanceof ReflectionUnionType) {
            $types = [];
            foreach ($reflectionType->getTypes() as $type) {
                $types[] = $this->convertReflectionType($type);
            }
            return new UnionType($types);
        }
        
        if ($reflectionType instanceof ReflectionIntersectionType) {
            // PHP 8.1+ 交集类型，暂时返回 mixed
            return new Type('mixed');
        }
        
        return new Type('mixed');
    }

    /**
     * 获取方法可见性
     *
     * @param ReflectionMethod $method
     * @return string
     */
    protected function getVisibility(ReflectionMethod $method): string
    {
        if ($method->isPublic()) {
            return 'public';
        }
        
        if ($method->isProtected()) {
            return 'protected';
        }
        
        return 'private';
    }

    /**
     * 获取属性可见性
     *
     * @param ReflectionProperty $property
     * @return string
     */
    protected function getPropertyVisibility(ReflectionProperty $property): string
    {
        if ($property->isPublic()) {
            return 'public';
        }
        
        if ($property->isProtected()) {
            return 'protected';
        }
        
        return 'private';
    }

    /**
     * 获取属性默认值
     *
     * @param ReflectionProperty $property
     * @return mixed
     */
    protected function getPropertyDefaultValue(ReflectionProperty $property): mixed
    {
        try {
            return $property->getDefaultValue();
        } catch (\ReflectionException $e) {
            return null;
        }
    }

    /**
     * 获取类反射实例
     *
     * @param string $className
     * @return ReflectionClass
     * @throws \ReflectionException
     */
    protected function getClassReflection(string $className): ReflectionClass
    {
        if (!isset($this->reflectionCache[$className])) {
            $this->reflectionCache[$className] = new ReflectionClass($className);
        }
        
        return $this->reflectionCache[$className];
    }

    /**
     * 清除反射缓存
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->reflectionCache = [];
    }
}
