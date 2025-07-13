<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Exception;

/**
 * 缓存异常类
 * 
 * 处理缓存相关的异常情况
 */
class CacheException extends ScrambleException
{
    /**
     * 缓存键不存在
     */
    public const KEY_NOT_FOUND = 1001;

    /**
     * 缓存写入失败
     */
    public const WRITE_FAILED = 1002;

    /**
     * 缓存读取失败
     */
    public const READ_FAILED = 1003;

    /**
     * 缓存删除失败
     */
    public const DELETE_FAILED = 1004;

    /**
     * 缓存序列化失败
     */
    public const SERIALIZATION_FAILED = 1005;

    /**
     * 缓存反序列化失败
     */
    public const DESERIALIZATION_FAILED = 1006;

    /**
     * 缓存连接失败
     */
    public const CONNECTION_FAILED = 1007;

    /**
     * 缓存配置错误
     */
    public const CONFIG_ERROR = 1008;

    /**
     * 创建缓存键不存在异常
     *
     * @param string $key 缓存键
     * @return static
     */
    public static function keyNotFound(string $key): static
    {
        return new static("Cache key not found: {$key}", self::KEY_NOT_FOUND);
    }

    /**
     * 创建缓存写入失败异常
     *
     * @param string $key 缓存键
     * @param string $reason 失败原因
     * @return static
     */
    public static function writeFailed(string $key, string $reason = ''): static
    {
        $message = "Failed to write cache key: {$key}";
        if ($reason) {
            $message .= ". Reason: {$reason}";
        }
        return new static($message, self::WRITE_FAILED);
    }

    /**
     * 创建缓存读取失败异常
     *
     * @param string $key 缓存键
     * @param string $reason 失败原因
     * @return static
     */
    public static function readFailed(string $key, string $reason = ''): static
    {
        $message = "Failed to read cache key: {$key}";
        if ($reason) {
            $message .= ". Reason: {$reason}";
        }
        return new static($message, self::READ_FAILED);
    }

    /**
     * 创建缓存删除失败异常
     *
     * @param string $key 缓存键
     * @param string $reason 失败原因
     * @return static
     */
    public static function deleteFailed(string $key, string $reason = ''): static
    {
        $message = "Failed to delete cache key: {$key}";
        if ($reason) {
            $message .= ". Reason: {$reason}";
        }
        return new static($message, self::DELETE_FAILED);
    }

    /**
     * 创建序列化失败异常
     *
     * @param string $reason 失败原因
     * @return static
     */
    public static function serializationFailed(string $reason = ''): static
    {
        $message = "Cache serialization failed";
        if ($reason) {
            $message .= ": {$reason}";
        }
        return new static($message, self::SERIALIZATION_FAILED);
    }

    /**
     * 创建反序列化失败异常
     *
     * @param string $reason 失败原因
     * @return static
     */
    public static function deserializationFailed(string $reason = ''): static
    {
        $message = "Cache deserialization failed";
        if ($reason) {
            $message .= ": {$reason}";
        }
        return new static($message, self::DESERIALIZATION_FAILED);
    }

    /**
     * 创建连接失败异常
     *
     * @param string $driver 缓存驱动
     * @param string $reason 失败原因
     * @return static
     */
    public static function connectionFailed(string $driver, string $reason = ''): static
    {
        $message = "Failed to connect to cache driver: {$driver}";
        if ($reason) {
            $message .= ". Reason: {$reason}";
        }
        return new static($message, self::CONNECTION_FAILED);
    }

    /**
     * 创建配置错误异常
     *
     * @param string $config 配置项
     * @param string $reason 错误原因
     * @return static
     */
    public static function configError(string $config, string $reason = ''): static
    {
        $message = "Cache configuration error: {$config}";
        if ($reason) {
            $message .= ". Reason: {$reason}";
        }
        return new static($message, self::CONFIG_ERROR);
    }
}
