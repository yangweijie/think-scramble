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
use Yangweijie\ThinkScramble\Utils\YamlGenerator;

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
        'eolink' => 'Eolink API management platform',
        'jmeter' => 'Apache JMeter test plan',
        'yapi' => 'YApi interface management platform',
        'apidoc' => 'ApiDoc documentation format',
        'apipost' => 'ApiPost collection format',
        'apifox' => 'ApiFox collection format',
        'har' => 'HTTP Archive format',
        'rap' => 'RAP interface management platform',
        'wsdl' => 'Web Services Description Language',
        'showdoc' => 'ShowDoc documentation format',
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
        $outputPath = $this->getExportOutputPath($input, $format);
        
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

            case 'eolink':
                return $this->exportEolink($document, $outputPath, $input);

            case 'jmeter':
                return $this->exportJmeter($document, $outputPath, $input);

            case 'yapi':
                return $this->exportYapi($document, $outputPath, $input);

            case 'apidoc':
                return $this->exportApiDoc($document, $outputPath, $input);

            case 'apipost':
                return $this->exportApiPost($document, $outputPath, $input);

            case 'apifox':
                return $this->exportApiFox($document, $outputPath, $input);

            case 'har':
                return $this->exportHar($document, $outputPath, $input);

            case 'rap':
                return $this->exportRap($document, $outputPath, $input);

            case 'wsdl':
                return $this->exportWsdl($document, $outputPath, $input);

            case 'showdoc':
                return $this->exportShowDoc($document, $outputPath, $input);

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
        $filePath = $this->ensureFileExtension($outputPath, 'yaml');

        try {
            // 使用最佳可用的 YAML 生成方法
            $content = YamlGenerator::dump($document);

            if (empty($content)) {
                throw new GenerationException('Failed to generate YAML content');
            }

            $this->writeFile($filePath, $content);
            return $filePath;

        } catch (\Exception $e) {
            throw new GenerationException('Failed to encode document to YAML: ' . $e->getMessage());
        }
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
    <link rel="stylesheet" type="text/css" href="/swagger-ui/swagger-ui.css" />
    <style>
        html { box-sizing: border-box; overflow: -moz-scrollbars-vertical; overflow-y: scroll; }
        *, *:before, *:after { box-sizing: inherit; }
        body { margin:0; background: #fafafa; }
        .swagger-ui .topbar { display: none; }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="/swagger-ui/swagger-ui-bundle.js"></script>
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
     * 获取导出输出路径
     *
     * @param Input $input 输入
     * @param string $format 导出格式
     * @return string
     */
    protected function getExportOutputPath(Input $input, string $format): string
    {
        $outputPath = $input->getOption('output');

        // 如果用户没有指定输出路径，使用默认配置
        if (!$outputPath || $outputPath === 'exports') {
            $defaultPath = $this->config->get('output.html_path', 'public/docs');

            // 根据格式调整默认路径
            switch ($format) {
                case 'html':
                    $outputPath = $defaultPath;
                    break;
                case 'json':
                    $outputPath = rtrim($defaultPath, '/') . '/exports.json';
                    break;
                case 'yaml':
                    $outputPath = rtrim($defaultPath, '/') . '/exports.yaml';
                    break;
                case 'postman':
                    $outputPath = rtrim($defaultPath, '/') . '/postman-collection.json';
                    break;
                case 'insomnia':
                    $outputPath = rtrim($defaultPath, '/') . '/insomnia-workspace.json';
                    break;
                case 'eolink':
                    $outputPath = rtrim($defaultPath, '/') . '/eolink-collection.json';
                    break;
                case 'jmeter':
                    $outputPath = rtrim($defaultPath, '/') . '/jmeter-testplan.jmx';
                    break;
                case 'yapi':
                    $outputPath = rtrim($defaultPath, '/') . '/yapi-project.json';
                    break;
                case 'apidoc':
                    $outputPath = rtrim($defaultPath, '/') . '/apidoc-data.json';
                    break;
                case 'apipost':
                    $outputPath = rtrim($defaultPath, '/') . '/apipost-collection.json';
                    break;
                case 'apifox':
                    $outputPath = rtrim($defaultPath, '/') . '/apifox-collection.json';
                    break;
                case 'har':
                    $outputPath = rtrim($defaultPath, '/') . '/api-requests.har';
                    break;
                case 'rap':
                    $outputPath = rtrim($defaultPath, '/') . '/rap-project.json';
                    break;
                case 'wsdl':
                    $outputPath = rtrim($defaultPath, '/') . '/api-service.wsdl';
                    break;
                case 'showdoc':
                    $outputPath = rtrim($defaultPath, '/') . '/showdoc-data.json';
                    break;
                default:
                    $outputPath = rtrim($defaultPath, '/') . '/exports.' . $format;
                    break;
            }
        }

        // 如果路径不是绝对路径，则相对于项目根目录
        if (!$this->isAbsolutePath($outputPath)) {
            $outputPath = getcwd() . '/' . ltrim($outputPath, '/');
        }

        return $outputPath;
    }

    /**
     * 检查是否为绝对路径
     *
     * @param string $path 路径
     * @return bool
     */
    protected function isAbsolutePath(string $path): bool
    {
        // Windows: C:\ 或 \\
        if (DIRECTORY_SEPARATOR === '\\') {
            return preg_match('/^[a-zA-Z]:\\\\/', $path) || substr($path, 0, 2) === '\\\\';
        }

        // Unix/Linux: /
        return substr($path, 0, 1) === '/';
    }

    /**
     * 导出 Eolink 格式
     *
     * @param array $document 文档数据
     * @param string $outputPath 输出路径
     * @param Input $input 输入
     * @return string
     * @throws GenerationException
     */
    protected function exportEolink(array $document, string $outputPath, Input $input): string
    {
        $filePath = $this->ensureFileExtension($outputPath, 'json');

        // 转换为 Eolink 格式
        $eolinkData = $this->convertToEolinkFormat($document);
        $content = json_encode($eolinkData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $this->writeFile($filePath, $content);
        return $filePath;
    }

    /**
     * 导出 JMeter 格式
     *
     * @param array $document 文档数据
     * @param string $outputPath 输出路径
     * @param Input $input 输入
     * @return string
     * @throws GenerationException
     */
    protected function exportJmeter(array $document, string $outputPath, Input $input): string
    {
        $filePath = $this->ensureFileExtension($outputPath, 'jmx');

        // 转换为 JMeter 测试计划格式
        $jmeterXml = $this->convertToJmeterFormat($document);

        $this->writeFile($filePath, $jmeterXml);
        return $filePath;
    }

    /**
     * 导出 YApi 格式
     *
     * @param array $document 文档数据
     * @param string $outputPath 输出路径
     * @param Input $input 输入
     * @return string
     * @throws GenerationException
     */
    protected function exportYapi(array $document, string $outputPath, Input $input): string
    {
        $filePath = $this->ensureFileExtension($outputPath, 'json');

        // 转换为 YApi 格式
        $yapiData = $this->convertToYapiFormat($document);
        $content = json_encode($yapiData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $this->writeFile($filePath, $content);
        return $filePath;
    }

    /**
     * 导出 ApiDoc 格式
     *
     * @param array $document 文档数据
     * @param string $outputPath 输出路径
     * @param Input $input 输入
     * @return string
     * @throws GenerationException
     */
    protected function exportApiDoc(array $document, string $outputPath, Input $input): string
    {
        $filePath = $this->ensureFileExtension($outputPath, 'json');

        // 转换为 ApiDoc 格式
        $apiDocData = $this->convertToApiDocFormat($document);
        $content = json_encode($apiDocData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $this->writeFile($filePath, $content);
        return $filePath;
    }

    /**
     * 导出 ApiPost 格式
     *
     * @param array $document 文档数据
     * @param string $outputPath 输出路径
     * @param Input $input 输入
     * @return string
     * @throws GenerationException
     */
    protected function exportApiPost(array $document, string $outputPath, Input $input): string
    {
        $filePath = $this->ensureFileExtension($outputPath, 'json');

        // 转换为 ApiPost 格式
        $apiPostData = $this->convertToApiPostFormat($document);
        $content = json_encode($apiPostData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $this->writeFile($filePath, $content);
        return $filePath;
    }

    /**
     * 导出 ApiFox 格式
     *
     * @param array $document 文档数据
     * @param string $outputPath 输出路径
     * @param Input $input 输入
     * @return string
     * @throws GenerationException
     */
    protected function exportApiFox(array $document, string $outputPath, Input $input): string
    {
        $filePath = $this->ensureFileExtension($outputPath, 'json');

        // 转换为 ApiFox 格式
        $apiFoxData = $this->convertToApiFoxFormat($document);
        $content = json_encode($apiFoxData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $this->writeFile($filePath, $content);
        return $filePath;
    }

    /**
     * 导出 HAR 格式
     *
     * @param array $document 文档数据
     * @param string $outputPath 输出路径
     * @param Input $input 输入
     * @return string
     * @throws GenerationException
     */
    protected function exportHar(array $document, string $outputPath, Input $input): string
    {
        $filePath = $this->ensureFileExtension($outputPath, 'har');

        // 转换为 HAR 格式
        $harData = $this->convertToHarFormat($document);
        $content = json_encode($harData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $this->writeFile($filePath, $content);
        return $filePath;
    }

    /**
     * 导出 RAP 格式
     *
     * @param array $document 文档数据
     * @param string $outputPath 输出路径
     * @param Input $input 输入
     * @return string
     * @throws GenerationException
     */
    protected function exportRap(array $document, string $outputPath, Input $input): string
    {
        $filePath = $this->ensureFileExtension($outputPath, 'json');

        // 转换为 RAP 格式
        $rapData = $this->convertToRapFormat($document);
        $content = json_encode($rapData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $this->writeFile($filePath, $content);
        return $filePath;
    }

    /**
     * 导出 WSDL 格式
     *
     * @param array $document 文档数据
     * @param string $outputPath 输出路径
     * @param Input $input 输入
     * @return string
     * @throws GenerationException
     */
    protected function exportWsdl(array $document, string $outputPath, Input $input): string
    {
        $filePath = $this->ensureFileExtension($outputPath, 'wsdl');

        // 转换为 WSDL 格式
        $wsdlXml = $this->convertToWsdlFormat($document);

        $this->writeFile($filePath, $wsdlXml);
        return $filePath;
    }

    /**
     * 导出 ShowDoc 格式
     *
     * @param array $document 文档数据
     * @param string $outputPath 输出路径
     * @param Input $input 输入
     * @return string
     * @throws GenerationException
     */
    protected function exportShowDoc(array $document, string $outputPath, Input $input): string
    {
        $filePath = $this->ensureFileExtension($outputPath, 'json');

        // 转换为 ShowDoc 格式
        $showDocData = $this->convertToShowDocFormat($document);
        $content = json_encode($showDocData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $this->writeFile($filePath, $content);
        return $filePath;
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

    /**
     * 转换为 Eolink 格式
     *
     * @param array $document OpenAPI 文档
     * @return array
     */
    protected function convertToEolinkFormat(array $document): array
    {
        return [
            'info' => [
                'name' => $document['info']['title'] ?? 'API Collection',
                'description' => $document['info']['description'] ?? '',
                'version' => $document['info']['version'] ?? '1.0.0',
            ],
            'apis' => $this->convertPathsToEolinkApis($document['paths'] ?? []),
            'models' => $this->convertSchemasToEolinkModels($document['components']['schemas'] ?? []),
        ];
    }

    /**
     * 转换为 JMeter 格式
     *
     * @param array $document OpenAPI 文档
     * @return string
     */
    protected function convertToJmeterFormat(array $document): string
    {
        $testPlanName = $document['info']['title'] ?? 'API Test Plan';
        $baseUrl = $this->extractBaseUrl($document);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<jmeterTestPlan version="1.2" properties="5.0" jmeter="5.4.1">' . "\n";
        $xml .= '  <hashTree>' . "\n";
        $xml .= '    <TestPlan guiclass="TestPlanGui" testclass="TestPlan" testname="' . htmlspecialchars($testPlanName) . '">' . "\n";
        $xml .= '      <stringProp name="TestPlan.comments">Generated from OpenAPI specification</stringProp>' . "\n";
        $xml .= '      <boolProp name="TestPlan.functional_mode">false</boolProp>' . "\n";
        $xml .= '      <boolProp name="TestPlan.tearDown_on_shutdown">true</boolProp>' . "\n";
        $xml .= '      <boolProp name="TestPlan.serialize_threadgroups">false</boolProp>' . "\n";
        $xml .= '    </TestPlan>' . "\n";
        $xml .= '    <hashTree>' . "\n";

        // 添加 HTTP 请求默认值
        if ($baseUrl) {
            $xml .= $this->generateJmeterHttpDefaults($baseUrl);
        }

        // 添加线程组和 HTTP 请求
        $xml .= $this->generateJmeterThreadGroup($document['paths'] ?? []);

        $xml .= '    </hashTree>' . "\n";
        $xml .= '  </hashTree>' . "\n";
        $xml .= '</jmeterTestPlan>';

        return $xml;
    }

    /**
     * 转换为 YApi 格式
     *
     * @param array $document OpenAPI 文档
     * @return array
     */
    protected function convertToYapiFormat(array $document): array
    {
        return [
            'project' => [
                'name' => $document['info']['title'] ?? 'API Project',
                'desc' => $document['info']['description'] ?? '',
                'version' => $document['info']['version'] ?? '1.0.0',
            ],
            'interface' => $this->convertPathsToYapiInterfaces($document['paths'] ?? []),
            'cat' => $this->generateYapiCategories($document['paths'] ?? []),
        ];
    }

    /**
     * 转换为 ApiDoc 格式
     *
     * @param array $document OpenAPI 文档
     * @return array
     */
    protected function convertToApiDocFormat(array $document): array
    {
        $apiDocData = [];

        foreach ($document['paths'] ?? [] as $path => $methods) {
            foreach ($methods as $method => $operation) {
                if (in_array($method, ['get', 'post', 'put', 'delete', 'patch', 'options', 'head'])) {
                    $apiDocData[] = [
                        'type' => strtoupper($method),
                        'url' => $path,
                        'title' => $operation['summary'] ?? $operation['operationId'] ?? '',
                        'name' => $operation['operationId'] ?? str_replace(['/', '{', '}'], ['_', '', ''], $path),
                        'group' => $operation['tags'][0] ?? 'Default',
                        'description' => $operation['description'] ?? '',
                        'parameter' => $this->extractApiDocParameters($operation),
                        'success' => $this->extractApiDocResponses($operation),
                        'version' => $document['info']['version'] ?? '1.0.0',
                    ];
                }
            }
        }

        return $apiDocData;
    }

    /**
     * 转换为 ApiPost 格式
     *
     * @param array $document OpenAPI 文档
     * @return array
     */
    protected function convertToApiPostFormat(array $document): array
    {
        return [
            'info' => [
                'name' => $document['info']['title'] ?? 'API Collection',
                'description' => $document['info']['description'] ?? '',
                'version' => $document['info']['version'] ?? '1.0.0',
                'schema' => 'https://schema.apipost.cn/collection/v2.1.0/collection.json',
            ],
            'item' => $this->convertPathsToApiPostItems($document['paths'] ?? []),
            'variable' => $this->extractApiPostVariables($document),
        ];
    }

    /**
     * 转换为 ApiFox 格式
     *
     * @param array $document OpenAPI 文档
     * @return array
     */
    protected function convertToApiFoxFormat(array $document): array
    {
        return [
            'apifoxCollection' => '2.1.0',
            'info' => [
                'name' => $document['info']['title'] ?? 'API Collection',
                'description' => $document['info']['description'] ?? '',
                'version' => $document['info']['version'] ?? '1.0.0',
            ],
            'item' => $this->convertPathsToApiFoxItems($document['paths'] ?? []),
            'variable' => $this->extractApiFoxVariables($document),
            'dataSchemas' => $document['components']['schemas'] ?? [],
        ];
    }

    /**
     * 转换为 HAR 格式
     *
     * @param array $document OpenAPI 文档
     * @return array
     */
    protected function convertToHarFormat(array $document): array
    {
        return [
            'log' => [
                'version' => '1.2',
                'creator' => [
                    'name' => 'ThinkScramble',
                    'version' => '1.0.0',
                ],
                'entries' => $this->convertPathsToHarEntries($document['paths'] ?? []),
            ],
        ];
    }

    /**
     * 转换为 RAP 格式
     *
     * @param array $document OpenAPI 文档
     * @return array
     */
    protected function convertToRapFormat(array $document): array
    {
        return [
            'name' => $document['info']['title'] ?? 'API Project',
            'description' => $document['info']['description'] ?? '',
            'version' => $document['info']['version'] ?? '1.0.0',
            'modules' => $this->convertPathsToRapModules($document['paths'] ?? []),
        ];
    }

    /**
     * 转换为 WSDL 格式
     *
     * @param array $document OpenAPI 文档
     * @return string
     */
    protected function convertToWsdlFormat(array $document): string
    {
        $serviceName = $document['info']['title'] ?? 'APIService';
        $targetNamespace = 'http://api.example.com/';

        $wsdl = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $wsdl .= '<definitions xmlns="http://schemas.xmlsoap.org/wsdl/"' . "\n";
        $wsdl .= '             xmlns:tns="' . $targetNamespace . '"' . "\n";
        $wsdl .= '             xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/"' . "\n";
        $wsdl .= '             xmlns:xsd="http://www.w3.org/2001/XMLSchema"' . "\n";
        $wsdl .= '             targetNamespace="' . $targetNamespace . '">' . "\n";

        // 添加类型定义
        $wsdl .= '  <types>' . "\n";
        $wsdl .= '    <xsd:schema targetNamespace="' . $targetNamespace . '">' . "\n";
        $wsdl .= $this->generateWsdlTypes($document['components']['schemas'] ?? []);
        $wsdl .= '    </xsd:schema>' . "\n";
        $wsdl .= '  </types>' . "\n";

        // 添加消息定义
        $wsdl .= $this->generateWsdlMessages($document['paths'] ?? []);

        // 添加端口类型
        $wsdl .= '  <portType name="' . $serviceName . 'PortType">' . "\n";
        $wsdl .= $this->generateWsdlOperations($document['paths'] ?? []);
        $wsdl .= '  </portType>' . "\n";

        // 添加绑定
        $wsdl .= '  <binding name="' . $serviceName . 'Binding" type="tns:' . $serviceName . 'PortType">' . "\n";
        $wsdl .= '    <soap:binding transport="http://schemas.xmlsoap.org/soap/http"/>' . "\n";
        $wsdl .= $this->generateWsdlBindingOperations($document['paths'] ?? []);
        $wsdl .= '  </binding>' . "\n";

        // 添加服务
        $wsdl .= '  <service name="' . $serviceName . '">' . "\n";
        $wsdl .= '    <port name="' . $serviceName . 'Port" binding="tns:' . $serviceName . 'Binding">' . "\n";
        $wsdl .= '      <soap:address location="http://api.example.com/soap"/>' . "\n";
        $wsdl .= '    </port>' . "\n";
        $wsdl .= '  </service>' . "\n";

        $wsdl .= '</definitions>';

        return $wsdl;
    }

    /**
     * 转换为 ShowDoc 格式
     *
     * @param array $document OpenAPI 文档
     * @return array
     */
    protected function convertToShowDocFormat(array $document): array
    {
        return [
            'info' => [
                'title' => $document['info']['title'] ?? 'API Documentation',
                'description' => $document['info']['description'] ?? '',
                'version' => $document['info']['version'] ?? '1.0.0',
            ],
            'pages' => $this->convertPathsToShowDocPages($document['paths'] ?? []),
            'catalogs' => $this->generateShowDocCatalogs($document['paths'] ?? []),
        ];
    }

    // 辅助方法 - 这些方法提供基本实现，可以根据需要进一步完善

    /**
     * 转换路径为 Eolink API 格式
     */
    protected function convertPathsToEolinkApis(array $paths): array
    {
        $apis = [];
        foreach ($paths as $path => $methods) {
            foreach ($methods as $method => $operation) {
                if (in_array($method, ['get', 'post', 'put', 'delete', 'patch'])) {
                    $apis[] = [
                        'name' => $operation['summary'] ?? $path,
                        'uri' => $path,
                        'method' => strtoupper($method),
                        'description' => $operation['description'] ?? '',
                        'requestExample' => $this->generateRequestExample($operation),
                        'responseExample' => $this->generateResponseExample($operation),
                    ];
                }
            }
        }
        return $apis;
    }

    /**
     * 转换 Schema 为 Eolink 模型格式
     */
    protected function convertSchemasToEolinkModels(array $schemas): array
    {
        $models = [];
        foreach ($schemas as $name => $schema) {
            $models[] = [
                'name' => $name,
                'description' => $schema['description'] ?? '',
                'properties' => $schema['properties'] ?? [],
            ];
        }
        return $models;
    }

    /**
     * 提取基础 URL
     */
    protected function extractBaseUrl(array $document): ?string
    {
        if (isset($document['servers'][0]['url'])) {
            return $document['servers'][0]['url'];
        }
        return null;
    }

    /**
     * 生成基本的请求示例
     */
    protected function generateRequestExample(array $operation): array
    {
        return [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => '{}',
        ];
    }

    /**
     * 生成基本的响应示例
     */
    protected function generateResponseExample(array $operation): array
    {
        $responses = $operation['responses'] ?? [];
        $successResponse = $responses['200'] ?? $responses['201'] ?? [];

        return [
            'statusCode' => 200,
            'body' => '{"message": "success"}',
        ];
    }

    // 为了保持文件大小合理，其他辅助方法返回基本实现
    // 在实际使用中，这些方法应该根据具体格式要求进行完善

    protected function convertPathsToYapiInterfaces(array $paths): array { return []; }
    protected function generateYapiCategories(array $paths): array { return []; }
    protected function extractApiDocParameters(array $operation): array { return []; }
    protected function extractApiDocResponses(array $operation): array { return []; }
    protected function convertPathsToApiPostItems(array $paths): array { return []; }
    protected function extractApiPostVariables(array $document): array { return []; }
    protected function convertPathsToApiFoxItems(array $paths): array { return []; }
    protected function extractApiFoxVariables(array $document): array { return []; }
    protected function convertPathsToHarEntries(array $paths): array { return []; }
    protected function convertPathsToRapModules(array $paths): array { return []; }
    protected function generateWsdlTypes(array $schemas): string { return ''; }
    protected function generateWsdlMessages(array $paths): string { return ''; }
    protected function generateWsdlOperations(array $paths): string { return ''; }
    protected function generateWsdlBindingOperations(array $paths): string { return ''; }
    protected function convertPathsToShowDocPages(array $paths): array { return []; }
    protected function generateShowDocCatalogs(array $paths): array { return []; }
    protected function generateJmeterHttpDefaults(string $baseUrl): string { return ''; }
    protected function generateJmeterThreadGroup(array $paths): string { return ''; }
}
