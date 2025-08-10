<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Analyzer;

use PhpParser\Node;
use PhpParser\NodeFinder;

/**
 * 文件上传分析器
 * 
 * 分析控制器方法中的文件上传相关代码
 */
class FileUploadAnalyzer
{
    /**
     * AST 解析器
     */
    protected AstParser $astParser;

    /**
     * DocBlock 解析器
     */
    protected DocBlockParser $docBlockParser;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->astParser = new AstParser();
        $this->docBlockParser = new DocBlockParser();
    }

    /**
     * 分析方法中的文件上传参数
     *
     * @param \ReflectionMethod $method 方法反射
     * @return array
     */
    public function analyzeMethod(\ReflectionMethod $method): array
    {
        $fileUploads = [];

        // 从注释中提取文件上传信息
        $docComment = $method->getDocComment();
        if ($docComment) {
            $docUploads = $this->extractFromDocComment($docComment);
            $fileUploads = array_merge($fileUploads, $docUploads);
            
            // 从 OpenAPI 注解中提取 requestBody 信息
            $openApiUploads = $this->extractFromOpenApiAnnotations($docComment);
            $fileUploads = array_merge($fileUploads, $openApiUploads);
        }

        // 从代码中自动识别文件上传
        $codeUploads = $this->extractFromCode($method);
        $fileUploads = array_merge($fileUploads, $codeUploads);

        // 去重并合并信息
        return $this->mergeFileUploads($fileUploads);
    }

    /**
     * 从注释中提取文件上传信息
     *
     * @param string $docComment
     * @return array
     */
    protected function extractFromDocComment(string $docComment): array
    {
        $parsed = $this->docBlockParser->parse($docComment);
        $fileUploads = [];

        foreach ($parsed['tags'] as $tag) {
            if (isset($tag['is_file_upload']) && $tag['is_file_upload']) {
                $fileUploads[] = $this->normalizeFileUploadInfo($tag);
            }
        }

        return $fileUploads;
    }

    /**
     * 从 OpenAPI 注解中提取文件上传信息
     *
     * @param string $docComment
     * @return array
     */
    protected function extractFromOpenApiAnnotations(string $docComment): array
    {
        $fileUploads = [];
        
        // 简单解析注释中的 OpenAPI requestBody 定义
        // 这里我们查找 @OA\RequestBody 注解
        if (preg_match_all('/@OA\\\\\\\\RequestBody\s*\([^)]*\)/', $docComment, $matches)) {
            foreach ($matches[0] as $match) {
                // 查找 @OA\Property 定义
                if (preg_match_all('/@OA\\\\\\\\Property\s*\(\s*property\s*=\s*"([^"]+)"\s*,[^)]*type\s*=\s*"array"[^)]*\)/', $match, $arrayProps)) {
                    // 处理数组类型的属性
                    foreach ($arrayProps[1] as $propertyName) {
                        $fileUploads[] = [
                            'name' => $propertyName,
                            'required' => true,
                            'description' => '文件上传参数',
                            'is_array' => true,
                            'source' => 'openapi_annotation',
                        ];
                    }
                }
                
                if (preg_match_all('/@OA\\\\\\\\Property\s*\(\s*property\s*=\s*"([^"]+)"\s*,[^)]*format\s*=\s*"binary"[^)]*\)/', $match, $binaryProps)) {
                    // 处理二进制类型的属性
                    foreach ($binaryProps[1] as $propertyName) {
                        // 检查是否已经添加了该属性（避免重复）
                        $exists = false;
                        foreach ($fileUploads as $index => $upload) {
                            if ($upload['name'] === $propertyName) {
                                $exists = true;
                                // 如果已经存在且不是数组类型，则更新它
                                if (!isset($upload['is_array']) || !$upload['is_array']) {
                                    $fileUploads[$index]['is_array'] = false;
                                }
                                break;
                            }
                        }
                        
                        if (!$exists) {
                            $fileUploads[] = [
                                'name' => $propertyName,
                                'required' => true,
                                'description' => '文件上传参数',
                                'is_array' => false,
                                'source' => 'openapi_annotation',
                            ];
                        }
                    }
                }
            }
        }
        
        return $fileUploads;
    }

    /**
     * 从代码中自动识别文件上传
     *
     * @param \ReflectionMethod $method
     * @return array
     */
    protected function extractFromCode(\ReflectionMethod $method): array
    {
        $fileUploads = [];
        
        try {
            $filename = $method->getFileName();
            if (!$filename) {
                return $fileUploads;
            }

            $ast = $this->astParser->parseFile($filename);
            $methodNode = $this->findMethodNode($ast, $method->getName());
            
            if ($methodNode) {
                $fileUploads = $this->analyzeMethodNode($methodNode);
            }
        } catch (\Exception $e) {
            // 忽略解析错误
        }

        return $fileUploads;
    }

    /**
     * 查找方法节点
     *
     * @param array $ast
     * @param string $methodName
     * @return Node\Stmt\ClassMethod|null
     */
    protected function findMethodNode(array $ast, string $methodName): ?Node\Stmt\ClassMethod
    {
        $finder = new NodeFinder();
        $methods = $finder->findInstanceOf($ast, Node\Stmt\ClassMethod::class);

        foreach ($methods as $method) {
            if ($method->name->name === $methodName) {
                return $method;
            }
        }

        return null;
    }

    /**
     * 分析方法节点中的文件上传调用
     *
     * @param Node\Stmt\ClassMethod $methodNode
     * @return array
     */
    protected function analyzeMethodNode(Node\Stmt\ClassMethod $methodNode): array
    {
        $fileUploads = [];
        $finder = new NodeFinder();

        // 查找 $request->file() 调用
        $methodCalls = $finder->findInstanceOf($methodNode, Node\Expr\MethodCall::class);

        foreach ($methodCalls as $call) {
            if ($this->isFileUploadCall($call)) {
                $paramName = $this->extractFileParameterName($call);
                if ($paramName) {
                    $fileUploads[] = [
                        'name' => $paramName,
                        'required' => true,
                        'description' => "文件上传参数",
                        'source' => 'code_analysis',
                    ];
                }
            }
        }

        return $fileUploads;
    }

    /**
     * 检查是否为文件上传调用
     *
     * @param Node\Expr\MethodCall $call
     * @return bool
     */
    protected function isFileUploadCall(Node\Expr\MethodCall $call): bool
    {
        // 检查方法名是否为 'file'
        if (!($call->name instanceof Node\Identifier) || $call->name->name !== 'file') {
            return false;
        }

        // 检查调用对象是否为 $request
        if ($call->var instanceof Node\Expr\Variable && 
            is_string($call->var->name) && 
            $call->var->name === 'request') {
            return true;
        }

        return false;
    }

    /**
     * 提取文件参数名
     *
     * @param Node\Expr\MethodCall $call
     * @return string|null
     */
    protected function extractFileParameterName(Node\Expr\MethodCall $call): ?string
    {
        if (empty($call->args)) {
            return 'file'; // 默认参数名
        }

        $arg = $call->args[0]->value;
        if ($arg instanceof Node\Scalar\String_) {
            return $arg->value;
        }

        return null;
    }

    /**
     * 标准化文件上传信息
     *
     * @param array $info
     * @return array
     */
    protected function normalizeFileUploadInfo(array $info): array
    {
        return [
            'name' => $info['name'] ?? $info['variable'] ?? 'file',
            'required' => $info['required'] ?? false,
            'description' => $info['description'] ?? '文件上传参数',
            'allowed_types' => $info['allowed_types'] ?? [],
            'max_size' => $info['max_size'] ?? null,
            'source' => 'annotation',
        ];
    }

    /**
     * 合并文件上传信息
     *
     * @param array $fileUploads
     * @return array
     */
    protected function mergeFileUploads(array $fileUploads): array
    {
        $merged = [];
        $byName = [];

        foreach ($fileUploads as $upload) {
            $name = $upload['name'];
            
            // 处理数组形式的参数名（如 files[]）
            $baseName = $name;
            if (str_ends_with($name, '[]')) {
                $baseName = substr($name, 0, -2);
            }
            
            // 检查是否已存在同名参数（包括数组形式和非数组形式）
            $existingKey = null;
            foreach ($byName as $key => $existing) {
                $existingName = $existing['name'];
                $existingBaseName = $existingName;
                if (str_ends_with($existingName, '[]')) {
                    $existingBaseName = substr($existingName, 0, -2);
                }
                
                if ($baseName === $existingBaseName) {
                    $existingKey = $key;
                    break;
                }
            }
            
            if ($existingKey !== null) {
                // 合并信息，注释优先于代码分析
                $existing = $byName[$existingKey];
                // 如果新的参数是数组形式（如 files[]），或者来源是注解，则优先使用
                if (str_ends_with($name, '[]') || $upload['source'] === 'annotation') {
                    $byName[$existingKey] = array_merge($existing, $upload);
                }
            } else {
                $byName[] = $upload;
            }
        }

        return array_values($byName);
    }

    /**
     * 生成文件上传的 OpenAPI 参数定义
     *
     * @param array $fileUpload
     * @return array
     */
    public function generateOpenApiParameter(array $fileUpload): array
    {
        $schema = [
            'type' => 'string',
            'format' => 'binary',
        ];

        $parameter = [
            'name' => $fileUpload['name'],
            'in' => 'formData', // 保留兼容性，但推荐使用 requestBody
            'required' => $fileUpload['required'] ?? false,
            'description' => $fileUpload['description'] ?? '文件上传参数',
            'schema' => $schema,
        ];

        // 添加文件类型限制
        if (!empty($fileUpload['allowed_types'])) {
            $parameter['description'] .= ' (支持格式: ' . implode(', ', $fileUpload['allowed_types']) . ')';
        }

        // 添加文件大小限制
        if (!empty($fileUpload['max_size'])) {
            $sizeText = $this->formatFileSize($fileUpload['max_size']);
            $parameter['description'] .= ' (最大大小: ' . $sizeText . ')';
        }

        return $parameter;
    }

    /**
     * 生成文件上传的 OpenAPI 请求体属性定义
     *
     * @param array $fileUpload
     * @return array
     */
    public function generateOpenApiRequestBodyProperty(array $fileUpload): array
    {
        // 检查是否为数组类型（批量上传）
        if (isset($fileUpload['is_array']) && $fileUpload['is_array']) {
            // 数组类型的文件上传
            $property = [
                'type' => 'array',
                'description' => $fileUpload['description'] ?? '文件上传参数',
                'items' => [
                    'type' => 'string',
                    'format' => 'binary'
                ]
            ];
        } else {
            // 检查参数名是否包含复数形式或特定关键词，以推断是否为数组
            $name = $fileUpload['name'] ?? '';
            $isArrayInferred = $this->isInferredAsArray($name, $fileUpload);
            
            if ($isArrayInferred) {
                // 数组类型的文件上传
                $property = [
                    'type' => 'array',
                    'description' => $fileUpload['description'] ?? '文件上传参数',
                    'items' => [
                        'type' => 'string',
                        'format' => 'binary'
                    ]
                ];
            } else {
                // 单个文件上传
                $property = [
                    'type' => 'string',
                    'format' => 'binary',
                    'description' => $fileUpload['description'] ?? '文件上传参数',
                ];
            }
        }

        // 添加文件类型限制
        if (!empty($fileUpload['allowed_types'])) {
            $property['description'] .= ' (支持格式: ' . implode(', ', $fileUpload['allowed_types']) . ')';
        }

        // 添加文件大小限制
        if (!empty($fileUpload['max_size'])) {
            $sizeText = $this->formatFileSize($fileUpload['max_size']);
            $property['description'] .= ' (最大大小: ' . $sizeText . ')';
        }

        return $property;
    }

    /**
     * 推断文件上传参数是否应为数组类型
     *
     * @param string $name 参数名
     * @param array $fileUpload 文件上传信息
     * @return bool
     */
    protected function isInferredAsArray(string $name, array $fileUpload): bool
    {
        // 明确标记为数组
        if (isset($fileUpload['is_array']) && $fileUpload['is_array']) {
            return true;
        }
        
        // 通过参数名推断（复数形式或带有[]）
        $pluralIndicators = ['files', 'images', 'documents', 'avatars'];
        foreach ($pluralIndicators as $indicator) {
            if (str_contains($name, $indicator)) {
                return true;
            }
        }
        
        // 检查参数名是否以[]结尾（HTML表单多文件上传的标准格式）
        if (str_ends_with($name, '[]')) {
            return true;
        }
        
        // 通过描述推断
        $description = $fileUpload['description'] ?? '';
        $arrayIndicators = ['多文件', '批量', '多个', '支持多'];
        foreach ($arrayIndicators as $indicator) {
            if (str_contains($description, $indicator)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * 格式化文件大小
     *
     * @param int $bytes
     * @return string
     */
    protected function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1024 * 1024 * 1024) {
            return round($bytes / (1024 * 1024 * 1024), 1) . 'GB';
        } elseif ($bytes >= 1024 * 1024) {
            return round($bytes / (1024 * 1024), 1) . 'MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 1) . 'KB';
        }
        return $bytes . 'B';
    }
}