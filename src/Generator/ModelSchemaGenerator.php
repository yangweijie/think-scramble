<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Generator;

use Yangweijie\ThinkScramble\Analyzer\ModelAnalyzer;
use Yangweijie\ThinkScramble\Analyzer\ModelRelationAnalyzer;
use Yangweijie\ThinkScramble\Contracts\ConfigInterface;
use Yangweijie\ThinkScramble\Exception\GenerationException;

/**
 * 模型 Schema 生成器
 * 
 * 根据 ThinkPHP 模型生成 OpenAPI Schema 定义
 */
class ModelSchemaGenerator
{
    /**
     * 配置接口
     */
    protected ConfigInterface $config;

    /**
     * 模型分析器
     */
    protected ModelAnalyzer $modelAnalyzer;

    /**
     * 模型关系分析器
     */
    protected ModelRelationAnalyzer $relationAnalyzer;

    /**
     * 已分析的模型缓存
     */
    protected array $analyzedModels = [];

    /**
     * 构造函数
     *
     * @param ConfigInterface $config
     */
    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
        $this->modelAnalyzer = new ModelAnalyzer();
        $this->relationAnalyzer = new ModelRelationAnalyzer();
    }

    /**
     * 生成模型的 OpenAPI Schema
     *
     * @param string $modelClass 模型类名
     * @param array $options 选项
     * @return array
     * @throws GenerationException
     */
    public function generateSchema(string $modelClass, array $options = []): array
    {
        try {
            // 检查缓存
            if (isset($this->analyzedModels[$modelClass])) {
                return $this->buildSchema($this->analyzedModels[$modelClass], $options);
            }

            // 分析模型
            $modelInfo = $this->modelAnalyzer->analyzeModel($modelClass);
            $this->analyzedModels[$modelClass] = $modelInfo;

            return $this->buildSchema($modelInfo, $options);

        } catch (\Exception $e) {
            throw new GenerationException("Failed to generate schema for model {$modelClass}: " . $e->getMessage());
        }
    }

    /**
     * 构建 Schema
     *
     * @param array $modelInfo 模型信息
     * @param array $options 选项
     * @return array
     */
    protected function buildSchema(array $modelInfo, array $options = []): array
    {
        $includeRelations = $options['include_relations'] ?? false;
        $includeTimestamps = $options['include_timestamps'] ?? true;
        $includeHidden = $options['include_hidden'] ?? false;
        $fieldsFilter = $options['fields'] ?? null;

        $schema = [
            'type' => 'object',
            'title' => $this->getModelTitle($modelInfo),
            'description' => $this->getModelDescription($modelInfo),
            'properties' => [],
        ];

        $required = [];

        // 添加基本字段
        foreach ($modelInfo['fields'] as $field => $fieldInfo) {
            // 应用字段过滤
            if ($fieldsFilter && !in_array($field, $fieldsFilter)) {
                continue;
            }

            // 跳过隐藏字段
            if (!$includeHidden && $this->isHiddenField($field, $modelInfo)) {
                continue;
            }

            $schema['properties'][$field] = $this->buildFieldSchema($fieldInfo);

            if ($fieldInfo['required']) {
                $required[] = $field;
            }
        }

        // 添加时间戳字段
        if ($includeTimestamps && $modelInfo['timestamps']['enabled']) {
            $this->addTimestampFields($schema, $modelInfo['timestamps']);
        }

        // 添加软删除字段
        if ($modelInfo['soft_delete']['enabled']) {
            $this->addSoftDeleteField($schema, $modelInfo['soft_delete']);
        }

        // 添加关联字段
        if ($includeRelations) {
            $this->addRelationFields($schema, $modelInfo['relations']);
        }

        // 设置必填字段
        if (!empty($required)) {
            $schema['required'] = $required;
        }

        // 添加示例
        $schema['example'] = $this->generateExample($modelInfo, $options);

        return $schema;
    }

    /**
     * 构建字段 Schema
     *
     * @param array $fieldInfo 字段信息
     * @return array
     */
    protected function buildFieldSchema(array $fieldInfo): array
    {
        $schema = [
            'type' => $fieldInfo['type'],
        ];

        if (!empty($fieldInfo['description'])) {
            $schema['description'] = $fieldInfo['description'];
        }

        if (isset($fieldInfo['format'])) {
            $schema['format'] = $fieldInfo['format'];
        }

        if (isset($fieldInfo['maxLength'])) {
            $schema['maxLength'] = $fieldInfo['maxLength'];
        }

        if (isset($fieldInfo['minLength'])) {
            $schema['minLength'] = $fieldInfo['minLength'];
        }

        if (isset($fieldInfo['minimum'])) {
            $schema['minimum'] = $fieldInfo['minimum'];
        }

        if (isset($fieldInfo['maximum'])) {
            $schema['maximum'] = $fieldInfo['maximum'];
        }

        if (isset($fieldInfo['enum'])) {
            $schema['enum'] = $fieldInfo['enum'];
        }

        if (isset($fieldInfo['example'])) {
            $schema['example'] = $fieldInfo['example'];
        }

        return $schema;
    }

    /**
     * 添加时间戳字段
     *
     * @param array &$schema
     * @param array $timestamps
     */
    protected function addTimestampFields(array &$schema, array $timestamps): void
    {
        if (!empty($timestamps['create_time'])) {
            $schema['properties'][$timestamps['create_time']] = [
                'type' => 'string',
                'format' => 'date-time',
                'description' => '创建时间',
                'example' => '2024-01-01T12:00:00Z',
            ];
        }

        if (!empty($timestamps['update_time'])) {
            $schema['properties'][$timestamps['update_time']] = [
                'type' => 'string',
                'format' => 'date-time',
                'description' => '更新时间',
                'example' => '2024-01-01T12:00:00Z',
            ];
        }
    }

    /**
     * 添加软删除字段
     *
     * @param array &$schema
     * @param array $softDelete
     */
    protected function addSoftDeleteField(array &$schema, array $softDelete): void
    {
        if (!empty($softDelete['delete_time'])) {
            $schema['properties'][$softDelete['delete_time']] = [
                'type' => 'string',
                'format' => 'date-time',
                'description' => '删除时间',
                'nullable' => true,
                'example' => null,
            ];
        }
    }

    /**
     * 添加关联字段
     *
     * @param array &$schema
     * @param array $relations
     */
    protected function addRelationFields(array &$schema, array $relations): void
    {
        foreach ($relations as $relationName => $relation) {
            if ($relation['inferred'] ?? false) {
                // 跳过推断的关联，置信度较低
                continue;
            }

            $relationSchema = $this->relationAnalyzer->generateRelationSchema($relation);
            $schema['properties'][$relationName] = $relationSchema;
        }
    }

    /**
     * 获取模型标题
     *
     * @param array $modelInfo
     * @return string
     */
    protected function getModelTitle(array $modelInfo): string
    {
        $className = $modelInfo['class'];
        $lastBackslash = strrpos($className, '\\');
        $shortName = $lastBackslash !== false ? substr($className, $lastBackslash + 1) : $className;

        // 移除 Model 后缀
        return preg_replace('/Model$/', '', $shortName);
    }

    /**
     * 获取模型描述
     *
     * @param array $modelInfo
     * @return string
     */
    protected function getModelDescription(array $modelInfo): string
    {
        $title = $this->getModelTitle($modelInfo);
        $table = $modelInfo['table'];
        
        return "{$title} 模型 (表: {$table})";
    }

    /**
     * 检查是否为隐藏字段
     *
     * @param string $field
     * @param array $modelInfo
     * @return bool
     */
    protected function isHiddenField(string $field, array $modelInfo): bool
    {
        // 常见的隐藏字段
        $commonHidden = ['password', 'token', 'secret', 'salt'];
        
        return in_array($field, $commonHidden);
    }

    /**
     * 生成示例数据
     *
     * @param array $modelInfo
     * @param array $options
     * @return array
     */
    protected function generateExample(array $modelInfo, array $options = []): array
    {
        $example = [];

        foreach ($modelInfo['fields'] as $field => $fieldInfo) {
            $example[$field] = $this->generateFieldExample($fieldInfo);
        }

        // 添加时间戳示例
        if ($options['include_timestamps'] ?? true) {
            if ($modelInfo['timestamps']['enabled']) {
                $example[$modelInfo['timestamps']['create_time']] = '2024-01-01T12:00:00Z';
                $example[$modelInfo['timestamps']['update_time']] = '2024-01-01T12:00:00Z';
            }
        }

        return $example;
    }

    /**
     * 生成字段示例值
     *
     * @param array $fieldInfo
     * @return mixed
     */
    protected function generateFieldExample(array $fieldInfo)
    {
        if (isset($fieldInfo['example'])) {
            return $fieldInfo['example'];
        }

        $type = $fieldInfo['type'];
        $name = $fieldInfo['name'] ?? '';

        switch ($type) {
            case 'integer':
                return $this->generateIntegerExample($name);
            case 'number':
                return $this->generateNumberExample($name);
            case 'boolean':
                return true;
            case 'array':
                return [];
            case 'object':
                return new \stdClass();
            default:
                return $this->generateStringExample($name, $fieldInfo);
        }
    }

    /**
     * 生成整数示例
     *
     * @param string $fieldName
     * @return int
     */
    protected function generateIntegerExample(string $fieldName): int
    {
        $patterns = [
            '/id$/' => 1,
            '/count$/' => 10,
            '/age$/' => 25,
            '/year$/' => 2024,
            '/status$/' => 1,
        ];

        foreach ($patterns as $pattern => $value) {
            if (preg_match($pattern, $fieldName)) {
                return $value;
            }
        }

        return 1;
    }

    /**
     * 生成数字示例
     *
     * @param string $fieldName
     * @return float
     */
    protected function generateNumberExample(string $fieldName): float
    {
        $patterns = [
            '/price$/' => 99.99,
            '/amount$/' => 100.00,
            '/rate$/' => 0.05,
            '/score$/' => 85.5,
        ];

        foreach ($patterns as $pattern => $value) {
            if (preg_match($pattern, $fieldName)) {
                return $value;
            }
        }

        return 1.0;
    }

    /**
     * 生成字符串示例
     *
     * @param string $fieldName
     * @param array $fieldInfo
     * @return string
     */
    protected function generateStringExample(string $fieldName, array $fieldInfo): string
    {
        // 检查格式
        if (isset($fieldInfo['format'])) {
            switch ($fieldInfo['format']) {
                case 'email':
                    return 'user@example.com';
                case 'date':
                    return '2024-01-01';
                case 'date-time':
                    return '2024-01-01T12:00:00Z';
                case 'uri':
                    return 'https://example.com';
            }
        }

        // 基于字段名生成
        $patterns = [
            '/name$/' => '示例名称',
            '/title$/' => '示例标题',
            '/email$/' => 'user@example.com',
            '/phone$/' => '13800138000',
            '/address$/' => '示例地址',
            '/description$/' => '这是一个示例描述',
            '/content$/' => '示例内容',
            '/url$/' => 'https://example.com',
            '/code$/' => 'EXAMPLE001',
        ];

        foreach ($patterns as $pattern => $value) {
            if (preg_match($pattern, $fieldName)) {
                return $value;
            }
        }

        return '示例值';
    }

    /**
     * 批量生成多个模型的 Schema
     *
     * @param array $modelClasses 模型类名数组
     * @param array $options 选项
     * @return array
     */
    public function generateMultipleSchemas(array $modelClasses, array $options = []): array
    {
        $schemas = [];

        foreach ($modelClasses as $modelClass) {
            try {
                $schema = $this->generateSchema($modelClass, $options);
                $schemaName = $this->getModelTitle($this->analyzedModels[$modelClass]);
                $schemas[$schemaName] = $schema;
            } catch (GenerationException $e) {
                // 记录错误但继续处理其他模型
                continue;
            }
        }

        return $schemas;
    }

    /**
     * 映射字段类型
     *
     * @param string $type
     * @return array
     */
    protected function mapFieldType(string $type): array
    {
        $typeMapping = [
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

        $type = strtolower($type);

        // 处理带长度的类型，如 varchar(255)
        if (preg_match('/^(\w+)\((\d+)\)$/', $type, $matches)) {
            $baseType = $matches[1];
            $length = (int)$matches[2];

            $mapped = $typeMapping[$baseType] ?? 'string';
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
            'type' => $typeMapping[$type] ?? 'string',
        ];
    }

    /**
     * 清除缓存
     */
    public function clearCache(): void
    {
        $this->analyzedModels = [];
    }
}
