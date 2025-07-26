<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Console;

use Yangweijie\ThinkScramble\Generator\DocumentBuilder;
use Yangweijie\ThinkScramble\Config\DefaultConfig;
use Yangweijie\ThinkScramble\Cache\CacheManager;

/**
 * ThinkScramble 命令行工具
 */
class ScrambleCommand
{
    /**
     * 版本号
     */
    const VERSION = '1.4.0';

    /**
     * 执行命令
     *
     * @param array $options 选项
     * @param array $argv 参数
     * @return int 退出码
     */
    public function execute(array $options, array $argv): int
    {
        try {
            // 生成文档
            if (isset($options['output']) || !empty($argv[1])) {
                return $this->generateDocumentation($options);
            }

            // 显示统计信息
            if (isset($options['stats'])) {
                return $this->showStats($options);
            }

            // 验证配置
            if (isset($options['validate'])) {
                return $this->validateConfig($options);
            }

            // 监控文件变化
            if (isset($options['watch'])) {
                return $this->watchFiles($options);
            }

            // 默认显示帮助
            $this->showHelp();
            return 0;

        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * 生成文档
     */
    protected function generateDocumentation(array $options): int
    {
        $this->info("Generating OpenAPI documentation...");

        // 加载配置
        $configFile = $options['config'] ?? 'scramble.php';
        $config = $this->loadConfig($configFile);

        // 创建文档构建器
        $cacheManager = new CacheManager($config);
        $documentBuilder = new DocumentBuilder($config);

        // 设置控制器路径
        $controllersPath = $options['controllers'] ?? 'app/controller';
        if (is_dir($controllersPath)) {
            $controllers = $this->discoverControllers($controllersPath);
            $this->info("Found " . count($controllers) . " controllers");
        } else {
            $controllers = [];
            $this->warning("Controllers directory not found: {$controllersPath}");
        }

        // 设置模型路径
        $modelsPath = $options['models'] ?? 'app/model';
        if (is_dir($modelsPath)) {
            $documentBuilder->autoDiscoverModels($modelsPath);
            $this->info("Auto-discovered models from: {$modelsPath}");
        }

        // 添加安全方案
        if (isset($options['middleware']) && !empty($controllers)) {
            $documentBuilder->addSecuritySchemes($controllers);
            $this->info("Added security schemes from middleware analysis");
        }

        // 生成文档
        $document = $documentBuilder->build();

        // 输出文件
        $outputFile = $options['output'] ?? 'openapi.json';
        $format = $options['format'] ?? $this->getFormatFromExtension($outputFile);

        $this->saveDocument($document, $outputFile, $format);
        $this->success("Documentation generated: {$outputFile}");

        // 显示统计信息
        $this->showGenerationStats($document, $cacheManager);

        return 0;
    }

    /**
     * 显示统计信息
     */
    protected function showStats(array $options): int
    {
        $this->info("ThinkScramble Statistics");
        $this->info("========================");

        $configFile = $options['config'] ?? 'scramble.php';
        $config = $this->loadConfig($configFile);
        $cacheManager = new CacheManager($config);

        // 缓存统计
        $cacheStats = $cacheManager->getStats();
        $this->info("Cache Statistics:");
        $this->info("  Hits: " . ($cacheStats['hits'] ?? 0));
        $this->info("  Misses: " . ($cacheStats['misses'] ?? 0));
        $this->info("  Total Files: " . ($cacheStats['total_files'] ?? 0));
        $this->info("  Total Size: " . $this->formatBytes($cacheStats['total_size'] ?? 0));

        // 控制器统计
        $controllersPath = $options['controllers'] ?? 'app/controller';
        if (is_dir($controllersPath)) {
            $controllers = $this->discoverControllers($controllersPath);
            $this->info("\nController Statistics:");
            $this->info("  Total Controllers: " . count($controllers));
        }

        // 模型统计
        $modelsPath = $options['models'] ?? 'app/model';
        if (is_dir($modelsPath)) {
            $models = $this->discoverModels($modelsPath);
            $this->info("\nModel Statistics:");
            $this->info("  Total Models: " . count($models));
        }

        return 0;
    }

    /**
     * 验证配置
     */
    protected function validateConfig(array $options): int
    {
        $this->info("Validating configuration...");

        $configFile = $options['config'] ?? 'scramble.php';
        
        if (!file_exists($configFile)) {
            $this->error("Configuration file not found: {$configFile}");
            return 1;
        }

        try {
            $config = $this->loadConfig($configFile);
            $documentBuilder = new DocumentBuilder($config);
            
            // 验证安全配置
            $validation = $documentBuilder->validateSecurity();
            
            if ($validation['valid']) {
                $this->success("Configuration is valid");
            } else {
                $this->error("Configuration validation failed:");
                foreach ($validation['errors'] as $error) {
                    $this->error("  - {$error}");
                }
            }

            if (!empty($validation['warnings'])) {
                $this->warning("Warnings:");
                foreach ($validation['warnings'] as $warning) {
                    $this->warning("  - {$warning}");
                }
            }

            return $validation['valid'] ? 0 : 1;

        } catch (\Exception $e) {
            $this->error("Configuration error: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * 监控文件变化
     */
    protected function watchFiles(array $options): int
    {
        $this->info("Watching for file changes...");
        $this->info("Press Ctrl+C to stop");

        $watchPaths = [
            $options['controllers'] ?? 'app/controller',
            $options['models'] ?? 'app/model',
        ];

        $lastModified = [];
        
        while (true) {
            $changed = false;
            
            foreach ($watchPaths as $path) {
                if (!is_dir($path)) continue;
                
                $files = $this->getPhpFiles($path);
                
                foreach ($files as $file) {
                    $mtime = filemtime($file);
                    
                    if (!isset($lastModified[$file])) {
                        $lastModified[$file] = $mtime;
                        continue;
                    }
                    
                    if ($mtime > $lastModified[$file]) {
                        $this->info("File changed: {$file}");
                        $lastModified[$file] = $mtime;
                        $changed = true;
                    }
                }
            }
            
            if ($changed) {
                $this->info("Regenerating documentation...");
                $this->generateDocumentation($options);
            }
            
            sleep(2); // 检查间隔
        }

        return 0;
    }

    /**
     * 加载配置
     */
    protected function loadConfig(string $configFile): DefaultConfig
    {
        if (file_exists($configFile)) {
            $configData = include $configFile;
            return new DefaultConfig($configData);
        }

        return new DefaultConfig();
    }

    /**
     * 发现控制器
     */
    protected function discoverControllers(string $path): array
    {
        $controllers = [];
        $files = $this->getPhpFiles($path);

        foreach ($files as $file) {
            $className = $this->extractClassName($file);
            if ($className && $this->isController($className)) {
                $controllers[] = $className;
            }
        }

        return $controllers;
    }

    /**
     * 发现模型
     */
    protected function discoverModels(string $path): array
    {
        $models = [];
        $files = $this->getPhpFiles($path);

        foreach ($files as $file) {
            $className = $this->extractClassName($file);
            if ($className && $this->isModel($className)) {
                $models[] = $className;
            }
        }

        return $models;
    }

    /**
     * 获取 PHP 文件
     */
    protected function getPhpFiles(string $path): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * 提取类名
     */
    protected function extractClassName(string $filePath): ?string
    {
        $content = file_get_contents($filePath);
        
        // 提取命名空间
        if (preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatches)) {
            $namespace = $namespaceMatches[1];
        } else {
            $namespace = '';
        }

        // 提取类名
        if (preg_match('/class\s+(\w+)/', $content, $classMatches)) {
            $className = $classMatches[1];
            return $namespace ? $namespace . '\\' . $className : $className;
        }

        return null;
    }

    /**
     * 检查是否为控制器
     */
    protected function isController(string $className): bool
    {
        return str_contains($className, 'Controller') || str_contains($className, 'controller');
    }

    /**
     * 检查是否为模型
     */
    protected function isModel(string $className): bool
    {
        try {
            if (!class_exists($className)) {
                return false;
            }
            $reflection = new \ReflectionClass($className);
            return $reflection->isSubclassOf('think\\Model');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 保存文档
     */
    protected function saveDocument(array $document, string $outputFile, string $format): void
    {
        switch ($format) {
            case 'yaml':
            case 'yml':
                $content = yaml_emit($document);
                break;
            case 'json':
            default:
                $content = json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                break;
        }

        file_put_contents($outputFile, $content);
    }

    /**
     * 从文件扩展名获取格式
     */
    protected function getFormatFromExtension(string $filename): string
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        return in_array($extension, ['yaml', 'yml']) ? 'yaml' : 'json';
    }

    /**
     * 显示生成统计
     */
    protected function showGenerationStats(array $document, CacheManager $cacheManager): void
    {
        $pathCount = count($document['paths'] ?? []);
        $schemaCount = count($document['components']['schemas'] ?? []);
        $securityCount = count($document['components']['securitySchemes'] ?? []);
        
        $this->info("\nGeneration Statistics:");
        $this->info("  API Paths: {$pathCount}");
        $this->info("  Schemas: {$schemaCount}");
        $this->info("  Security Schemes: {$securityCount}");

        $cacheStats = $cacheManager->getStats();
        $hitRate = ($cacheStats['hits'] + $cacheStats['misses']) > 0 
            ? round(($cacheStats['hits'] / ($cacheStats['hits'] + $cacheStats['misses'])) * 100, 2)
            : 0;
        $this->info("  Cache Hit Rate: {$hitRate}%");
    }

    /**
     * 格式化字节数
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * 显示帮助信息
     */
    public function showHelp(): void
    {
        echo "ThinkScramble CLI Tool v" . self::VERSION . "\n";
        echo "Usage: scramble [options]\n\n";
        echo "Options:\n";
        echo "  --output=FILE         Output file (default: openapi.json)\n";
        echo "  --config=FILE         Configuration file (default: scramble.php)\n";
        echo "  --format=FORMAT       Output format: json|yaml (auto-detect from extension)\n";
        echo "  --controllers=PATH    Controllers directory (default: app/controller)\n";
        echo "  --models=PATH         Models directory (default: app/model)\n";
        echo "  --middleware          Include middleware analysis\n";
        echo "  --validate            Validate configuration\n";
        echo "  --stats               Show statistics\n";
        echo "  --watch               Watch for file changes\n";
        echo "  --version             Show version\n";
        echo "  --help, -h            Show this help\n\n";
        echo "Examples:\n";
        echo "  scramble --output=api.json\n";
        echo "  scramble --output=api.yaml --middleware\n";
        echo "  scramble --stats\n";
        echo "  scramble --watch --output=api.json\n";
    }

    /**
     * 显示版本信息
     */
    public function showVersion(): void
    {
        echo "ThinkScramble v" . self::VERSION . "\n";
    }

    /**
     * 输出信息
     */
    protected function info(string $message): void
    {
        echo "[INFO] {$message}\n";
    }

    /**
     * 输出成功信息
     */
    protected function success(string $message): void
    {
        echo "[SUCCESS] {$message}\n";
    }

    /**
     * 输出警告
     */
    protected function warning(string $message): void
    {
        echo "[WARNING] {$message}\n";
    }

    /**
     * 输出错误
     */
    protected function error(string $message): void
    {
        echo "[ERROR] {$message}\n";
    }
}
