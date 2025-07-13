<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Exception;

/**
 * 文档生成异常类
 * 
 * 当 OpenAPI 文档生成过程中出现错误时抛出
 */
class GenerationException extends ScrambleException
{
    /**
     * 创建文档构建异常
     *
     * @param string $reason 失败原因
     * @return static
     */
    public static function documentBuildFailed(string $reason): static
    {
        return new static("Failed to build OpenAPI document: {$reason}");
    }

    /**
     * 创建模式生成异常
     *
     * @param string $type 类型名称
     * @param string $reason 失败原因
     * @return static
     */
    public static function schemaGenerationFailed(string $type, string $reason): static
    {
        return new static("Failed to generate schema for type '{$type}': {$reason}");
    }

    /**
     * 创建参数提取异常
     *
     * @param string $method 方法名称
     * @param string $reason 失败原因
     * @return static
     */
    public static function parameterExtractionFailed(string $method, string $reason): static
    {
        return new static("Failed to extract parameters for method '{$method}': {$reason}");
    }
}
