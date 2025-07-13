<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Analyzer;

use Yangweijie\ThinkScramble\Analyzer\Type\Type;
use Yangweijie\ThinkScramble\Analyzer\Type\ScalarType;
use Yangweijie\ThinkScramble\Analyzer\Type\ArrayType;
use Yangweijie\ThinkScramble\Analyzer\Type\UnionType;

/**
 * DocBlock 解析器
 * 
 * 解析 PHPDoc 注释中的类型信息和注解
 */
class DocBlockParser
{
    /**
     * 解析 DocBlock
     *
     * @param string $docComment DocBlock 注释
     * @return array
     */
    public function parse(string $docComment): array
    {
        $result = [
            'summary' => '',
            'description' => '',
            'tags' => [],
            'types' => [],
        ];

        // 清理 DocBlock
        $cleaned = $this->cleanDocBlock($docComment);
        $lines = explode("\n", $cleaned);

        $summary = '';
        $description = '';
        $tags = [];
        $inDescription = false;

        foreach ($lines as $line) {
            $line = trim($line);
            
            if (empty($line)) {
                if (!empty($summary) && !$inDescription) {
                    $inDescription = true;
                }
                continue;
            }

            // 解析标签
            if (str_starts_with($line, '@')) {
                $tag = $this->parseTag($line);
                if ($tag) {
                    $tags[] = $tag;
                }
                continue;
            }

            // 解析摘要和描述
            if (empty($summary)) {
                $summary = $line;
            } elseif ($inDescription) {
                $description .= ($description ? ' ' : '') . $line;
            }
        }

        $result['summary'] = $summary;
        $result['description'] = $description;
        $result['tags'] = $tags;

        return $result;
    }

    /**
     * 解析参数类型
     *
     * @param string $docComment DocBlock 注释
     * @param string $paramName 参数名
     * @return Type|null
     */
    public function parseParameterType(string $docComment, string $paramName): ?Type
    {
        $parsed = $this->parse($docComment);
        
        foreach ($parsed['tags'] as $tag) {
            if ($tag['name'] === 'param' && isset($tag['variable']) && $tag['variable'] === $paramName) {
                return $this->parseTypeString($tag['type'] ?? '');
            }
        }

        return null;
    }

    /**
     * 解析返回类型
     *
     * @param string $docComment DocBlock 注释
     * @return Type|null
     */
    public function parseReturnType(string $docComment): ?Type
    {
        $parsed = $this->parse($docComment);
        
        foreach ($parsed['tags'] as $tag) {
            if ($tag['name'] === 'return') {
                return $this->parseTypeString($tag['type'] ?? '');
            }
        }

        return null;
    }

    /**
     * 解析变量类型
     *
     * @param string $docComment DocBlock 注释
     * @return Type|null
     */
    public function parseVariableType(string $docComment): ?Type
    {
        $parsed = $this->parse($docComment);
        
        foreach ($parsed['tags'] as $tag) {
            if ($tag['name'] === 'var') {
                return $this->parseTypeString($tag['type'] ?? '');
            }
        }

        return null;
    }

    /**
     * 清理 DocBlock 注释
     *
     * @param string $docComment
     * @return string
     */
    protected function cleanDocBlock(string $docComment): string
    {
        // 移除开始和结束标记
        $cleaned = preg_replace('/^\/\*\*|\*\/$/', '', $docComment);
        
        // 移除每行开头的 * 和空格
        $cleaned = preg_replace('/^\s*\*\s?/m', '', $cleaned);
        
        return trim($cleaned);
    }

    /**
     * 解析标签
     *
     * @param string $line
     * @return array|null
     */
    protected function parseTag(string $line): ?array
    {
        // 匹配标签格式：@tagname [type] [variable] [description]
        if (preg_match('/^@(\w+)(?:\s+(.+))?$/', $line, $matches)) {
            $tagName = $matches[1];
            $content = $matches[2] ?? '';

            $tag = ['name' => $tagName];

            // 解析不同类型的标签
            switch ($tagName) {
                case 'param':
                    $parsed = $this->parseParamTag($content);
                    $tag = array_merge($tag, $parsed);
                    break;

                case 'return':
                case 'var':
                    $parsed = $this->parseTypeTag($content);
                    $tag = array_merge($tag, $parsed);
                    break;

                case 'throws':
                    $tag['type'] = trim($content);
                    break;

                default:
                    $tag['content'] = $content;
                    break;
            }

            return $tag;
        }

        return null;
    }

    /**
     * 解析参数标签
     *
     * @param string $content
     * @return array
     */
    protected function parseParamTag(string $content): array
    {
        // 格式：type $variable description
        if (preg_match('/^(\S+)\s+\$(\w+)(?:\s+(.+))?$/', $content, $matches)) {
            return [
                'type' => $matches[1],
                'variable' => $matches[2],
                'description' => $matches[3] ?? '',
            ];
        }

        return ['content' => $content];
    }

    /**
     * 解析类型标签
     *
     * @param string $content
     * @return array
     */
    protected function parseTypeTag(string $content): array
    {
        // 格式：type [description]
        $parts = explode(' ', $content, 2);
        
        return [
            'type' => $parts[0] ?? '',
            'description' => $parts[1] ?? '',
        ];
    }

    /**
     * 解析类型字符串
     *
     * @param string $typeString
     * @return Type|null
     */
    protected function parseTypeString(string $typeString): ?Type
    {
        if (empty($typeString)) {
            return null;
        }

        // 处理可空类型
        $nullable = false;
        if (str_starts_with($typeString, '?')) {
            $nullable = true;
            $typeString = substr($typeString, 1);
        }

        // 处理联合类型
        if (str_contains($typeString, '|')) {
            $types = [];
            foreach (explode('|', $typeString) as $type) {
                $parsedType = $this->parseSingleType(trim($type));
                if ($parsedType) {
                    $types[] = $parsedType;
                }
            }
            
            if (count($types) > 1) {
                return new UnionType($types);
            } elseif (count($types) === 1) {
                return $types[0]->setNullable($nullable);
            }
        }

        // 解析单一类型
        $type = $this->parseSingleType($typeString);
        if ($type) {
            return $type->setNullable($nullable);
        }

        return null;
    }

    /**
     * 解析单一类型
     *
     * @param string $typeString
     * @return Type|null
     */
    protected function parseSingleType(string $typeString): ?Type
    {
        // 标量类型
        if (in_array($typeString, ['int', 'integer', 'float', 'double', 'string', 'bool', 'boolean'])) {
            $normalizedType = match ($typeString) {
                'integer' => 'int',
                'double' => 'float',
                'boolean' => 'bool',
                default => $typeString,
            };
            return new ScalarType($normalizedType);
        }

        // 数组类型
        if ($typeString === 'array') {
            return ArrayType::simple();
        }

        // 泛型数组类型 array<type> 或 type[]
        if (preg_match('/^array<(.+)>$/', $typeString, $matches)) {
            $valueType = $this->parseSingleType(trim($matches[1]));
            return ArrayType::of($valueType ?: new Type('mixed'));
        }

        if (str_ends_with($typeString, '[]')) {
            $valueType = $this->parseSingleType(substr($typeString, 0, -2));
            return ArrayType::of($valueType ?: new Type('mixed'));
        }

        // 其他类型（类名等）
        return new Type($typeString);
    }
}
