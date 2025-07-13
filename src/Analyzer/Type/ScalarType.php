<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Analyzer\Type;

/**
 * 标量类型
 * 
 * 表示 PHP 的标量类型：int, float, string, bool
 */
class ScalarType extends Type
{
    /**
     * 支持的标量类型
     */
    public const SUPPORTED_TYPES = ['int', 'float', 'string', 'bool'];

    /**
     * 构造函数
     *
     * @param string $name
     * @param bool $nullable
     */
    public function __construct(string $name, bool $nullable = false)
    {
        if (!in_array($name, self::SUPPORTED_TYPES)) {
            throw new \InvalidArgumentException("Unsupported scalar type: {$name}");
        }

        parent::__construct($name, $nullable);
    }

    /**
     * 创建整数类型
     *
     * @param bool $nullable
     * @return static
     */
    public static function int(bool $nullable = false): static
    {
        return new static('int', $nullable);
    }

    /**
     * 创建浮点数类型
     *
     * @param bool $nullable
     * @return static
     */
    public static function float(bool $nullable = false): static
    {
        return new static('float', $nullable);
    }

    /**
     * 创建字符串类型
     *
     * @param bool $nullable
     * @return static
     */
    public static function string(bool $nullable = false): static
    {
        return new static('string', $nullable);
    }

    /**
     * 创建布尔类型
     *
     * @param bool $nullable
     * @return static
     */
    public static function bool(bool $nullable = false): static
    {
        return new static('bool', $nullable);
    }

    /**
     * 检查是否与另一个类型兼容
     *
     * @param Type $other
     * @return bool
     */
    public function isCompatibleWith(Type $other): bool
    {
        if (parent::isCompatibleWith($other)) {
            return true;
        }

        // 数字类型之间的兼容性
        if (($this->name === 'int' && $other->name === 'float') ||
            ($this->name === 'float' && $other->name === 'int')) {
            return true;
        }

        return false;
    }
}
