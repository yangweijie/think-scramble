<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Analyzer;

use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\NodeFinder;
use PhpParser\ErrorHandler;
use PhpParser\Error;
use Yangweijie\ThinkScramble\Exception\AnalysisException;

/**
 * AST 解析器
 * 
 * 封装 nikic/php-parser 的使用，提供 PHP 代码的 AST 解析功能
 */
class AstParser
{
    /**
     * PHP 解析器实例
     */
    protected Parser $parser;

    /**
     * 节点遍历器
     */
    protected NodeTraverser $traverser;

    /**
     * 错误处理器
     */
    protected ErrorHandler $errorHandler;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->parser = (new ParserFactory())->create(ParserFactory::ONLY_PHP7);
        $this->traverser = new NodeTraverser();
        $this->errorHandler = new ErrorHandler\Collecting();
    }

    /**
     * 解析 PHP 代码
     *
     * @param string $code PHP 代码
     * @return Node[]
     * @throws AnalysisException
     */
    public function parse(string $code): array
    {
        try {
            $ast = $this->parser->parse($code, $this->errorHandler);
            
            if ($ast === null) {
                throw AnalysisException::astParsingFailed('inline code', 'Parser returned null');
            }

            return $ast;
        } catch (Error $e) {
            throw AnalysisException::astParsingFailed('inline code', $e->getMessage());
        }
    }

    /**
     * 解析文件
     *
     * @param string $filePath 文件路径
     * @return Node[]
     * @throws AnalysisException
     */
    public function parseFile(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw AnalysisException::astParsingFailed($filePath, 'File not found');
        }

        $code = file_get_contents($filePath);
        if ($code === false) {
            throw AnalysisException::astParsingFailed($filePath, 'Failed to read file');
        }

        try {
            $ast = $this->parser->parse($code, $this->errorHandler);
            
            if ($ast === null) {
                throw AnalysisException::astParsingFailed($filePath, 'Parser returned null');
            }

            return $ast;
        } catch (Error $e) {
            throw AnalysisException::astParsingFailed($filePath, $e->getMessage());
        }
    }

    /**
     * 遍历 AST 节点
     *
     * @param Node[] $ast AST 节点数组
     * @param NodeVisitor $visitor 节点访问器
     * @return Node[]
     */
    public function traverse(array $ast, NodeVisitor $visitor): array
    {
        $this->traverser->addVisitor($visitor);
        $result = $this->traverser->traverse($ast);
        $this->traverser->removeVisitor($visitor);
        
        return $result;
    }

    /**
     * 查找指定类型的节点
     *
     * @param Node[] $ast AST 节点数组
     * @param string $nodeType 节点类型
     * @return Node[]
     */
    public function findNodes(array $ast, string $nodeType): array
    {
        $finder = new NodeFinder();
        return $finder->findInstanceOf($ast, $nodeType);
    }

    /**
     * 查找类定义
     *
     * @param Node[] $ast AST 节点数组
     * @return Node\Stmt\Class_[]
     */
    public function findClasses(array $ast): array
    {
        return $this->findNodes($ast, Node\Stmt\Class_::class);
    }

    /**
     * 查找方法定义
     *
     * @param Node[] $ast AST 节点数组
     * @return Node\Stmt\ClassMethod[]
     */
    public function findMethods(array $ast): array
    {
        return $this->findNodes($ast, Node\Stmt\ClassMethod::class);
    }

    /**
     * 查找函数定义
     *
     * @param Node[] $ast AST 节点数组
     * @return Node\Stmt\Function_[]
     */
    public function findFunctions(array $ast): array
    {
        return $this->findNodes($ast, Node\Stmt\Function_::class);
    }

    /**
     * 获取解析错误
     *
     * @return Error[]
     */
    public function getErrors(): array
    {
        return $this->errorHandler->getErrors();
    }

    /**
     * 检查是否有解析错误
     *
     * @return bool
     */
    public function hasErrors(): bool
    {
        return $this->errorHandler->hasErrors();
    }

    /**
     * 清除错误
     *
     * @return void
     */
    public function clearErrors(): void
    {
        $this->errorHandler->clearErrors();
    }
}
