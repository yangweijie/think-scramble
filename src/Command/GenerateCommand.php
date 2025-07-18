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
 * API 文档生成命令
 * 
 * 用于生成 OpenAPI 文档的 ThinkPHP 命令行工具
 */
class GenerateCommand extends Command
{
    /**
     * 配置实例
     */
    protected ScrambleConfig $config;

    /**
     * 配置命令
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('scramble:generate')
             ->setDescription('Generate OpenAPI documentation for your API')
             ->addOption('output', 'o', Option::VALUE_OPTIONAL, 'Output file path', 'api-docs.json')
             ->addOption('format', 'f', Option::VALUE_OPTIONAL, 'Output format (json|yaml)', 'json')
             ->addOption('pretty', 'p', Option::VALUE_NONE, 'Pretty print JSON output')
             ->addOption('config', 'c', Option::VALUE_OPTIONAL, 'Custom config file path')
             ->addOption('force', null, Option::VALUE_NONE, 'Force overwrite existing file')
             ->addOption('validate', 'v', Option::VALUE_NONE, 'Validate generated documentation')
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
            $app = $this->getApplication()->getKernel();
            $this->initializeConfig($input);

            if (!$input->getOption('quiet')) {
                $output->writeln('<info>Starting API documentation generation...</info>');
            }

            // 生成文档
            $document = $this->generateDocumentation($app, $output);

            // 输出文档
            $outputPath = $this->outputDocumentation($document, $app, $input, $output);

            // 验证文档
            if ($input->getOption('validate')) {
                $this->validateDocumentation($document, $output);
            }

            if (!$input->getOption('quiet')) {
                $output->writeln("<success>Documentation generated successfully: {$outputPath}</success>");
            }

            return self::SUCCESS;

        } catch (GenerationException $e) {
            $output->writeln("<error>Generation failed: {$e->getMessage()}</error>");
            return self::FAILURE;
        } catch (\Exception $e) {
            $output->writeln("<error>Unexpected error: {$e->getMessage()}</error>");
            if ($output->isVerbose()) {
                $output->writeln("<comment>Stack trace:</comment>");
                $output->writeln($e->getTraceAsString());
            }
            return self::FAILURE;
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
    }

    /**
     * 生成文档
     *
     * @param App $app 应用实例
     * @param Output $output 输出
     * @return array
     * @throws GenerationException
     */
    protected function generateDocumentation(App $app, Output $output): array
    {
        if (!$output->getOption('quiet')) {
            $output->writeln('<comment>Analyzing routes and controllers...</comment>');
        }

        $generator = new OpenApiGenerator($app, $this->config);
        
        $startTime = microtime(true);
        $document = $generator->generate();
        $endTime = microtime(true);

        $duration = round(($endTime - $startTime) * 1000, 2);
        
        if (!$output->getOption('quiet')) {
            $pathCount = count($document['paths'] ?? []);
            $schemaCount = count($document['components']['schemas'] ?? []);
            
            $output->writeln("<comment>Generated documentation in {$duration}ms</comment>");
            $output->writeln("<comment>Found {$pathCount} API endpoints and {$schemaCount} schemas</comment>");
        }

        return $document;
    }

    /**
     * 输出文档
     *
     * @param array $document 文档数据
     * @param App $app 应用实例
     * @param Input $input 输入
     * @param Output $output 输出
     * @return string 输出文件路径
     * @throws GenerationException
     */
    protected function outputDocumentation(array $document, App $app, Input $input, Output $output): string
    {
        $outputPath = $input->getOption('output');
        $format = strtolower($input->getOption('format'));
        $force = $input->getOption('force');

        // 检查文件是否存在
        if (file_exists($outputPath) && !$force) {
            throw new GenerationException("Output file already exists: {$outputPath}. Use --force to overwrite.");
        }

        // 生成内容
        $generator = new OpenApiGenerator($app, $this->config);
        
        switch ($format) {
            case 'yaml':
            case 'yml':
                $content = $generator->generateYaml();
                break;
            
            case 'json':
            default:
                $pretty = $input->getOption('pretty');
                $content = $generator->generateJson($pretty);
                break;
        }

        // 写入文件
        $result = file_put_contents($outputPath, $content);
        
        if ($result === false) {
            throw new GenerationException("Failed to write output file: {$outputPath}");
        }

        return $outputPath;
    }

    /**
     * 验证文档
     *
     * @param array $document 文档数据
     * @param Output $output 输出
     * @return void
     */
    protected function validateDocumentation(array $document, Output $output): void
    {
        if (!$output->getOption('quiet')) {
            $output->writeln('<comment>Validating documentation...</comment>');
        }

        $errors = [];

        // 基本结构验证
        $requiredFields = ['openapi', 'info', 'paths'];
        foreach ($requiredFields as $field) {
            if (!isset($document[$field])) {
                $errors[] = "Missing required field: {$field}";
            }
        }

        // 版本验证
        if (isset($document['openapi']) && !preg_match('/^3\.\d+\.\d+$/', $document['openapi'])) {
            $errors[] = "Invalid OpenAPI version: {$document['openapi']}";
        }

        // 路径验证
        if (isset($document['paths'])) {
            foreach ($document['paths'] as $path => $methods) {
                if (!str_starts_with($path, '/')) {
                    $errors[] = "Path must start with '/': {$path}";
                }
            }
        }

        if (empty($errors)) {
            if (!$output->getOption('quiet')) {
                $output->writeln('<success>Documentation validation passed</success>');
            }
        } else {
            $output->writeln('<error>Documentation validation failed:</error>');
            foreach ($errors as $error) {
                $output->writeln("  - {$error}");
            }
        }
    }

    /**
     * 获取命令帮助信息
     *
     * @return string
     */
    protected function getCommandHelp(): string
    {
        return <<<'HELP'
The <info>scramble:generate</info> command generates OpenAPI documentation for your API.

<comment>Usage:</comment>
  php think scramble:generate [options] [output]

<comment>Arguments:</comment>
  output                Output file path (default: api-docs.json)

<comment>Options:</comment>
  -f, --format=FORMAT   Output format: json or yaml (default: json)
  -p, --pretty          Pretty print JSON output
  -c, --config=CONFIG   Custom config file path
  --force               Force overwrite existing file
  -v, --validate        Validate generated documentation
  -q, --quiet           Suppress output messages

<comment>Examples:</comment>
  php think scramble:generate
  php think scramble:generate docs/api.json --pretty
  php think scramble:generate docs/api.yaml --format=yaml
  php think scramble:generate --config=custom-config.php --validate
HELP;
    }
}
