<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Analyzer\Type;

/**
 * 类型基类
 *
 * 表示 PHP 类型系统中的各种类型
 */
class Type
{
    /**
     * 类型名称
     */
    protected string $name;

    /**
     * 是否可为空
     */
    protected bool $nullable = false;

    /**
     * 构造函数
     *
     * @param string $name 类型名称
     * @param bool $nullable 是否可为空
     */
    public function __construct(string $name, bool $nullable = false)
    {
        $this->name = $name;
        $this->nullable = $nullable;
    }

    /**
     * 获取类型名称
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * 检查是否可为空
     *
     * @return bool
     */
    public function isNullable(): bool
    {
        return $this->nullable;
    }

    /**
     * 设置可为空
     *
     * @param bool $nullable
     * @return static
     */
    public function setNullable(bool $nullable = true): static
    {
        $this->nullable = $nullable;
        return $this;
    }

    /**
     * 获取类型字符串表示
     *
     * @return string
     */
    public function toString(): string
    {
        $type = $this->name;
        return $this->nullable ? "?{$type}" : $type;
    }

    /**
     * 检查是否为标量类型
     *
     * @return bool
     */
    public function isScalar(): bool
    {
        return in_array($this->name, ['int', 'float', 'string', 'bool']);
    }

    /**
     * 检查是否为复合类型
     *
     * @return bool
     */
    public function isCompound(): bool
    {
        return in_array($this->name, ['array', 'object', 'callable', 'iterable']);
    }

    /**
     * 检查是否为特殊类型
     *
     * @return bool
     */
    public function isSpecial(): bool
    {
        return in_array($this->name, ['resource', 'null', 'void', 'never']);
    }

    /**
     * 检查是否为类类型
     *
     * @return bool
     */
    public function isClass(): bool
    {
        return !$this->isScalar() && !$this->isCompound() && !$this->isSpecial();
    }

    /**
     * 检查是否与另一个类型兼容
     *
     * @param Type $other
     * @return bool
     */
    public function isCompatibleWith(Type $other): bool
    {
        // 基本兼容性检查
        if ($this->name === $other->name) {
            return true;
        }

        // null 类型兼容性
        if ($other->name === 'null' && $this->nullable) {
            return true;
        }

        // mixed 类型兼容所有类型
        if ($this->name === 'mixed' || $other->name === 'mixed') {
            return true;
        }

        return false;
    }

    /**
     * 创建类型实例
     *
     * @param string $name
     * @param bool $nullable
     * @return static
     */
    public static function create(string $name, bool $nullable = false): static
    {
        return new static($name, $nullable);
    }

    /**
     * 字符串表示
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }
}
