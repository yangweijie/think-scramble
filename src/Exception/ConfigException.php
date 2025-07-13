<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Exception;

/**
 * 配置异常类
 * 
 * 当配置文件格式错误、缺少必要配置项或配置值无效时抛出
 */
class ConfigException extends ScrambleException
{
    /**
     * 创建配置缺失异常
     *
     * @param string $key 配置键名
     * @return static
     */
    public static function missingKey(string $key): static
    {
        return new static("Missing required configuration key: {$key}");
    }

    /**
     * 创建配置值无效异常
     *
     * @param string $key 配置键名
     * @param mixed $value 配置值
     * @param string $expected 期望的类型或格式
     * @return static
     */
    public static function invalidValue(string $key, mixed $value, string $expected): static
    {
        $type = gettype($value);
        return new static("Invalid configuration value for '{$key}': expected {$expected}, got {$type}");
    }
}
