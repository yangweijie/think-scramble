<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Exception;

/**
 * 代码分析异常类
 * 
 * 当 AST 解析、类型推断或反射分析失败时抛出
 */
class AnalysisException extends ScrambleException
{
    /**
     * 创建 AST 解析异常
     *
     * @param string $file 文件路径
     * @param string $reason 失败原因
     * @return static
     */
    public static function astParsingFailed(string $file, string $reason): static
    {
        return new static("Failed to parse AST for file '{$file}': {$reason}");
    }

    /**
     * 创建类型推断异常
     *
     * @param string $expression 表达式
     * @param string $reason 失败原因
     * @return static
     */
    public static function typeInferenceFailed(string $expression, string $reason): static
    {
        return new static("Failed to infer type for expression '{$expression}': {$reason}");
    }

    /**
     * 创建反射分析异常
     *
     * @param string $class 类名
     * @param string $reason 失败原因
     * @return static
     */
    public static function reflectionFailed(string $class, string $reason): static
    {
        return new static("Failed to analyze class '{$class}' via reflection: {$reason}");
    }
}
