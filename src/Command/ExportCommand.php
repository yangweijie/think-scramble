<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Argument;
use think\console\input\Option;
use think\App;
use Yangweijie\ThinkScramble\Config\ScrambleConfig;
use Yangweijie\ThinkScramble\Generator\OpenApiGenerator;
use Yangweijie\ThinkScramble\Exception\GenerationException;

/**
 * API 文档导出命令
 * 
 * 用于导出 API 文档到不同格式和平台的命令行工具
 */
class ExportCommand extends Command
{
    /**
     * 配置实例
     */
    protected ScrambleConfig $config;

    /**
     * 支持的导出格式
     */
    protected array $supportedFormats = [
        'json' => 'OpenAPI JSON format',
        'yaml' => 'OpenAPI YAML format',
        'html' => 'Static HTML documentation',
        'postman' => 'Postman collection',
        'insomnia' => 'Insomnia workspace',
    ];

    /**
     * 配置命令
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('scramble:export')
             ->setDescription('Export API documentation to various formats and platforms')
             ->addOption('format', 'f', Option::VALUE_REQUIRED, 'Export format: ' . implode('|', array_keys($this->supportedFormats)))
             ->addOption('output', 'o', Option::VALUE_OPTIONAL, 'Output directory or file path', 'exports')
             ->addOption('title', 't', Option::VALUE_OPTIONAL, 'Documentation title')
             ->addOption('api-version', null, Option::VALUE_OPTIONAL, 'API version')
             ->addOption('template', null, Option::VALUE_OPTIONAL, 'Custom template path for HTML export')
             ->addOption('include-examples', 'e', Option::VALUE_NONE, 'Include request/response examples')
             ->addOption('compress', 'z', Option::VALUE_NONE, 'Compress output (for applicable formats)')
             ->addOption('config', 'c', Option::VALUE_OPTIONAL, 'Custom config file path')
             ->addOption('quiet', 'q', Option::VALUE_NONE, 'Suppress output messages')
             ->setHelp($this->getCommandHelp());
    }

    /**
     * 执行命令
     *
     * @param Input $input 输入
     * @param Output $output 输出
     * @return int
     */
    protected function execute(Input $input, Output $output): int
    {
        try {
            // 在 ThinkPHP 中获取应用实例
            $app = app();
            $this->initializeConfig($input);

            $format = strtolower($input->getOption('format'));
            
            if (!isset($this->supportedFormats[$format])) {
                $output->writeln("<error>Unsupported format: {$format}</error>");
                $output->writeln("Supported formats: " . implode(', ', array_keys($this->supportedFormats)));
                return 1; // FAILURE
            }

            if (!$input->getOption('quiet')) {
                $output->writeln("<info>Exporting API documentation to {$format} format...</info>");
            }

            // 生成文档
            $document = $this->generateDocumentation($app, $input, $output);

            // 导出文档
            $outputPath = $this->exportDocumentation($document, $format, $app, $input, $output);

            if (!$input->getOption('quiet')) {
                $output->writeln("<success>Documentation exported successfully: {$outputPath}</success>");
            }

            return 0; // SUCCESS

        } catch (GenerationException $e) {
            $output->writeln("<error>Export failed: {$e->getMessage()}</error>");
            return 1; // FAILURE
        } catch (\Exception $e) {
            $output->writeln("<error>Unexpected error: {$e->getMessage()}</error>");
            if ($output->isVerbose()) {
                $output->writeln("<comment>Stack trace:</comment>");
                $output->writeln($e->getTraceAsString());
            }
            return 1; // FAILURE
        }
    }

    /**
     * 初始化配置
     *
     * @param Input $input 输入
     * @return void
     */
    protected function initializeConfig(Input $input): void
    {
        $configPath = $input->getOption('config');
        
        if ($configPath && file_exists($configPath)) {
            $customConfig = require $configPath;
            $this->config = new ScrambleConfig($customConfig);
        } else {
            $this->config = new ScrambleConfig();
        }

        // 覆盖配置选项
        if ($title = $input->getOption('title')) {
            $this->config->set('info.title', $title);
        }

        if ($version = $input->getOption('api-version')) {
            $this->config->set('info.version', $version);
        }
    }

    /**
     * 生成文档
     *
     * @param App $app 应用实例
     * @param Input $input 输入
     * @param Output $output 输出
     * @return array
     * @throws GenerationException
     */
    protected function generateDocumentation(App $app, Input $input, Output $output): array
    {
        if (!$input->getOption('quiet')) {
            $output->writeln('<comment>Generating documentation...</comment>');
        }

        $generator = new OpenApiGenerator($app, $this->config);
        return $generator->generate();
    }

    /**
     * 导出文档
     *
     * @param array $document 文档数据
     * @param string $format 导出格式
     * @param App $app 应用实例
     * @param Input $input 输入
     * @param Output $output 输出
     * @return string 输出路径
     * @throws GenerationException
     */
    protected function exportDocumentation(array $document, string $format, App $app, Input $input, Output $output): string
    {
        $outputPath = $input->getOption('output');
        
        switch ($format) {
            case 'json':
                return $this->exportJson($document, $outputPath, $input);
            
            case 'yaml':
                return $this->exportYaml($document, $outputPath, $input);
            
            case 'html':
                return $this->exportHtml($document, $outputPath, $input, $output);
            
            case 'postman':
                return $this->exportPostman($document, $outputPath, $input);
            
            case 'insomnia':
                return $this->exportInsomnia($document, $outputPath, $input);
            
            default:
                throw new GenerationException("Unsupported export format: {$format}");
        }
    }

    /**
     * 导出 JSON 格式
     *
     * @param array $document 文档数据
     * @param string $outputPath 输出路径
     * @param Input $input 输入
     * @return string
     * @throws GenerationException
     */
    protected function exportJson(array $document, string $outputPath, Input $input): string
    {
        $filePath = $this->ensureFileExtension($outputPath, 'json');
        $content = json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        if ($content === false) {
            throw new GenerationException('Failed to encode document to JSON');
        }

        $this->writeFile($filePath, $content);
        return $filePath;
    }

    /**
     * 导出 YAML 格式
     *
     * @param array $document 文档数据
     * @param string $outputPath 输出路径
     * @param Input $input 输入
     * @return string
     * @throws GenerationException
     */
    protected function exportYaml(array $document, string $outputPath, Input $input): string
    {
        if (!function_exists('yaml_emit')) {
            throw new GenerationException('YAML extension is not available');
        }

        $filePath = $this->ensureFileExtension($outputPath, 'yaml');
        $content = yaml_emit($document);
        
        if ($content === false) {
            throw new GenerationException('Failed to encode document to YAML');
        }

        $this->writeFile($filePath, $content);
        return $filePath;
    }

    /**
     * 导出 HTML 格式
     *
     * @param array $document 文档数据
     * @param string $outputPath 输出路径
     * @param Input $input 输入
     * @param Output $output 输出
     * @return string
     * @throws GenerationException
     */
    protected function exportHtml(array $document, string $outputPath, Input $input, Output $output): string
    {
        $dirPath = $this->ensureDirectory($outputPath);
        $templatePath = $input->getOption('template');
        
        if ($templatePath && !file_exists($templatePath)) {
            throw new GenerationException("Template file not found: {$templatePath}");
        }

        // 使用默认模板或自定义模板
        $template = $templatePath ? file_get_contents($templatePath) : $this->getDefaultHtmlTemplate();
        
        // 替换模板变量
        $html = str_replace([
            '{{title}}',
            '{{version}}',
            '{{spec}}',
        ], [
            $document['info']['title'] ?? 'API Documentation',
            $document['info']['version'] ?? '1.0.0',
            json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        ], $template);

        $filePath = $dirPath . '/index.html';
        $this->writeFile($filePath, $html);

        return $filePath;
    }

    /**
     * 导出 Postman 集合
     *
     * @param array $document 文档数据
     * @param string $outputPath 输出路径
     * @param Input $input 输入
     * @return string
     * @throws GenerationException
     */
    protected function exportPostman(array $document, string $outputPath, Input $input): string
    {
        $filePath = $this->ensureFileExtension($outputPath, 'json');
        
        // 转换为 Postman 集合格式
        $collection = $this->convertToPostmanCollection($document);
        $content = json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        $this->writeFile($filePath, $content);
        return $filePath;
    }

    /**
     * 导出 Insomnia 工作空间
     *
     * @param array $document 文档数据
     * @param string $outputPath 输出路径
     * @param Input $input 输入
     * @return string
     * @throws GenerationException
     */
    protected function exportInsomnia(array $document, string $outputPath, Input $input): string
    {
        $filePath = $this->ensureFileExtension($outputPath, 'json');
        
        // 转换为 Insomnia 工作空间格式
        $workspace = $this->convertToInsomniaWorkspace($document);
        $content = json_encode($workspace, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        $this->writeFile($filePath, $content);
        return $filePath;
    }

    /**
     * 转换为 Postman 集合格式
     *
     * @param array $document OpenAPI 文档
     * @return array
     */
    protected function convertToPostmanCollection(array $document): array
    {
        return [
            'info' => [
                'name' => $document['info']['title'] ?? 'API Collection',
                'description' => $document['info']['description'] ?? '',
                'version' => $document['info']['version'] ?? '1.0.0',
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'item' => $this->convertPathsToPostmanItems($document['paths'] ?? []),
        ];
    }

    /**
     * 转换路径为 Postman 项目
     *
     * @param array $paths 路径数据
     * @return array
     */
    protected function convertPathsToPostmanItems(array $paths): array
    {
        $items = [];
        
        foreach ($paths as $path => $methods) {
            foreach ($methods as $method => $operation) {
                $items[] = [
                    'name' => $operation['summary'] ?? "{$method} {$path}",
                    'request' => [
                        'method' => strtoupper($method),
                        'url' => [
                            'raw' => '{{baseUrl}}' . $path,
                            'host' => ['{{baseUrl}}'],
                            'path' => explode('/', trim($path, '/')),
                        ],
                        'description' => $operation['description'] ?? '',
                    ],
                ];
            }
        }
        
        return $items;
    }

    /**
     * 转换为 Insomnia 工作空间格式
     *
     * @param array $document OpenAPI 文档
     * @return array
     */
    protected function convertToInsomniaWorkspace(array $document): array
    {
        return [
            '_type' => 'export',
            '__export_format' => 4,
            'resources' => [
                [
                    '_id' => 'wrk_' . uniqid(),
                    '_type' => 'workspace',
                    'name' => $document['info']['title'] ?? 'API Workspace',
                    'description' => $document['info']['description'] ?? '',
                ],
            ],
        ];
    }

    /**
     * 确保文件扩展名
     *
     * @param string $path 路径
     * @param string $extension 扩展名
     * @return string
     */
    protected function ensureFileExtension(string $path, string $extension): string
    {
        if (!str_ends_with($path, ".{$extension}")) {
            $path .= ".{$extension}";
        }
        return $path;
    }

    /**
     * 确保目录存在
     *
     * @param string $path 路径
     * @return string
     * @throws GenerationException
     */
    protected function ensureDirectory(string $path): string
    {
        if (!is_dir($path)) {
            if (!mkdir($path, 0755, true)) {
                throw new GenerationException("Failed to create directory: {$path}");
            }
        }
        return $path;
    }

    /**
     * 写入文件
     *
     * @param string $filePath 文件路径
     * @param string $content 内容
     * @return void
     * @throws GenerationException
     */
    protected function writeFile(string $filePath, string $content): void
    {
        $result = file_put_contents($filePath, $content);
        
        if ($result === false) {
            throw new GenerationException("Failed to write file: {$filePath}");
        }
    }

    /**
     * 获取默认 HTML 模板
     *
     * @return string
     */
    protected function getDefaultHtmlTemplate(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <title>{{title}}</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@4.15.5/swagger-ui.css" />
    <style>
        html { box-sizing: border-box; overflow: -moz-scrollbars-vertical; overflow-y: scroll; }
        *, *:before, *:after { box-sizing: inherit; }
        body { margin:0; background: #fafafa; }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@4.15.5/swagger-ui-bundle.js"></script>
    <script>
        window.onload = function() {
            const ui = SwaggerUIBundle({
                url: '',
                spec: {{spec}},
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIBundle.presets.standalone
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout"
            });
        };
    </script>
</body>
</html>
HTML;
    }

    /**
     * 获取命令帮助信息
     *
     * @return string
     */
    protected function getCommandHelp(): string
    {
        $formats = '';
        foreach ($this->supportedFormats as $format => $description) {
            $formats .= "  {$format}    {$description}\n";
        }

        return <<<HELP
The <info>scramble:export</info> command exports API documentation to various formats and platforms.

<comment>Usage:</comment>
  php think scramble:export <format> [output] [options]

<comment>Arguments:</comment>
  format                Export format
  output                Output directory or file path (default: exports)

<comment>Supported formats:</comment>
{$formats}
<comment>Options:</comment>
  -t, --title=TITLE     Documentation title
  --version=VERSION     API version
  --template=TEMPLATE   Custom template path for HTML export
  -e, --include-examples Include request/response examples
  -z, --compress        Compress output (for applicable formats)
  -c, --config=CONFIG   Custom config file path
  -q, --quiet           Suppress output messages

<comment>Examples:</comment>
  php think scramble:export json docs/api.json
  php think scramble:export html docs/ --title="My API"
  php think scramble:export postman collections/api.json
  php think scramble:export yaml --version=2.0.0
HELP;
    }
}
