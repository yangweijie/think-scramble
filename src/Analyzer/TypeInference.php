<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Analyzer;

use PhpParser\Node;
use PhpParser\NodeVisitor\NameResolver;
use Yangweijie\ThinkScramble\Analyzer\Type\Type;
use Yangweijie\ThinkScramble\Analyzer\Type\ScalarType;
use Yangweijie\ThinkScramble\Analyzer\Type\ArrayType;
use Yangweijie\ThinkScramble\Analyzer\Type\UnionType;
use Yangweijie\ThinkScramble\Exception\AnalysisException;

/**
 * 类型推断引擎
 * 
 * 实现静态类型推断算法，分析 PHP 代码中的类型信息
 */
class TypeInference
{
    /**
     * AST 解析器
     */
    protected AstParser $parser;

    /**
     * 类型缓存
     */
    protected array $typeCache = [];

    /**
     * 构造函数
     *
     * @param AstParser $parser
     */
    public function __construct(AstParser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * 推断表达式的类型
     *
     * @param Node $node AST 节点
     * @return Type
     * @throws AnalysisException
     */
    public function inferType(Node $node): Type
    {
        $cacheKey = $this->getCacheKey($node);
        
        if (isset($this->typeCache[$cacheKey])) {
            return $this->typeCache[$cacheKey];
        }

        $type = $this->doInferType($node);
        $this->typeCache[$cacheKey] = $type;
        
        return $type;
    }

    /**
     * 执行类型推断
     *
     * @param Node $node
     * @return Type
     * @throws AnalysisException
     */
    protected function doInferType(Node $node): Type
    {
        return match (true) {
            // 标量值
            $node instanceof Node\Scalar\LNumber => ScalarType::int(),
            $node instanceof Node\Scalar\DNumber => ScalarType::float(),
            $node instanceof Node\Scalar\String_ => ScalarType::string(),
            $node instanceof Node\Expr\ConstFetch && $this->isBooleanConstant($node) => ScalarType::bool(),
            
            // 数组
            $node instanceof Node\Expr\Array_ => $this->inferArrayType($node),
            
            // 变量
            $node instanceof Node\Expr\Variable => $this->inferVariableType($node),
            
            // 方法调用
            $node instanceof Node\Expr\MethodCall => $this->inferMethodCallType($node),
            
            // 函数调用
            $node instanceof Node\Expr\FuncCall => $this->inferFunctionCallType($node),
            
            // 属性访问
            $node instanceof Node\Expr\PropertyFetch => $this->inferPropertyType($node),
            
            // 二元操作
            $node instanceof Node\Expr\BinaryOp => $this->inferBinaryOpType($node),
            
            // 三元操作
            $node instanceof Node\Expr\Ternary => $this->inferTernaryType($node),
            
            // 类型声明
            $node instanceof Node\Identifier,
            $node instanceof Node\Name,
            $node instanceof Node\NullableType,
            $node instanceof Node\UnionType => $this->inferTypeDeclaration($node),
            
            // 默认情况
            default => new Type('mixed'),
        };
    }

    /**
     * 推断数组类型
     *
     * @param Node\Expr\Array_ $node
     * @return ArrayType
     */
    protected function inferArrayType(Node\Expr\Array_ $node): ArrayType
    {
        if (empty($node->items)) {
            return ArrayType::simple();
        }

        $keyTypes = [];
        $valueTypes = [];

        foreach ($node->items as $item) {
            if ($item === null) {
                continue;
            }

            // 推断键类型
            if ($item->key !== null) {
                $keyTypes[] = $this->inferType($item->key);
            } else {
                $keyTypes[] = ScalarType::int();
            }

            // 推断值类型
            $valueTypes[] = $this->inferType($item->value);
        }

        // 合并键类型
        $keyType = $this->mergeTypes($keyTypes);
        
        // 合并值类型
        $valueType = $this->mergeTypes($valueTypes);

        return new ArrayType($keyType, $valueType);
    }

    /**
     * 推断变量类型
     *
     * @param Node\Expr\Variable $node
     * @return Type
     */
    protected function inferVariableType(Node\Expr\Variable $node): Type
    {
        // 这里可以实现更复杂的变量类型推断
        // 例如通过分析赋值语句、函数参数等
        return new Type('mixed');
    }

    /**
     * 推断方法调用类型
     *
     * @param Node\Expr\MethodCall $node
     * @return Type
     */
    protected function inferMethodCallType(Node\Expr\MethodCall $node): Type
    {
        // 这里可以实现方法返回类型推断
        // 例如通过反射或静态分析
        return new Type('mixed');
    }

    /**
     * 推断函数调用类型
     *
     * @param Node\Expr\FuncCall $node
     * @return Type
     */
    protected function inferFunctionCallType(Node\Expr\FuncCall $node): Type
    {
        if ($node->name instanceof Node\Name) {
            $functionName = $node->name->toString();
            
            // 内置函数类型推断
            return match ($functionName) {
                'count', 'sizeof', 'strlen' => ScalarType::int(),
                'floatval', 'doubleval' => ScalarType::float(),
                'strval', 'trim', 'substr' => ScalarType::string(),
                'boolval', 'is_array', 'is_string' => ScalarType::bool(),
                'array_merge', 'array_filter' => ArrayType::simple(),
                default => new Type('mixed'),
            };
        }

        return new Type('mixed');
    }

    /**
     * 推断属性类型
     *
     * @param Node\Expr\PropertyFetch $node
     * @return Type
     */
    protected function inferPropertyType(Node\Expr\PropertyFetch $node): Type
    {
        // 这里可以实现属性类型推断
        return new Type('mixed');
    }

    /**
     * 推断二元操作类型
     *
     * @param Node\Expr\BinaryOp $node
     * @return Type
     */
    protected function inferBinaryOpType(Node\Expr\BinaryOp $node): Type
    {
        return match (true) {
            // 算术操作
            $node instanceof Node\Expr\BinaryOp\Plus,
            $node instanceof Node\Expr\BinaryOp\Minus,
            $node instanceof Node\Expr\BinaryOp\Mul,
            $node instanceof Node\Expr\BinaryOp\Div,
            $node instanceof Node\Expr\BinaryOp\Mod => $this->inferArithmeticType($node),
            
            // 比较操作
            $node instanceof Node\Expr\BinaryOp\Equal,
            $node instanceof Node\Expr\BinaryOp\NotEqual,
            $node instanceof Node\Expr\BinaryOp\Identical,
            $node instanceof Node\Expr\BinaryOp\NotIdentical,
            $node instanceof Node\Expr\BinaryOp\Greater,
            $node instanceof Node\Expr\BinaryOp\GreaterOrEqual,
            $node instanceof Node\Expr\BinaryOp\Smaller,
            $node instanceof Node\Expr\BinaryOp\SmallerOrEqual => ScalarType::bool(),
            
            // 字符串操作
            $node instanceof Node\Expr\BinaryOp\Concat => ScalarType::string(),
            
            // 逻辑操作
            $node instanceof Node\Expr\BinaryOp\BooleanAnd,
            $node instanceof Node\Expr\BinaryOp\BooleanOr,
            $node instanceof Node\Expr\BinaryOp\LogicalAnd,
            $node instanceof Node\Expr\BinaryOp\LogicalOr => ScalarType::bool(),
            
            default => new Type('mixed'),
        };
    }

    /**
     * 推断算术操作类型
     *
     * @param Node\Expr\BinaryOp $node
     * @return Type
     */
    protected function inferArithmeticType(Node\Expr\BinaryOp $node): Type
    {
        $leftType = $this->inferType($node->left);
        $rightType = $this->inferType($node->right);

        // 如果任一操作数是浮点数，结果是浮点数
        if ($leftType->getName() === 'float' || $rightType->getName() === 'float') {
            return ScalarType::float();
        }

        // 如果都是整数，结果是整数
        if ($leftType->getName() === 'int' && $rightType->getName() === 'int') {
            return ScalarType::int();
        }

        // 其他情况返回混合类型
        return new Type('mixed');
    }

    /**
     * 推断三元操作类型
     *
     * @param Node\Expr\Ternary $node
     * @return Type
     */
    protected function inferTernaryType(Node\Expr\Ternary $node): Type
    {
        $types = [];

        if ($node->if !== null) {
            $types[] = $this->inferType($node->if);
        }

        $types[] = $this->inferType($node->else);

        return $this->mergeTypes($types);
    }

    /**
     * 推断类型声明
     *
     * @param Node $node
     * @return Type
     */
    protected function inferTypeDeclaration(Node $node): Type
    {
        if ($node instanceof Node\Identifier) {
            return new Type($node->name);
        }

        if ($node instanceof Node\Name) {
            return new Type($node->toString());
        }

        if ($node instanceof Node\NullableType) {
            $innerType = $this->inferTypeDeclaration($node->type);
            return $innerType->setNullable(true);
        }

        if ($node instanceof Node\UnionType) {
            $types = [];
            foreach ($node->types as $type) {
                $types[] = $this->inferTypeDeclaration($type);
            }
            return new UnionType($types);
        }

        return new Type('mixed');
    }

    /**
     * 合并多个类型
     *
     * @param Type[] $types
     * @return Type
     */
    protected function mergeTypes(array $types): Type
    {
        if (empty($types)) {
            return new Type('mixed');
        }

        if (count($types) === 1) {
            return $types[0];
        }

        // 检查是否所有类型都相同
        $firstType = $types[0];
        $allSame = true;
        
        foreach ($types as $type) {
            if ($type->getName() !== $firstType->getName()) {
                $allSame = false;
                break;
            }
        }

        if ($allSame) {
            return $firstType;
        }

        // 创建联合类型
        return new UnionType($types);
    }

    /**
     * 检查是否为布尔常量
     *
     * @param Node\Expr\ConstFetch $node
     * @return bool
     */
    protected function isBooleanConstant(Node\Expr\ConstFetch $node): bool
    {
        $name = strtolower($node->name->toString());
        return in_array($name, ['true', 'false']);
    }

    /**
     * 获取缓存键
     *
     * @param Node $node
     * @return string
     */
    protected function getCacheKey(Node $node): string
    {
        return md5(serialize($node));
    }

    /**
     * 清除类型缓存
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->typeCache = [];
    }
}
