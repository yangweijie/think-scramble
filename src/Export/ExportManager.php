<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Export;

/**
 * 导出管理器
 */
class ExportManager
{
    /**
     * 支持的导出格式
     */
    protected array $exporters = [
        'postman' => PostmanExporter::class,
        'insomnia' => InsomniaExporter::class,
    ];

    /**
     * 导出为指定格式
     *
     * @param array $openApiDoc OpenAPI 文档
     * @param string $format 导出格式
     * @param string $filename 输出文件名
     * @return bool
     */
    public function export(array $openApiDoc, string $format, string $filename): bool
    {
        if (!isset($this->exporters[$format])) {
            throw new \InvalidArgumentException("Unsupported export format: {$format}");
        }

        $exporterClass = $this->exporters[$format];
        $exporter = new $exporterClass();

        $exportedData = $exporter->export($openApiDoc);
        $exporter->saveToFile($exportedData, $filename);

        return true;
    }

    /**
     * 获取支持的格式
     *
     * @return array
     */
    public function getSupportedFormats(): array
    {
        return array_keys($this->exporters);
    }

    /**
     * 批量导出
     *
     * @param array $openApiDoc OpenAPI 文档
     * @param array $formats 格式配置 ['format' => 'filename']
     * @return array 导出结果
     */
    public function batchExport(array $openApiDoc, array $formats): array
    {
        $results = [];

        foreach ($formats as $format => $filename) {
            try {
                $this->export($openApiDoc, $format, $filename);
                $results[$format] = [
                    'success' => true,
                    'filename' => $filename,
                    'message' => "Exported successfully to {$filename}",
                ];
            } catch (\Exception $e) {
                $results[$format] = [
                    'success' => false,
                    'filename' => $filename,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * 注册自定义导出器
     *
     * @param string $format 格式名称
     * @param string $exporterClass 导出器类名
     */
    public function registerExporter(string $format, string $exporterClass): void
    {
        if (!class_exists($exporterClass)) {
            throw new \InvalidArgumentException("Exporter class does not exist: {$exporterClass}");
        }

        $this->exporters[$format] = $exporterClass;
    }

    /**
     * 获取格式信息
     *
     * @return array
     */
    public function getFormatInfo(): array
    {
        return [
            'postman' => [
                'name' => 'Postman Collection',
                'description' => 'Export as Postman Collection v2.1',
                'extension' => 'json',
                'mime_type' => 'application/json',
            ],
            'insomnia' => [
                'name' => 'Insomnia Workspace',
                'description' => 'Export as Insomnia Workspace',
                'extension' => 'json',
                'mime_type' => 'application/json',
            ],
        ];
    }

    /**
     * 验证 OpenAPI 文档
     *
     * @param array $openApiDoc
     * @return array 验证结果
     */
    public function validateDocument(array $openApiDoc): array
    {
        $errors = [];
        $warnings = [];

        // 检查基本结构
        if (!isset($openApiDoc['openapi'])) {
            $errors[] = 'Missing OpenAPI version';
        }

        if (!isset($openApiDoc['info'])) {
            $errors[] = 'Missing info section';
        } else {
            if (!isset($openApiDoc['info']['title'])) {
                $warnings[] = 'Missing API title';
            }
            if (!isset($openApiDoc['info']['version'])) {
                $warnings[] = 'Missing API version';
            }
        }

        if (!isset($openApiDoc['paths']) || empty($openApiDoc['paths'])) {
            $warnings[] = 'No API paths defined';
        }

        // 检查路径
        $paths = $openApiDoc['paths'] ?? [];
        foreach ($paths as $path => $pathItem) {
            if (!is_array($pathItem)) {
                $errors[] = "Invalid path item for: {$path}";
                continue;
            }

            foreach ($pathItem as $method => $operation) {
                if (!is_array($operation)) {
                    $errors[] = "Invalid operation for: {$method} {$path}";
                    continue;
                }

                // 检查操作基本信息
                if (!isset($operation['responses'])) {
                    $warnings[] = "Missing responses for: {$method} {$path}";
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * 预处理文档
     *
     * @param array $openApiDoc
     * @return array 处理后的文档
     */
    public function preprocessDocument(array $openApiDoc): array
    {
        // 确保基本结构存在
        if (!isset($openApiDoc['info'])) {
            $openApiDoc['info'] = [
                'title' => 'API Documentation',
                'version' => '1.0.0',
            ];
        }

        if (!isset($openApiDoc['servers'])) {
            $openApiDoc['servers'] = [
                ['url' => 'http://localhost'],
            ];
        }

        if (!isset($openApiDoc['paths'])) {
            $openApiDoc['paths'] = [];
        }

        // 处理组件
        if (!isset($openApiDoc['components'])) {
            $openApiDoc['components'] = [];
        }

        if (!isset($openApiDoc['components']['schemas'])) {
            $openApiDoc['components']['schemas'] = [];
        }

        if (!isset($openApiDoc['components']['securitySchemes'])) {
            $openApiDoc['components']['securitySchemes'] = [];
        }

        return $openApiDoc;
    }

    /**
     * 生成导出摘要
     *
     * @param array $openApiDoc
     * @return array
     */
    public function generateExportSummary(array $openApiDoc): array
    {
        $paths = $openApiDoc['paths'] ?? [];
        $schemas = $openApiDoc['components']['schemas'] ?? [];
        $securitySchemes = $openApiDoc['components']['securitySchemes'] ?? [];

        $operationCount = 0;
        $methodCounts = [];

        foreach ($paths as $pathItem) {
            foreach ($pathItem as $method => $operation) {
                if (in_array(strtoupper($method), ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS'])) {
                    $operationCount++;
                    $method = strtoupper($method);
                    $methodCounts[$method] = ($methodCounts[$method] ?? 0) + 1;
                }
            }
        }

        return [
            'api_info' => [
                'title' => $openApiDoc['info']['title'] ?? 'Unknown',
                'version' => $openApiDoc['info']['version'] ?? '1.0.0',
                'description' => $openApiDoc['info']['description'] ?? '',
            ],
            'statistics' => [
                'total_paths' => count($paths),
                'total_operations' => $operationCount,
                'method_counts' => $methodCounts,
                'total_schemas' => count($schemas),
                'total_security_schemes' => count($securitySchemes),
            ],
            'servers' => $openApiDoc['servers'] ?? [],
            'export_timestamp' => date('c'),
        ];
    }
}
