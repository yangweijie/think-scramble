<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Analyzer\Type;

/**
 * 联合类型
 * 
 * 表示 PHP 8.0+ 的联合类型，如 string|int
 */
class UnionType extends Type
{
    /**
     * 类型列表
     *
     * @var Type[]
     */
    protected array $types = [];

    /**
     * 构造函数
     *
     * @param Type[] $types 类型列表
     */
    public function __construct(array $types)
    {
        if (count($types) < 2) {
            throw new \InvalidArgumentException('Union type must have at least 2 types');
        }

        $this->types = $types;
        
        // 联合类型的名称是所有类型名称的组合
        $names = array_map(fn(Type $type) => $type->getName(), $types);
        parent::__construct(implode('|', $names), false);
    }

    /**
     * 获取类型列表
     *
     * @return Type[]
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * 添加类型
     *
     * @param Type $type
     * @return static
     */
    public function addType(Type $type): static
    {
        $this->types[] = $type;
        
        // 重新计算名称
        $names = array_map(fn(Type $t) => $t->getName(), $this->types);
        $this->name = implode('|', $names);
        
        return $this;
    }

    /**
     * 检查是否包含指定类型
     *
     * @param string $typeName
     * @return bool
     */
    public function hasType(string $typeName): bool
    {
        foreach ($this->types as $type) {
            if ($type->getName() === $typeName) {
                return true;
            }
        }
        return false;
    }

    /**
     * 检查是否包含 null 类型
     *
     * @return bool
     */
    public function hasNull(): bool
    {
        return $this->hasType('null');
    }

    /**
     * 获取类型字符串表示
     *
     * @return string
     */
    public function toString(): string
    {
        $typeStrings = array_map(fn(Type $type) => $type->toString(), $this->types);
        return implode('|', $typeStrings);
    }

    /**
     * 检查是否与另一个类型兼容
     *
     * @param Type $other
     * @return bool
     */
    public function isCompatibleWith(Type $other): bool
    {
        // 如果另一个类型是联合类型的一部分，则兼容
        foreach ($this->types as $type) {
            if ($type->isCompatibleWith($other)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 简化联合类型
     * 
     * 移除重复类型，合并兼容类型等
     *
     * @return Type
     */
    public function simplify(): Type
    {
        $simplified = [];
        $hasNull = false;

        foreach ($this->types as $type) {
            if ($type->getName() === 'null') {
                $hasNull = true;
                continue;
            }

            // 检查是否已有兼容类型
            $found = false;
            foreach ($simplified as $existing) {
                if ($existing->isCompatibleWith($type)) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $simplified[] = $type;
            }
        }

        // 如果只剩一个类型
        if (count($simplified) === 1) {
            $type = $simplified[0];
            if ($hasNull) {
                $type->setNullable(true);
            }
            return $type;
        }

        // 如果有 null，添加回去
        if ($hasNull) {
            $simplified[] = new Type('null');
        }

        return new static($simplified);
    }

    /**
     * 创建联合类型
     *
     * @param Type ...$types
     * @return static
     */
    public static function of(Type ...$types): static
    {
        return new static($types);
    }
}
