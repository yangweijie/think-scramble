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

                case 'upload':
                case 'file':
                    $parsed = $this->parseFileUploadTag($content);
                    $tag = array_merge($tag, $parsed);
                    break;

                // ThinkPHP 注解支持
                case 'Route':
                case 'Get':
                case 'Post':
                case 'Put':
                case 'Delete':
                case 'Patch':
                case 'Options':
                case 'Head':
                    $parsed = $this->parseRouteAnnotation($tagName, $content);
                    $tag = array_merge($tag, $parsed);
                    break;

                case 'Middleware':
                    $parsed = $this->parseMiddlewareAnnotation($content);
                    $tag = array_merge($tag, $parsed);
                    break;

                case 'Validate':
                case 'ValidateRule':
                    $parsed = $this->parseValidateAnnotation($content);
                    $tag = array_merge($tag, $parsed);
                    break;

                case 'Resource':
                    $parsed = $this->parseResourceAnnotation($content);
                    $tag = array_merge($tag, $parsed);
                    break;

                case 'Inject':
                case 'Value':
                    $parsed = $this->parseInjectAnnotation($content);
                    $tag = array_merge($tag, $parsed);
                    break;

                // OpenAPI 注解支持
                case 'Api':
                case 'ApiParam':
                case 'ApiResponse':
                case 'ApiSuccess':
                case 'ApiError':
                    $parsed = $this->parseApiAnnotation($tagName, $content);
                    $tag = array_merge($tag, $parsed);
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
        // 支持格式：
        // type $variable description
        // {file} $variable description
        // {file} variable description (without $)

        // 检查是否为文件类型参数
        if (preg_match('/^\{(file|upload)\}\s+\$?(\w+)(?:\s+(.+))?$/', $content, $matches)) {
            return [
                'type' => 'file',
                'variable' => $matches[2],
                'description' => $matches[3] ?? '',
                'is_file_upload' => true,
            ];
        }

        // 标准格式：type $variable description
        if (preg_match('/^(\S+)\s+\$(\w+)(?:\s+(.+))?$/', $content, $matches)) {
            $result = [
                'type' => $matches[1],
                'variable' => $matches[2],
                'description' => $matches[3] ?? '',
            ];

            // 检查类型是否为文件相关
            if (in_array(strtolower($matches[1]), ['file', 'upload', 'uploadedfile'])) {
                $result['is_file_upload'] = true;
            }

            return $result;
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
     * 解析文件上传标签
     *
     * @param string $content
     * @return array
     */
    protected function parseFileUploadTag(string $content): array
    {
        // 支持格式：
        // @upload filename 文件描述
        // @upload filename required 文件描述
        // @upload filename jpg,png,gif max:2MB 头像文件

        $parts = preg_split('/\s+/', trim($content));
        $result = [
            'is_file_upload' => true,
            'name' => $parts[0] ?? 'file',
            'required' => false,
            'description' => '',
            'allowed_types' => [],
            'max_size' => null,
        ];

        $description = [];
        $i = 1;

        while ($i < count($parts)) {
            $part = $parts[$i];

            if ($part === 'required') {
                $result['required'] = true;
            } elseif (preg_match('/^([a-z,]+)$/', $part)) {
                // 文件类型：jpg,png,gif
                $result['allowed_types'] = explode(',', $part);
            } elseif (preg_match('/^max:(\d+)(MB|KB|GB)?$/i', $part, $matches)) {
                // 文件大小：max:2MB
                $size = (int)$matches[1];
                $unit = strtoupper($matches[2] ?? 'MB');
                $result['max_size'] = $this->convertSizeToBytes($size, $unit);
            } else {
                // 描述文本
                $description[] = $part;
            }
            $i++;
        }

        $result['description'] = implode(' ', $description);

        return $result;
    }

    /**
     * 转换文件大小为字节
     *
     * @param int $size
     * @param string $unit
     * @return int
     */
    protected function convertSizeToBytes(int $size, string $unit): int
    {
        return match (strtoupper($unit)) {
            'KB' => $size * 1024,
            'MB' => $size * 1024 * 1024,
            'GB' => $size * 1024 * 1024 * 1024,
            default => $size,
        };
    }

    /**
     * 解析路由注解
     *
     * @param string $method HTTP方法
     * @param string $content 注解内容
     * @return array
     */
    protected function parseRouteAnnotation(string $method, string $content): array
    {
        $route = [
            'is_route_annotation' => true,
            'method' => strtoupper($method),
            'path' => '',
            'name' => '',
            'middleware' => [],
            'domain' => '',
        ];

        // 提取路径 - 支持多种格式
        // @Get("/api/users")
        // @Route("/api/users", method="GET")
        if (preg_match('/["\']([^"\']*)["\']/', $content, $matches)) {
            $route['path'] = $matches[1];
        }

        // 提取路由名称
        if (preg_match('/name\s*=\s*["\']([^"\']+)["\']/', $content, $matches)) {
            $route['name'] = $matches[1];
        }

        // 提取中间件
        if (preg_match('/middleware\s*=\s*["\']([^"\']+)["\']/', $content, $matches)) {
            $route['middleware'] = explode(',', $matches[1]);
        }

        // 提取域名
        if (preg_match('/domain\s*=\s*["\']([^"\']+)["\']/', $content, $matches)) {
            $route['domain'] = $matches[1];
        }

        return $route;
    }

    /**
     * 解析中间件注解
     *
     * @param string $content
     * @return array
     */
    protected function parseMiddlewareAnnotation(string $content): array
    {
        $middleware = [
            'is_middleware_annotation' => true,
            'middleware' => [],
        ];

        // 单个中间件: @Middleware("auth")
        if (preg_match('/^["\']([^"\']+)["\']$/', trim($content), $matches)) {
            $middleware['middleware'] = [$matches[1]];
        }
        // 多个中间件: @Middleware({"auth", "throttle:60,1"})
        elseif (preg_match_all('/["\']([^"\']+)["\']/', $content, $matches)) {
            $middleware['middleware'] = $matches[1];
        }
        // 简单格式: @Middleware auth,throttle
        elseif (!empty($content)) {
            $middleware['middleware'] = array_map('trim', explode(',', $content));
        }

        return $middleware;
    }

    /**
     * 解析验证注解
     *
     * @param string $content
     * @return array
     */
    protected function parseValidateAnnotation(string $content): array
    {
        $validate = [
            'is_validate_annotation' => true,
            'class' => '',
            'scene' => '',
            'batch' => false,
        ];

        // 验证器类: @Validate("UserValidate")
        if (preg_match('/["\']([^"\']+)["\']/', $content, $matches)) {
            $validate['class'] = $matches[1];
        }

        // 验证场景
        if (preg_match('/scene\s*=\s*["\']([^"\']+)["\']/', $content, $matches)) {
            $validate['scene'] = $matches[1];
        }

        // 批量验证
        if (strpos($content, 'batch') !== false) {
            $validate['batch'] = true;
        }

        return $validate;
    }

    /**
     * 解析资源路由注解
     *
     * @param string $content
     * @return array
     */
    protected function parseResourceAnnotation(string $content): array
    {
        $resource = [
            'is_resource_annotation' => true,
            'name' => '',
            'only' => [],
            'except' => [],
        ];

        // 资源名称: @Resource("blog")
        if (preg_match('/["\']([^"\']+)["\']/', $content, $matches)) {
            $resource['name'] = $matches[1];
        }

        // only 参数
        if (preg_match('/only\s*=\s*\[([^\]]+)\]/', $content, $matches)) {
            $only = explode(',', $matches[1]);
            $resource['only'] = array_map(function($item) {
                return trim($item, ' "\'');
            }, $only);
        }

        // except 参数
        if (preg_match('/except\s*=\s*\[([^\]]+)\]/', $content, $matches)) {
            $except = explode(',', $matches[1]);
            $resource['except'] = array_map(function($item) {
                return trim($item, ' "\'');
            }, $except);
        }

        return $resource;
    }

    /**
     * 解析依赖注入注解
     *
     * @param string $content
     * @return array
     */
    protected function parseInjectAnnotation(string $content): array
    {
        $inject = [
            'is_inject_annotation' => true,
            'class' => '',
            'name' => '',
        ];

        // 注入类: @Inject("UserService")
        if (preg_match('/["\']([^"\']+)["\']/', $content, $matches)) {
            $inject['class'] = $matches[1];
        }

        // 注入名称
        if (preg_match('/name\s*=\s*["\']([^"\']+)["\']/', $content, $matches)) {
            $inject['name'] = $matches[1];
        }

        return $inject;
    }

    /**
     * 解析 API 文档注解
     *
     * @param string $type 注解类型
     * @param string $content 注解内容
     * @return array
     */
    protected function parseApiAnnotation(string $type, string $content): array
    {
        $api = [
            'is_api_annotation' => true,
            'type' => $type,
            'content' => $content,
        ];

        switch ($type) {
            case 'Api':
                $api = array_merge($api, $this->parseApiTag($content));
                break;
            case 'ApiParam':
                $api = array_merge($api, $this->parseApiParamTag($content));
                break;
            case 'ApiResponse':
            case 'ApiSuccess':
            case 'ApiError':
                $api = array_merge($api, $this->parseApiResponseTag($content));
                break;
        }

        return $api;
    }

    /**
     * 解析 @Api 标签
     *
     * @param string $content
     * @return array
     */
    protected function parseApiTag(string $content): array
    {
        // @Api {get} /api/users 获取用户列表
        if (preg_match('/\{(\w+)\}\s+(\S+)\s*(.*)/', $content, $matches)) {
            return [
                'method' => strtoupper($matches[1]),
                'path' => $matches[2],
                'title' => trim($matches[3]),
            ];
        }

        return ['title' => $content];
    }

    /**
     * 解析 @ApiParam 标签
     *
     * @param string $content
     * @return array
     */
    protected function parseApiParamTag(string $content): array
    {
        // @ApiParam {String} name 用户名
        if (preg_match('/\{(\w+)\}\s+(\w+)\s*(.*)/', $content, $matches)) {
            return [
                'param_type' => $matches[1],
                'param_name' => $matches[2],
                'description' => trim($matches[3]),
            ];
        }

        return ['description' => $content];
    }

    /**
     * 解析 @ApiResponse 标签
     *
     * @param string $content
     * @return array
     */
    protected function parseApiResponseTag(string $content): array
    {
        // @ApiSuccess {Object} data 响应数据
        if (preg_match('/\{(\w+)\}\s+(\w+)\s*(.*)/', $content, $matches)) {
            return [
                'type' => $matches[1],
                'field' => $matches[2],
                'description' => trim($matches[3]),
            ];
        }

        return ['description' => $content];
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
