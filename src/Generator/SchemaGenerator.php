<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Generator;

use Yangweijie\ThinkScramble\Contracts\ConfigInterface;
use Yangweijie\ThinkScramble\Analyzer\Type\Type;
use Yangweijie\ThinkScramble\Analyzer\Type\ScalarType;
use Yangweijie\ThinkScramble\Analyzer\Type\ArrayType;
use Yangweijie\ThinkScramble\Analyzer\Type\UnionType;
use Yangweijie\ThinkScramble\Exception\GenerationException;

/**
 * OpenAPI 数据模式生成器
 * 
 * 生成符合 OpenAPI 3.0 规范的数据模式定义
 */
class SchemaGenerator
{
    /**
     * 配置接口
     */
    protected ConfigInterface $config;

    /**
     * 模式缓存
     */
    protected array $schemaCache = [];

    /**
     * 类型映射
     */
    protected array $typeMapping = [
        'int' => 'integer',
        'integer' => 'integer',
        'float' => 'number',
        'double' => 'number',
        'string' => 'string',
        'bool' => 'boolean',
        'boolean' => 'boolean',
        'array' => 'array',
        'object' => 'object',
        'mixed' => null, // 特殊处理
    ];

    /**
     * 格式映射
     */
    protected array $formatMapping = [
        'email' => 'email',
        'url' => 'uri',
        'uri' => 'uri',
        'date' => 'date',
        'datetime' => 'date-time',
        'time' => 'time',
        'password' => 'password',
        'binary' => 'binary',
        'byte' => 'byte',
    ];

    /**
     * 构造函数
     *
     * @param ConfigInterface $config 配置接口
     */
    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * 从类型对象生成模式
     *
     * @param Type $type 类型对象
     * @return array
     * @throws GenerationException
     */
    public function generateFromType(Type $type): array
    {
        try {
            return $this->convertType($type);
        } catch (\Exception $e) {
            throw new GenerationException("Failed to generate schema from type: " . $e->getMessage());
        }
    }

    /**
     * 从数组数据生成模式
     *
     * @param array $data 数据数组
     * @param string|null $name 模式名称
     * @return array
     * @throws GenerationException
     */
    public function generateFromArray(array $data, ?string $name = null): array
    {
        try {
            return $this->analyzeArrayStructure($data, $name);
        } catch (\Exception $e) {
            throw new GenerationException("Failed to generate schema from array: " . $e->getMessage());
        }
    }

    /**
     * 从类生成模式
     *
     * @param string $className 类名
     * @return array
     * @throws GenerationException
     */
    public function generateFromClass(string $className): array
    {
        if (isset($this->schemaCache[$className])) {
            return $this->schemaCache[$className];
        }

        try {
            if (!class_exists($className)) {
                throw new GenerationException("Class not found: {$className}");
            }

            $reflection = new \ReflectionClass($className);
            $schema = $this->analyzeClassStructure($reflection);
            
            $this->schemaCache[$className] = $schema;
            return $schema;

        } catch (\Exception $e) {
            throw new GenerationException("Failed to generate schema from class {$className}: " . $e->getMessage());
        }
    }

    /**
     * 转换类型对象
     *
     * @param Type $type 类型对象
     * @return array
     */
    protected function convertType(Type $type): array
    {
        $typeName = $type->getName();
        $nullable = $type->isNullable();

        if ($type instanceof ScalarType) {
            return $this->convertScalarType($type);
        }

        if ($type instanceof ArrayType) {
            return $this->convertArrayType($type);
        }

        if ($type instanceof UnionType) {
            return $this->convertUnionType($type);
        }

        // 处理其他类型
        $schema = $this->convertBasicType($typeName);
        
        if ($nullable) {
            $schema['nullable'] = true;
        }

        return $schema;
    }

    /**
     * 转换标量类型
     *
     * @param ScalarType $type 标量类型
     * @return array
     */
    protected function convertScalarType(ScalarType $type): array
    {
        $typeName = $type->getName();
        $schema = [
            'type' => $this->typeMapping[$typeName] ?? 'string',
        ];

        // 添加示例值
        $schema['example'] = $this->getExampleValue($typeName);

        if ($type->isNullable()) {
            $schema['nullable'] = true;
        }

        return $schema;
    }

    /**
     * 转换数组类型
     *
     * @param ArrayType $type 数组类型
     * @return array
     */
    protected function convertArrayType(ArrayType $type): array
    {
        $schema = [
            'type' => 'array',
        ];

        $valueType = $type->getValueType();
        if ($valueType) {
            $schema['items'] = $this->convertType($valueType);
        } else {
            $schema['items'] = ['type' => 'object'];
        }

        if ($type->isNullable()) {
            $schema['nullable'] = true;
        }

        return $schema;
    }

    /**
     * 转换联合类型
     *
     * @param UnionType $type 联合类型
     * @return array
     */
    protected function convertUnionType(UnionType $type): array
    {
        $types = $type->getTypes();
        $schemas = [];

        foreach ($types as $subType) {
            $schemas[] = $this->convertType($subType);
        }

        return [
            'oneOf' => $schemas,
        ];
    }

    /**
     * 转换基本类型
     *
     * @param string $typeName 类型名称
     * @return array
     */
    protected function convertBasicType(string $typeName): array
    {
        if (isset($this->typeMapping[$typeName])) {
            return [
                'type' => $this->typeMapping[$typeName],
                'example' => $this->getExampleValue($typeName),
            ];
        }

        // 排除不应该生成模式的类
        $excludedClasses = [
            'think\Response',
            'think\response\Json',
            'think\response\Xml',
            'think\response\Html',
            'think\response\Redirect',
            'think\response\View',
            'think\Request',
            'think\Collection',
            'think\Paginator',
        ];

        // 处理类类型
        if (class_exists($typeName) && !in_array($typeName, $excludedClasses)) {
            return [
                '$ref' => "#/components/schemas/" . $this->getSchemaName($typeName),
            ];
        }

        // 默认为字符串类型
        return [
            'type' => 'string',
            'example' => 'string',
        ];
    }

    /**
     * 分析数组结构
     *
     * @param array $data 数据数组
     * @param string|null $name 模式名称
     * @return array
     */
    protected function analyzeArrayStructure(array $data, ?string $name = null): array
    {
        if (empty($data)) {
            return [
                'type' => 'array',
                'items' => ['type' => 'object'],
            ];
        }

        // 检查是否为关联数组
        if ($this->isAssociativeArray($data)) {
            return $this->analyzeObjectStructure($data, $name);
        }

        // 分析数组项类型
        $itemTypes = [];
        foreach ($data as $item) {
            $itemTypes[] = $this->inferTypeFromValue($item);
        }

        // 合并相同类型
        $uniqueTypes = array_unique($itemTypes);
        
        if (count($uniqueTypes) === 1) {
            $itemSchema = $this->createSchemaFromType($uniqueTypes[0]);
        } else {
            $itemSchema = ['oneOf' => array_map([$this, 'createSchemaFromType'], $uniqueTypes)];
        }

        return [
            'type' => 'array',
            'items' => $itemSchema,
        ];
    }

    /**
     * 分析对象结构
     *
     * @param array $data 数据数组
     * @param string|null $name 模式名称
     * @return array
     */
    protected function analyzeObjectStructure(array $data, ?string $name = null): array
    {
        $properties = [];
        $required = [];

        foreach ($data as $key => $value) {
            $properties[$key] = $this->createSchemaFromValue($value);
            
            // 假设所有属性都是必需的（可以根据实际情况调整）
            $required[] = $key;
        }

        $schema = [
            'type' => 'object',
            'properties' => $properties,
        ];

        if (!empty($required)) {
            $schema['required'] = $required;
        }

        return $schema;
    }

    /**
     * 分析类结构
     *
     * @param \ReflectionClass $reflection 反射类
     * @return array
     */
    protected function analyzeClassStructure(\ReflectionClass $reflection): array
    {
        $properties = [];
        $required = [];

        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $propertyName = $property->getName();
            $propertyType = $property->getType();

            if ($propertyType) {
                $properties[$propertyName] = $this->convertReflectionType($propertyType);
            } else {
                $properties[$propertyName] = ['type' => 'string'];
            }

            // 检查是否为必需属性
            if (!$property->hasDefaultValue() && !$propertyType?->allowsNull()) {
                $required[] = $propertyName;
            }
        }

        $schema = [
            'type' => 'object',
            'properties' => $properties,
        ];

        if (!empty($required)) {
            $schema['required'] = $required;
        }

        return $schema;
    }

    /**
     * 转换反射类型
     *
     * @param \ReflectionType $type 反射类型
     * @return array
     */
    protected function convertReflectionType(\ReflectionType $type): array
    {
        if ($type instanceof \ReflectionNamedType) {
            $typeName = $type->getName();
            $schema = $this->convertBasicType($typeName);
            
            if ($type->allowsNull()) {
                $schema['nullable'] = true;
            }
            
            return $schema;
        }

        if ($type instanceof \ReflectionUnionType) {
            $schemas = [];
            foreach ($type->getTypes() as $subType) {
                $schemas[] = $this->convertReflectionType($subType);
            }
            return ['oneOf' => $schemas];
        }

        return ['type' => 'string'];
    }

    /**
     * 从值创建模式
     *
     * @param mixed $value 值
     * @return array
     */
    protected function createSchemaFromValue($value): array
    {
        $type = $this->inferTypeFromValue($value);
        return $this->createSchemaFromType($type);
    }

    /**
     * 从类型创建模式
     *
     * @param string $type 类型
     * @return array
     */
    protected function createSchemaFromType(string $type): array
    {
        return [
            'type' => $this->typeMapping[$type] ?? 'string',
            'example' => $this->getExampleValue($type),
        ];
    }

    /**
     * 推断值的类型
     *
     * @param mixed $value 值
     * @return string
     */
    protected function inferTypeFromValue($value): string
    {
        if (is_int($value)) {
            return 'integer';
        }

        if (is_float($value)) {
            return 'number';
        }

        if (is_bool($value)) {
            return 'boolean';
        }

        if (is_array($value)) {
            return 'array';
        }

        if (is_object($value)) {
            return 'object';
        }

        return 'string';
    }

    /**
     * 检查是否为关联数组
     *
     * @param array $array 数组
     * @return bool
     */
    protected function isAssociativeArray(array $array): bool
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * 获取示例值
     *
     * @param string $type 类型
     * @return mixed
     */
    protected function getExampleValue(string $type)
    {
        return match ($type) {
            'int', 'integer' => 1,
            'float', 'double', 'number' => 1.0,
            'bool', 'boolean' => true,
            'array' => [],
            'object' => new \stdClass(),
            default => 'string',
        };
    }

    /**
     * 获取模式名称
     *
     * @param string $className 类名
     * @return string
     */
    protected function getSchemaName(string $className): string
    {
        return basename(str_replace('\\', '/', $className));
    }

    /**
     * 清除模式缓存
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->schemaCache = [];
    }
}
