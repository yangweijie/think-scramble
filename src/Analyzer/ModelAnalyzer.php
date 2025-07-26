<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Analyzer;

use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use think\Model;
use Yangweijie\ThinkScramble\Exception\AnalysisException;
use Yangweijie\ThinkScramble\Cache\CacheManager;

/**
 * ThinkPHP 模型分析器
 * 
 * 自动分析模型类，提取字段、类型、关联关系等信息
 */
class ModelAnalyzer
{
    /**
     * DocBlock 解析器
     */
    protected DocBlockParser $docBlockParser;

    /**
     * 缓存管理器
     */
    protected ?CacheManager $cacheManager = null;

    /**
     * 字段类型映射
     */
    protected array $typeMapping = [
        'int' => 'integer',
        'integer' => 'integer',
        'bigint' => 'integer',
        'tinyint' => 'integer',
        'smallint' => 'integer',
        'mediumint' => 'integer',
        'float' => 'number',
        'double' => 'number',
        'decimal' => 'number',
        'numeric' => 'number',
        'varchar' => 'string',
        'char' => 'string',
        'text' => 'string',
        'longtext' => 'string',
        'mediumtext' => 'string',
        'tinytext' => 'string',
        'json' => 'object',
        'datetime' => 'string',
        'timestamp' => 'string',
        'date' => 'string',
        'time' => 'string',
        'year' => 'integer',
        'bool' => 'boolean',
        'boolean' => 'boolean',
        'enum' => 'string',
        'set' => 'array',
    ];

    /**
     * 构造函数
     */
    public function __construct(?CacheManager $cacheManager = null)
    {
        $this->docBlockParser = new DocBlockParser();
        $this->cacheManager = $cacheManager;
    }

    /**
     * 分析模型类
     *
     * @param string $className 模型类名
     * @return array
     * @throws AnalysisException
     */
    public function analyzeModel(string $className): array
    {
        // 检查缓存
        if ($this->cacheManager) {
            $cacheKey = "model_analysis:{$className}";
            $cached = $this->cacheManager->get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        try {
            if (!class_exists($className)) {
                throw new AnalysisException("Model class {$className} not found");
            }

            $reflection = new ReflectionClass($className);

            // 检查是否继承自 think\Model
            if (!$reflection->isSubclassOf(Model::class) && $className !== Model::class) {
                throw new AnalysisException("Class {$className} is not a ThinkPHP model");
            }

            $result = [
                'class' => $className,
                'table' => $this->getTableName($reflection),
                'fields' => $this->analyzeFields($reflection),
                'relations' => $this->analyzeRelations($reflection),
                'validation' => $this->analyzeValidation($reflection),
                'timestamps' => $this->analyzeTimestamps($reflection),
                'soft_delete' => $this->analyzeSoftDelete($reflection),
                'schema' => $this->generateSchema($reflection),
            ];

            // 缓存结果
            if ($this->cacheManager) {
                $this->cacheManager->set($cacheKey, $result, 3600);
            }

            return $result;

        } catch (\Exception $e) {
            throw new AnalysisException("Failed to analyze model {$className}: " . $e->getMessage());
        }
    }

    /**
     * 获取表名
     *
     * @param ReflectionClass $reflection
     * @return string
     */
    protected function getTableName(ReflectionClass $reflection): string
    {
        // 尝试获取 table 属性
        if ($reflection->hasProperty('table')) {
            $tableProperty = $reflection->getProperty('table');
            $tableProperty->setAccessible(true);
            $table = $tableProperty->getValue();
            if ($table) {
                return $table;
            }
        }

        // 根据类名推断表名
        $className = $reflection->getShortName();
        return $this->convertToTableName($className);
    }

    /**
     * 分析模型字段
     *
     * @param ReflectionClass $reflection
     * @return array
     */
    protected function analyzeFields(ReflectionClass $reflection): array
    {
        $fields = [];

        // 从 schema 属性获取字段定义
        $schema = $this->getModelSchema($reflection);
        if (!empty($schema)) {
            foreach ($schema as $field => $definition) {
                $fields[$field] = $this->parseFieldDefinition($field, $definition);
            }
        }

        // 从 type 属性获取字段类型
        $types = $this->getModelTypes($reflection);
        foreach ($types as $field => $type) {
            if (!isset($fields[$field])) {
                $fields[$field] = [];
            }
            $fields[$field] = array_merge($fields[$field], $this->parseFieldType($type));
        }

        // 从 DocBlock 注释获取字段信息
        $docFields = $this->getFieldsFromDocBlock($reflection);
        foreach ($docFields as $field => $info) {
            if (!isset($fields[$field])) {
                $fields[$field] = [];
            }
            $fields[$field] = array_merge($fields[$field], $info);
        }

        // 添加默认字段信息
        foreach ($fields as $field => &$info) {
            $info = array_merge([
                'name' => $field,
                'type' => 'string',
                'required' => false,
                'description' => '',
                'example' => null,
            ], $info);
        }

        return $fields;
    }

    /**
     * 分析模型关联关系
     *
     * @param ReflectionClass $reflection
     * @return array
     */
    protected function analyzeRelations(ReflectionClass $reflection): array
    {
        $relations = [];
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            $relationInfo = $this->analyzeRelationMethod($method);
            if ($relationInfo) {
                $relations[$method->getName()] = $relationInfo;
            }
        }

        return $relations;
    }

    /**
     * 分析关联方法
     *
     * @param ReflectionMethod $method
     * @return array|null
     */
    protected function analyzeRelationMethod(ReflectionMethod $method): ?array
    {
        $docComment = $method->getDocComment();
        if (!$docComment) {
            return null;
        }

        $parsed = $this->docBlockParser->parse($docComment);
        
        // 检查是否有关联注释
        foreach ($parsed['tags'] as $tag) {
            if (in_array($tag['name'], ['hasOne', 'hasMany', 'belongsTo', 'belongsToMany', 'morphOne', 'morphMany'])) {
                return [
                    'type' => $tag['name'],
                    'method' => $method->getName(),
                    'model' => $tag['content'] ?? '',
                    'description' => $parsed['description'] ?? '',
                ];
            }
        }

        // 通过方法体分析关联关系（简化版）
        return $this->analyzeRelationFromMethodBody($method);
    }

    /**
     * 从方法体分析关联关系
     *
     * @param ReflectionMethod $method
     * @return array|null
     */
    protected function analyzeRelationFromMethodBody(ReflectionMethod $method): ?array
    {
        // 这里可以通过 AST 分析方法体中的关联调用
        // 简化实现，仅通过方法名推断
        $methodName = $method->getName();
        
        // 常见的关联方法命名模式
        $patterns = [
            '/^get(.+)Attr$/' => 'accessor',
            '/^set(.+)Attr$/' => 'mutator',
            '/(.+)s$/' => 'hasMany',
            '/(.+)$/' => 'belongsTo',
        ];

        foreach ($patterns as $pattern => $type) {
            if (preg_match($pattern, $methodName, $matches)) {
                return [
                    'type' => $type,
                    'method' => $methodName,
                    'inferred' => true,
                ];
            }
        }

        return null;
    }

    /**
     * 分析模型验证规则
     *
     * @param ReflectionClass $reflection
     * @return array
     */
    protected function analyzeValidation(ReflectionClass $reflection): array
    {
        $validation = [];

        // 检查是否有 rule 属性
        if ($reflection->hasProperty('rule')) {
            $ruleProperty = $reflection->getProperty('rule');
            $ruleProperty->setAccessible(true);
            $rules = $ruleProperty->getValue();
            if (is_array($rules)) {
                $validation['rules'] = $rules;
            }
        }

        // 检查是否有 message 属性
        if ($reflection->hasProperty('message')) {
            $messageProperty = $reflection->getProperty('message');
            $messageProperty->setAccessible(true);
            $messages = $messageProperty->getValue();
            if (is_array($messages)) {
                $validation['messages'] = $messages;
            }
        }

        return $validation;
    }

    /**
     * 分析时间戳字段
     *
     * @param ReflectionClass $reflection
     * @return array
     */
    protected function analyzeTimestamps(ReflectionClass $reflection): array
    {
        $timestamps = [
            'enabled' => true,
            'create_time' => 'create_time',
            'update_time' => 'update_time',
        ];

        // 检查 autoWriteTimestamp 属性
        if ($reflection->hasProperty('autoWriteTimestamp')) {
            $property = $reflection->getProperty('autoWriteTimestamp');
            $property->setAccessible(true);
            $timestamps['enabled'] = $property->getValue() !== false;
        }

        // 检查自定义时间戳字段名
        if ($reflection->hasProperty('createTime')) {
            $property = $reflection->getProperty('createTime');
            $property->setAccessible(true);
            $createTime = $property->getValue();
            if ($createTime) {
                $timestamps['create_time'] = $createTime;
            }
        }

        if ($reflection->hasProperty('updateTime')) {
            $property = $reflection->getProperty('updateTime');
            $property->setAccessible(true);
            $updateTime = $property->getValue();
            if ($updateTime) {
                $timestamps['update_time'] = $updateTime;
            }
        }

        return $timestamps;
    }

    /**
     * 分析软删除
     *
     * @param ReflectionClass $reflection
     * @return array
     */
    protected function analyzeSoftDelete(ReflectionClass $reflection): array
    {
        $softDelete = [
            'enabled' => false,
            'delete_time' => 'delete_time',
        ];

        // 检查是否使用了软删除 trait
        $traits = $reflection->getTraitNames();
        if (in_array('think\\model\\concern\\SoftDelete', $traits)) {
            $softDelete['enabled'] = true;

            // 检查自定义删除时间字段
            if ($reflection->hasProperty('deleteTime')) {
                $property = $reflection->getProperty('deleteTime');
                $property->setAccessible(true);
                $deleteTime = $property->getValue();
                if ($deleteTime) {
                    $softDelete['delete_time'] = $deleteTime;
                }
            }
        }

        return $softDelete;
    }

    /**
     * 生成 OpenAPI Schema
     *
     * @param ReflectionClass $reflection
     * @return array
     */
    protected function generateSchema(ReflectionClass $reflection): array
    {
        $fields = $this->analyzeFields($reflection);
        $properties = [];
        $required = [];

        foreach ($fields as $field => $info) {
            $properties[$field] = [
                'type' => $info['type'],
                'description' => $info['description'],
            ];

            if (!empty($info['example'])) {
                $properties[$field]['example'] = $info['example'];
            }

            if ($info['required']) {
                $required[] = $field;
            }

            // 添加格式信息
            if (isset($info['format'])) {
                $properties[$field]['format'] = $info['format'];
            }

            // 添加枚举值
            if (isset($info['enum'])) {
                $properties[$field]['enum'] = $info['enum'];
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
     * 获取模型 schema 定义
     *
     * @param ReflectionClass $reflection
     * @return array
     */
    protected function getModelSchema(ReflectionClass $reflection): array
    {
        if ($reflection->hasProperty('schema')) {
            $schemaProperty = $reflection->getProperty('schema');
            $schemaProperty->setAccessible(true);
            $schema = $schemaProperty->getValue();
            return is_array($schema) ? $schema : [];
        }

        return [];
    }

    /**
     * 获取模型字段类型定义
     *
     * @param ReflectionClass $reflection
     * @return array
     */
    protected function getModelTypes(ReflectionClass $reflection): array
    {
        if ($reflection->hasProperty('type')) {
            $typeProperty = $reflection->getProperty('type');
            $typeProperty->setAccessible(true);
            $types = $typeProperty->getValue();
            return is_array($types) ? $types : [];
        }

        return [];
    }

    /**
     * 从 DocBlock 获取字段信息
     *
     * @param ReflectionClass $reflection
     * @return array
     */
    protected function getFieldsFromDocBlock(ReflectionClass $reflection): array
    {
        $fields = [];
        $docComment = $reflection->getDocComment();

        if ($docComment) {
            $parsed = $this->docBlockParser->parse($docComment);
            
            foreach ($parsed['tags'] as $tag) {
                if ($tag['name'] === 'property' || $tag['name'] === 'property-read') {
                    $fieldInfo = $this->parsePropertyTag($tag);
                    if ($fieldInfo && isset($fieldInfo['name'])) {
                        $fields[$fieldInfo['name']] = $fieldInfo;
                    }
                }
            }
        }

        return $fields;
    }

    /**
     * 解析 @property 标签
     *
     * @param array $tag
     * @return array|null
     */
    protected function parsePropertyTag(array $tag): ?array
    {
        $content = $tag['content'] ?? '';
        
        // 格式：type $variable description
        if (preg_match('/^(\S+)\s+\$(\w+)(?:\s+(.+))?$/', $content, $matches)) {
            $type = $this->mapFieldType($matches[1]);
            return [
                'name' => $matches[2],
                'type' => $type['type'],
                'format' => $type['format'] ?? null,
                'description' => trim($matches[3] ?? ''),
            ];
        }

        return null;
    }

    /**
     * 解析字段定义
     *
     * @param string $field
     * @param mixed $definition
     * @return array
     */
    protected function parseFieldDefinition(string $field, $definition): array
    {
        if (is_string($definition)) {
            return $this->parseFieldType($definition);
        }

        if (is_array($definition)) {
            $info = [];
            
            if (isset($definition['type'])) {
                $typeInfo = $this->parseFieldType($definition['type']);
                $info = array_merge($info, $typeInfo);
            }

            if (isset($definition['comment'])) {
                $info['description'] = $definition['comment'];
            }

            if (isset($definition['default'])) {
                $info['example'] = $definition['default'];
            }

            return $info;
        }

        return ['type' => 'string'];
    }

    /**
     * 解析字段类型
     *
     * @param string $type
     * @return array
     */
    protected function parseFieldType(string $type): array
    {
        return $this->mapFieldType($type);
    }

    /**
     * 映射字段类型
     *
     * @param string $type
     * @return array
     */
    protected function mapFieldType(string $type): array
    {
        $type = strtolower($type);
        
        // 处理带长度的类型，如 varchar(255)
        if (preg_match('/^(\w+)\((\d+)\)$/', $type, $matches)) {
            $baseType = $matches[1];
            $length = (int)$matches[2];
            
            $mapped = $this->typeMapping[$baseType] ?? 'string';
            $result = ['type' => $mapped];
            
            if ($mapped === 'string') {
                $result['maxLength'] = $length;
            }
            
            return $result;
        }

        // 处理日期时间类型
        if (in_array($type, ['datetime', 'timestamp'])) {
            return [
                'type' => 'string',
                'format' => 'date-time',
            ];
        }

        if ($type === 'date') {
            return [
                'type' => 'string',
                'format' => 'date',
            ];
        }

        if ($type === 'time') {
            return [
                'type' => 'string',
                'format' => 'time',
            ];
        }

        return [
            'type' => $this->typeMapping[$type] ?? 'string',
        ];
    }

    /**
     * 转换类名为表名
     *
     * @param string $className
     * @return string
     */
    protected function convertToTableName(string $className): string
    {
        // 移除 Model 后缀
        $name = preg_replace('/Model$/', '', $className);
        
        // 转换为下划线命名
        $name = preg_replace('/([A-Z])/', '_$1', $name);
        $name = trim($name, '_');
        $name = strtolower($name);
        
        return $name;
    }
}
