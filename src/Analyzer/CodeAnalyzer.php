<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Analyzer;

use PhpParser\Node;
use Yangweijie\ThinkScramble\Contracts\AnalyzerInterface;
use Yangweijie\ThinkScramble\Analyzer\Type\Type;
use Yangweijie\ThinkScramble\Exception\AnalysisException;

/**
 * 代码分析器
 * 
 * 综合使用 AST 解析、类型推断和反射分析的主要分析器
 */
class CodeAnalyzer implements AnalyzerInterface
{
    /**
     * AST 解析器
     */
    protected AstParser $astParser;

    /**
     * 类型推断引擎
     */
    protected TypeInference $typeInference;

    /**
     * 反射分析器
     */
    protected ReflectionAnalyzer $reflectionAnalyzer;

    /**
     * DocBlock 解析器
     */
    protected DocBlockParser $docBlockParser;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->astParser = new AstParser();
        $this->typeInference = new TypeInference($this->astParser);
        $this->reflectionAnalyzer = new ReflectionAnalyzer();
        $this->docBlockParser = new DocBlockParser();
    }

    /**
     * 分析指定的文件或类
     *
     * @param string $target 分析目标（文件路径或类名）
     * @return array 分析结果
     * @throws AnalysisException
     */
    public function analyze(string $target): array
    {
        if (file_exists($target)) {
            return $this->analyzeFile($target);
        }

        if (class_exists($target)) {
            return $this->analyzeClass($target);
        }

        throw new AnalysisException("Target not found: {$target}");
    }

    /**
     * 检查是否支持分析指定的目标
     *
     * @param string $target 分析目标
     * @return bool
     */
    public function supports(string $target): bool
    {
        return file_exists($target) || class_exists($target);
    }

    /**
     * 分析文件
     *
     * @param string $filePath 文件路径
     * @return array
     * @throws AnalysisException
     */
    public function analyzeFile(string $filePath): array
    {
        $ast = $this->astParser->parseFile($filePath);
        
        $result = [
            'file' => $filePath,
            'classes' => [],
            'functions' => [],
            'errors' => [],
        ];

        // 分析类
        $classes = $this->astParser->findClasses($ast);
        foreach ($classes as $classNode) {
            $className = $this->getFullClassName($classNode);
            if ($className) {
                $result['classes'][$className] = $this->analyzeClassNode($classNode, $className);
            }
        }

        // 分析函数
        $functions = $this->astParser->findFunctions($ast);
        foreach ($functions as $functionNode) {
            $functionName = $functionNode->name->name;
            $result['functions'][$functionName] = $this->analyzeFunctionNode($functionNode);
        }

        // 收集错误
        if ($this->astParser->hasErrors()) {
            $result['errors'] = array_map(
                fn($error) => $error->getMessage(),
                $this->astParser->getErrors()
            );
        }

        return $result;
    }

    /**
     * 分析类
     *
     * @param string $className 类名
     * @return array
     * @throws AnalysisException
     */
    public function analyzeClass(string $className): array
    {
        // 使用反射分析
        $reflectionData = $this->reflectionAnalyzer->analyzeClass($className);
        
        // 尝试获取 AST 信息
        $astData = [];
        try {
            $reflection = new \ReflectionClass($className);
            $fileName = $reflection->getFileName();
            
            if ($fileName && file_exists($fileName)) {
                $ast = $this->astParser->parseFile($fileName);
                $classes = $this->astParser->findClasses($ast);
                
                foreach ($classes as $classNode) {
                    $nodeClassName = $this->getFullClassName($classNode);
                    if ($nodeClassName === $className) {
                        $astData = $this->analyzeClassNode($classNode, $className);
                        break;
                    }
                }
            }
        } catch (\Exception $e) {
            // 忽略 AST 分析错误，使用反射数据
        }

        // 合并反射和 AST 数据
        return array_merge($reflectionData, $astData);
    }

    /**
     * 分析类节点
     *
     * @param Node\Stmt\Class_ $classNode
     * @param string $className
     * @return array
     */
    protected function analyzeClassNode(Node\Stmt\Class_ $classNode, string $className): array
    {
        $result = [
            'name' => $className,
            'methods' => [],
            'properties' => [],
            'doc_comment' => $classNode->getDocComment() ? $classNode->getDocComment()->getText() : null,
        ];

        // 分析方法
        foreach ($classNode->getMethods() as $methodNode) {
            $methodName = $methodNode->name->name;
            $result['methods'][$methodName] = $this->analyzeMethodNode($methodNode, $className);
        }

        // 分析属性
        foreach ($classNode->getProperties() as $propertyNode) {
            foreach ($propertyNode->props as $prop) {
                $propertyName = $prop->name->name;
                $result['properties'][$propertyName] = $this->analyzePropertyNode($propertyNode, $prop);
            }
        }

        return $result;
    }

    /**
     * 分析方法节点
     *
     * @param Node\Stmt\ClassMethod $methodNode
     * @param string $className
     * @return array
     */
    protected function analyzeMethodNode(Node\Stmt\ClassMethod $methodNode, string $className): array
    {
        $methodName = $methodNode->name->name;
        
        $result = [
            'name' => $methodName,
            'class' => $className,
            'visibility' => $this->getNodeVisibility($methodNode),
            'is_static' => $methodNode->isStatic(),
            'is_abstract' => $methodNode->isAbstract(),
            'is_final' => $methodNode->isFinal(),
            'parameters' => [],
            'return_type' => null,
            'doc_comment' => $methodNode->getDocComment() ? $methodNode->getDocComment()->getText() : null,
        ];

        // 分析参数
        foreach ($methodNode->getParams() as $param) {
            $result['parameters'][] = $this->analyzeParameterNode($param);
        }

        // 分析返回类型
        if ($methodNode->getReturnType()) {
            $result['return_type'] = $this->typeInference->inferType($methodNode->getReturnType());
        }

        // 从 DocBlock 获取类型信息
        if ($result['doc_comment']) {
            $docReturnType = $this->docBlockParser->parseReturnType($result['doc_comment']);
            if ($docReturnType && !$result['return_type']) {
                $result['return_type'] = $docReturnType;
            }

            // 更新参数类型信息
            foreach ($result['parameters'] as &$param) {
                if (!$param['type']) {
                    $docType = $this->docBlockParser->parseParameterType($result['doc_comment'], $param['name']);
                    if ($docType) {
                        $param['type'] = $docType;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * 分析函数节点
     *
     * @param Node\Stmt\Function_ $functionNode
     * @return array
     */
    protected function analyzeFunctionNode(Node\Stmt\Function_ $functionNode): array
    {
        $result = [
            'name' => $functionNode->name->name,
            'parameters' => [],
            'return_type' => null,
            'doc_comment' => $functionNode->getDocComment() ? $functionNode->getDocComment()->getText() : null,
        ];

        // 分析参数
        foreach ($functionNode->getParams() as $param) {
            $result['parameters'][] = $this->analyzeParameterNode($param);
        }

        // 分析返回类型
        if ($functionNode->getReturnType()) {
            $result['return_type'] = $this->typeInference->inferType($functionNode->getReturnType());
        }

        return $result;
    }

    /**
     * 分析参数节点
     *
     * @param Node\Param $param
     * @return array
     */
    protected function analyzeParameterNode(Node\Param $param): array
    {
        $result = [
            'name' => $param->var->name,
            'type' => null,
            'is_optional' => $param->default !== null,
            'is_variadic' => $param->variadic,
            'is_passed_by_reference' => $param->byRef,
            'default_value' => null,
        ];

        // 分析类型
        if ($param->type) {
            $result['type'] = $this->typeInference->inferType($param->type);
        }

        // 分析默认值
        if ($param->default) {
            try {
                $result['default_value'] = $this->typeInference->inferType($param->default);
            } catch (\Exception $e) {
                // 忽略默认值分析错误
            }
        }

        return $result;
    }

    /**
     * 分析属性节点
     *
     * @param Node\Stmt\Property $propertyNode
     * @param Node\Stmt\PropertyProperty $prop
     * @return array
     */
    protected function analyzePropertyNode(Node\Stmt\Property $propertyNode, Node\Stmt\PropertyProperty $prop): array
    {
        $result = [
            'name' => $prop->name->name,
            'visibility' => $this->getNodeVisibility($propertyNode),
            'is_static' => $propertyNode->isStatic(),
            'type' => null,
            'default_value' => null,
            'doc_comment' => $propertyNode->getDocComment() ? $propertyNode->getDocComment()->getText() : null,
        ];

        // 分析类型
        if ($propertyNode->type) {
            $result['type'] = $this->typeInference->inferType($propertyNode->type);
        }

        // 分析默认值
        if ($prop->default) {
            try {
                $result['default_value'] = $this->typeInference->inferType($prop->default);
            } catch (\Exception $e) {
                // 忽略默认值分析错误
            }
        }

        return $result;
    }

    /**
     * 获取完整类名
     *
     * @param Node\Stmt\Class_ $classNode
     * @return string|null
     */
    protected function getFullClassName(Node\Stmt\Class_ $classNode): ?string
    {
        if (!$classNode->name) {
            return null;
        }

        $className = $classNode->name->name;
        
        // 如果有命名空间，需要获取完整类名
        // 这里简化处理，实际应该通过 NameResolver 获取
        return $className;
    }

    /**
     * 获取节点可见性
     *
     * @param Node\Stmt\ClassMethod|Node\Stmt\Property $node
     * @return string
     */
    protected function getNodeVisibility($node): string
    {
        if ($node->isPublic()) {
            return 'public';
        }

        if ($node->isProtected()) {
            return 'protected';
        }

        return 'private';
    }

    /**
     * 清除所有缓存
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->typeInference->clearCache();
        $this->reflectionAnalyzer->clearCache();
    }
}
