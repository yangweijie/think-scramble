<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Analyzer\Type;

/**
 * 数组类型
 * 
 * 表示 PHP 的数组类型，支持泛型
 */
class ArrayType extends Type
{
    /**
     * 键类型
     */
    protected ?Type $keyType = null;

    /**
     * 值类型
     */
    protected ?Type $valueType = null;

    /**
     * 构造函数
     *
     * @param Type|null $keyType 键类型
     * @param Type|null $valueType 值类型
     * @param bool $nullable 是否可为空
     */
    public function __construct(?Type $keyType = null, ?Type $valueType = null, bool $nullable = false)
    {
        parent::__construct('array', $nullable);
        $this->keyType = $keyType;
        $this->valueType = $valueType;
    }

    /**
     * 获取键类型
     *
     * @return Type|null
     */
    public function getKeyType(): ?Type
    {
        return $this->keyType;
    }

    /**
     * 获取值类型
     *
     * @return Type|null
     */
    public function getValueType(): ?Type
    {
        return $this->valueType;
    }

    /**
     * 设置键类型
     *
     * @param Type $keyType
     * @return static
     */
    public function setKeyType(Type $keyType): static
    {
        $this->keyType = $keyType;
        return $this;
    }

    /**
     * 设置值类型
     *
     * @param Type $valueType
     * @return static
     */
    public function setValueType(Type $valueType): static
    {
        $this->valueType = $valueType;
        return $this;
    }

    /**
     * 获取类型字符串表示
     *
     * @return string
     */
    public function toString(): string
    {
        $type = 'array';

        if ($this->keyType && $this->valueType) {
            $type = "array<{$this->keyType->toString()}, {$this->valueType->toString()}>";
        } elseif ($this->valueType) {
            $type = "array<{$this->valueType->toString()}>";
        }

        return $this->nullable ? "?{$type}" : $type;
    }

    /**
     * 检查是否为关联数组
     *
     * @return bool
     */
    public function isAssociative(): bool
    {
        return $this->keyType && $this->keyType->getName() === 'string';
    }

    /**
     * 检查是否为索引数组
     *
     * @return bool
     */
    public function isIndexed(): bool
    {
        return $this->keyType && $this->keyType->getName() === 'int';
    }

    /**
     * 创建简单数组类型
     *
     * @param bool $nullable
     * @return static
     */
    public static function simple(bool $nullable = false): static
    {
        return new static(null, null, $nullable);
    }

    /**
     * 创建泛型数组类型
     *
     * @param Type $valueType
     * @param bool $nullable
     * @return static
     */
    public static function of(Type $valueType, bool $nullable = false): static
    {
        return new static(null, $valueType, $nullable);
    }

    /**
     * 创建关联数组类型
     *
     * @param Type $valueType
     * @param bool $nullable
     * @return static
     */
    public static function associative(Type $valueType, bool $nullable = false): static
    {
        return new static(ScalarType::string(), $valueType, $nullable);
    }

    /**
     * 创建索引数组类型
     *
     * @param Type $valueType
     * @param bool $nullable
     * @return static
     */
    public static function indexed(Type $valueType, bool $nullable = false): static
    {
        return new static(ScalarType::int(), $valueType, $nullable);
    }
}
