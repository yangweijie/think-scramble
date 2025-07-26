<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Analyzer;

use ReflectionClass;
use ReflectionMethod;
use PhpParser\Node;
use PhpParser\NodeFinder;

/**
 * 模型关系分析器
 * 
 * 分析 ThinkPHP 模型之间的关联关系
 */
class ModelRelationAnalyzer
{
    /**
     * AST 解析器
     */
    protected AstParser $astParser;

    /**
     * DocBlock 解析器
     */
    protected DocBlockParser $docBlockParser;

    /**
     * 支持的关联类型
     */
    protected array $relationTypes = [
        'hasOne' => 'one-to-one',
        'hasMany' => 'one-to-many',
        'belongsTo' => 'many-to-one',
        'belongsToMany' => 'many-to-many',
        'morphOne' => 'polymorphic-one',
        'morphMany' => 'polymorphic-many',
        'morphTo' => 'polymorphic-to',
    ];

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->astParser = new AstParser();
        $this->docBlockParser = new DocBlockParser();
    }

    /**
     * 分析模型关联关系
     *
     * @param ReflectionClass $modelReflection
     * @return array
     */
    public function analyzeRelations(ReflectionClass $modelReflection): array
    {
        $relations = [];
        $methods = $modelReflection->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            $relation = $this->analyzeRelationMethod($method);
            if ($relation) {
                $relations[$method->getName()] = $relation;
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
        // 从注释中分析
        $docRelation = $this->analyzeFromDocComment($method);
        if ($docRelation) {
            return $docRelation;
        }

        // 从方法体中分析
        $codeRelation = $this->analyzeFromMethodBody($method);
        if ($codeRelation) {
            return $codeRelation;
        }

        // 从方法名推断
        return $this->analyzeFromMethodName($method);
    }

    /**
     * 从注释中分析关联关系
     *
     * @param ReflectionMethod $method
     * @return array|null
     */
    protected function analyzeFromDocComment(ReflectionMethod $method): ?array
    {
        $docComment = $method->getDocComment();
        if (!$docComment) {
            return null;
        }

        $parsed = $this->docBlockParser->parse($docComment);

        // 检查关联注释
        foreach ($parsed['tags'] as $tag) {
            if (array_key_exists($tag['name'], $this->relationTypes)) {
                return $this->parseRelationTag($tag, $method);
            }

            // 检查 @return 标签
            if ($tag['name'] === 'return') {
                $returnType = $tag['type'] ?? '';
                if ($this->isRelationReturnType($returnType)) {
                    return $this->parseReturnTypeRelation($returnType, $method, $parsed);
                }
            }
        }

        return null;
    }

    /**
     * 从方法体中分析关联关系
     *
     * @param ReflectionMethod $method
     * @return array|null
     */
    protected function analyzeFromMethodBody(ReflectionMethod $method): ?array
    {
        try {
            $filename = $method->getFileName();
            if (!$filename) {
                return null;
            }

            $ast = $this->astParser->parseFile($filename);
            $methodNode = $this->findMethodNode($ast, $method->getName());

            if ($methodNode) {
                return $this->analyzeMethodNode($methodNode, $method);
            }
        } catch (\Exception $e) {
            // 忽略解析错误
        }

        return null;
    }

    /**
     * 从方法名推断关联关系
     *
     * @param ReflectionMethod $method
     * @return array|null
     */
    protected function analyzeFromMethodName(ReflectionMethod $method): ?array
    {
        $methodName = $method->getName();

        // 跳过明显不是关联的方法
        if ($this->shouldSkipMethod($methodName)) {
            return null;
        }

        // 基于命名约定推断关联类型
        $patterns = [
            '/^(.+)s$/' => 'hasMany',      // users() -> hasMany
            '/^(.+)$/' => 'belongsTo',     // user() -> belongsTo
        ];

        foreach ($patterns as $pattern => $relationType) {
            if (preg_match($pattern, $methodName, $matches)) {
                $relatedModel = $this->guessRelatedModel($matches[1]);
                
                return [
                    'type' => $relationType,
                    'method' => $methodName,
                    'related_model' => $relatedModel,
                    'inferred' => true,
                    'confidence' => 'low',
                ];
            }
        }

        return null;
    }

    /**
     * 解析关联标签
     *
     * @param array $tag
     * @param ReflectionMethod $method
     * @return array
     */
    protected function parseRelationTag(array $tag, ReflectionMethod $method): array
    {
        $content = $tag['content'] ?? '';
        $relationType = $tag['name'];

        // 解析关联模型和参数
        $parts = explode(',', $content);
        $relatedModel = trim($parts[0] ?? '', '"\'');
        
        $relation = [
            'type' => $relationType,
            'method' => $method->getName(),
            'related_model' => $relatedModel,
            'source' => 'annotation',
            'confidence' => 'high',
        ];

        // 解析额外参数
        if (count($parts) > 1) {
            $relation['foreign_key'] = trim($parts[1] ?? '', '"\'');
        }
        if (count($parts) > 2) {
            $relation['local_key'] = trim($parts[2] ?? '', '"\'');
        }

        return $relation;
    }

    /**
     * 解析返回类型关联
     *
     * @param string $returnType
     * @param ReflectionMethod $method
     * @param array $parsed
     * @return array
     */
    protected function parseReturnTypeRelation(string $returnType, ReflectionMethod $method, array $parsed): array
    {
        $relationType = $this->inferRelationTypeFromReturn($returnType);
        $relatedModel = $this->extractModelFromReturnType($returnType);

        return [
            'type' => $relationType,
            'method' => $method->getName(),
            'related_model' => $relatedModel,
            'source' => 'return_type',
            'confidence' => 'medium',
            'description' => $parsed['description'] ?? '',
        ];
    }

    /**
     * 查找方法节点
     *
     * @param array $ast
     * @param string $methodName
     * @return Node\Stmt\ClassMethod|null
     */
    protected function findMethodNode(array $ast, string $methodName): ?Node\Stmt\ClassMethod
    {
        $finder = new NodeFinder();
        $methods = $finder->findInstanceOf($ast, Node\Stmt\ClassMethod::class);

        foreach ($methods as $method) {
            if ($method->name->name === $methodName) {
                return $method;
            }
        }

        return null;
    }

    /**
     * 分析方法节点
     *
     * @param Node\Stmt\ClassMethod $methodNode
     * @param ReflectionMethod $method
     * @return array|null
     */
    protected function analyzeMethodNode(Node\Stmt\ClassMethod $methodNode, ReflectionMethod $method): ?array
    {
        $finder = new NodeFinder();
        
        // 查找关联方法调用
        $methodCalls = $finder->findInstanceOf($methodNode, Node\Expr\MethodCall::class);

        foreach ($methodCalls as $call) {
            if ($this->isRelationMethodCall($call)) {
                return $this->parseRelationMethodCall($call, $method);
            }
        }

        return null;
    }

    /**
     * 检查是否为关联方法调用
     *
     * @param Node\Expr\MethodCall $call
     * @return bool
     */
    protected function isRelationMethodCall(Node\Expr\MethodCall $call): bool
    {
        if (!($call->name instanceof Node\Identifier)) {
            return false;
        }

        $methodName = $call->name->name;
        return array_key_exists($methodName, $this->relationTypes);
    }

    /**
     * 解析关联方法调用
     *
     * @param Node\Expr\MethodCall $call
     * @param ReflectionMethod $method
     * @return array
     */
    protected function parseRelationMethodCall(Node\Expr\MethodCall $call, ReflectionMethod $method): array
    {
        $relationType = $call->name->name;
        $args = $call->args;

        $relation = [
            'type' => $relationType,
            'method' => $method->getName(),
            'source' => 'method_call',
            'confidence' => 'high',
        ];

        // 解析参数
        if (!empty($args[0])) {
            $relation['related_model'] = $this->extractStringValue($args[0]->value);
        }

        if (!empty($args[1])) {
            $relation['foreign_key'] = $this->extractStringValue($args[1]->value);
        }

        if (!empty($args[2])) {
            $relation['local_key'] = $this->extractStringValue($args[2]->value);
        }

        return $relation;
    }

    /**
     * 提取字符串值
     *
     * @param Node $node
     * @return string|null
     */
    protected function extractStringValue(Node $node): ?string
    {
        if ($node instanceof Node\Scalar\String_) {
            return $node->value;
        }

        return null;
    }

    /**
     * 检查是否为关联返回类型
     *
     * @param string $returnType
     * @return bool
     */
    protected function isRelationReturnType(string $returnType): bool
    {
        $relationClasses = [
            'HasOne', 'HasMany', 'BelongsTo', 'BelongsToMany',
            'MorphOne', 'MorphMany', 'MorphTo',
        ];

        foreach ($relationClasses as $class) {
            if (strpos($returnType, $class) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * 从返回类型推断关联类型
     *
     * @param string $returnType
     * @return string
     */
    protected function inferRelationTypeFromReturn(string $returnType): string
    {
        $mapping = [
            'HasOne' => 'hasOne',
            'HasMany' => 'hasMany',
            'BelongsTo' => 'belongsTo',
            'BelongsToMany' => 'belongsToMany',
            'MorphOne' => 'morphOne',
            'MorphMany' => 'morphMany',
            'MorphTo' => 'morphTo',
        ];

        foreach ($mapping as $class => $type) {
            if (strpos($returnType, $class) !== false) {
                return $type;
            }
        }

        return 'unknown';
    }

    /**
     * 从返回类型提取模型名
     *
     * @param string $returnType
     * @return string
     */
    protected function extractModelFromReturnType(string $returnType): string
    {
        // 尝试从泛型中提取：HasMany<User>
        if (preg_match('/<([^>]+)>/', $returnType, $matches)) {
            return $matches[1];
        }

        // 尝试从类名中提取
        if (preg_match('/\\\\([^\\\\]+)$/', $returnType, $matches)) {
            return $matches[1];
        }

        return '';
    }

    /**
     * 检查是否应该跳过方法
     *
     * @param string $methodName
     * @return bool
     */
    protected function shouldSkipMethod(string $methodName): bool
    {
        $skipPatterns = [
            '/^get.+Attr$/',     // 访问器
            '/^set.+Attr$/',     // 修改器
            '/^scope.+$/',       // 查询范围
            '/^__/',             // 魔术方法
            '/^(save|delete|update|create|find|where|order|limit|group|having)/',  // 模型方法
        ];

        foreach ($skipPatterns as $pattern) {
            if (preg_match($pattern, $methodName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 猜测关联模型名
     *
     * @param string $methodName
     * @return string
     */
    protected function guessRelatedModel(string $methodName): string
    {
        // 转换为 PascalCase
        $modelName = str_replace('_', ' ', $methodName);
        $modelName = ucwords($modelName);
        $modelName = str_replace(' ', '', $modelName);

        return $modelName;
    }

    /**
     * 生成关联的 OpenAPI Schema 引用
     *
     * @param array $relation
     * @return array
     */
    public function generateRelationSchema(array $relation): array
    {
        $relatedModel = $relation['related_model'] ?? '';
        
        if (empty($relatedModel)) {
            return ['type' => 'object'];
        }

        $schema = [
            'description' => $this->getRelationDescription($relation),
        ];

        switch ($relation['type']) {
            case 'hasOne':
            case 'belongsTo':
            case 'morphOne':
            case 'morphTo':
                $schema['$ref'] = "#/components/schemas/{$relatedModel}";
                break;

            case 'hasMany':
            case 'belongsToMany':
            case 'morphMany':
                $schema = [
                    'type' => 'array',
                    'items' => [
                        '$ref' => "#/components/schemas/{$relatedModel}"
                    ],
                    'description' => $this->getRelationDescription($relation),
                ];
                break;

            default:
                $schema = ['type' => 'object'];
                break;
        }

        return $schema;
    }

    /**
     * 获取关联描述
     *
     * @param array $relation
     * @return string
     */
    protected function getRelationDescription(array $relation): string
    {
        $type = $relation['type'];
        $relatedModel = $relation['related_model'] ?? '';
        
        $descriptions = [
            'hasOne' => "一对一关联到 {$relatedModel}",
            'hasMany' => "一对多关联到 {$relatedModel}",
            'belongsTo' => "多对一关联到 {$relatedModel}",
            'belongsToMany' => "多对多关联到 {$relatedModel}",
            'morphOne' => "多态一对一关联到 {$relatedModel}",
            'morphMany' => "多态一对多关联到 {$relatedModel}",
            'morphTo' => "多态关联",
        ];

        return $descriptions[$type] ?? "关联到 {$relatedModel}";
    }
}
