<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Exception;

/**
 * 性能异常类
 * 
 * 处理性能相关的异常情况
 */
class PerformanceException extends ScrambleException
{
    /**
     * 解析超时
     */
    public const PARSE_TIMEOUT = 2001;

    /**
     * 内存不足
     */
    public const MEMORY_EXHAUSTED = 2002;

    /**
     * 文件过大
     */
    public const FILE_TOO_LARGE = 2003;

    /**
     * 解析失败
     */
    public const PARSE_FAILED = 2004;

    /**
     * 性能阈值超出
     */
    public const THRESHOLD_EXCEEDED = 2005;

    /**
     * 资源不可用
     */
    public const RESOURCE_UNAVAILABLE = 2006;

    /**
     * 创建解析超时异常
     *
     * @param string $file 文件路径
     * @param int $timeout 超时时间
     * @return static
     */
    public static function parseTimeout(string $file, int $timeout): static
    {
        return new static("Parse timeout for file: {$file} (timeout: {$timeout}s)", self::PARSE_TIMEOUT);
    }

    /**
     * 创建内存不足异常
     *
     * @param int $currentUsage 当前内存使用
     * @param int $limit 内存限制
     * @return static
     */
    public static function memoryExhausted(int $currentUsage, int $limit): static
    {
        $currentMB = round($currentUsage / 1024 / 1024, 2);
        $limitMB = round($limit / 1024 / 1024, 2);
        
        return new static("Memory exhausted: {$currentMB}MB used, {$limitMB}MB limit", self::MEMORY_EXHAUSTED);
    }

    /**
     * 创建文件过大异常
     *
     * @param string $file 文件路径
     * @param int $size 文件大小
     * @param int $maxSize 最大允许大小
     * @return static
     */
    public static function fileTooLarge(string $file, int $size, int $maxSize): static
    {
        $sizeMB = round($size / 1024 / 1024, 2);
        $maxSizeMB = round($maxSize / 1024 / 1024, 2);
        
        return new static("File too large: {$file} ({$sizeMB}MB, max: {$maxSizeMB}MB)", self::FILE_TOO_LARGE);
    }

    /**
     * 创建解析失败异常
     *
     * @param string $file 文件路径
     * @param string $reason 失败原因
     * @return static
     */
    public static function parseFailed(string $file, string $reason = ''): static
    {
        $message = "Failed to parse file: {$file}";
        if ($reason) {
            $message .= ". Reason: {$reason}";
        }
        return new static($message, self::PARSE_FAILED);
    }

    /**
     * 创建性能阈值超出异常
     *
     * @param string $metric 性能指标
     * @param float $value 当前值
     * @param float $threshold 阈值
     * @return static
     */
    public static function thresholdExceeded(string $metric, float $value, float $threshold): static
    {
        return new static("Performance threshold exceeded for {$metric}: {$value} > {$threshold}", self::THRESHOLD_EXCEEDED);
    }

    /**
     * 创建资源不可用异常
     *
     * @param string $resource 资源名称
     * @param string $reason 不可用原因
     * @return static
     */
    public static function resourceUnavailable(string $resource, string $reason = ''): static
    {
        $message = "Resource unavailable: {$resource}";
        if ($reason) {
            $message .= ". Reason: {$reason}";
        }
        return new static($message, self::RESOURCE_UNAVAILABLE);
    }
}
