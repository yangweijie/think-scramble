<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Config;

use Yangweijie\ThinkScramble\Exception\ConfigException;

/**
 * 配置发布器
 * 
 * 负责将扩展包的配置文件发布到 ThinkPHP 应用中
 */
class ConfigPublisher
{
    /**
     * 源配置文件路径
     */
    protected string $sourcePath;

    /**
     * 目标配置文件路径
     */
    protected string $targetPath;

    /**
     * 构造函数
     *
     * @param string|null $sourcePath 源配置文件路径
     * @param string|null $targetPath 目标配置文件路径
     */
    public function __construct(?string $sourcePath = null, ?string $targetPath = null)
    {
        $this->sourcePath = $sourcePath ?: $this->getDefaultSourcePath();
        $this->targetPath = $targetPath ?: $this->getDefaultTargetPath();
    }

    /**
     * 发布配置文件
     *
     * @param bool $force 是否强制覆盖已存在的文件
     * @return bool
     * @throws ConfigException
     */
    public function publish(bool $force = false): bool
    {
        if (!file_exists($this->sourcePath)) {
            throw ConfigException::missingKey("Source config file not found: {$this->sourcePath}");
        }

        if (file_exists($this->targetPath) && !$force) {
            throw ConfigException::invalidValue(
                'target_path',
                $this->targetPath,
                'non-existing file (use force=true to overwrite)'
            );
        }

        // 确保目标目录存在
        $targetDir = dirname($this->targetPath);
        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0755, true)) {
                throw ConfigException::invalidValue('target_directory', $targetDir, 'writable directory');
            }
        }

        // 生成独立的配置文件内容
        $content = $this->generateStandaloneConfig();

        // 写入配置文件
        if (file_put_contents($this->targetPath, $content) === false) {
            throw ConfigException::invalidValue('write_operation', $this->targetPath, 'successful file write');
        }

        return true;
    }

    /**
     * 检查配置文件是否已发布
     *
     * @return bool
     */
    public function isPublished(): bool
    {
        return file_exists($this->targetPath);
    }

    /**
     * 获取源配置文件路径
     *
     * @return string
     */
    public function getSourcePath(): string
    {
        return $this->sourcePath;
    }

    /**
     * 获取目标配置文件路径
     *
     * @return string
     */
    public function getTargetPath(): string
    {
        return $this->targetPath;
    }

    /**
     * 设置源配置文件路径
     *
     * @param string $path
     * @return static
     */
    public function setSourcePath(string $path): static
    {
        $this->sourcePath = $path;
        return $this;
    }

    /**
     * 设置目标配置文件路径
     *
     * @param string $path
     * @return static
     */
    public function setTargetPath(string $path): static
    {
        $this->targetPath = $path;
        return $this;
    }

    /**
     * 获取默认源配置文件路径
     *
     * @return string
     */
    protected function getDefaultSourcePath(): string
    {
        return __DIR__ . '/config.php';
    }

    /**
     * 获取默认目标配置文件路径
     *
     * @return string
     */
    protected function getDefaultTargetPath(): string
    {
        // 尝试检测 ThinkPHP 应用根目录
        $appPath = $this->detectAppPath();
        return $appPath . '/config/scramble.php';
    }

    /**
     * 检测 ThinkPHP 应用根目录
     *
     * @return string
     */
    protected function detectAppPath(): string
    {
        // 检查常见的 ThinkPHP 应用路径
        $possiblePaths = [
            getcwd(),
            dirname(getcwd()),
            dirname(dirname(getcwd())),
        ];

        foreach ($possiblePaths as $path) {
            if ($this->isThinkPHPApp($path)) {
                return $path;
            }
        }

        // 如果找不到，返回当前工作目录
        return getcwd();
    }

    /**
     * 检查是否为 ThinkPHP 应用目录
     *
     * @param string $path
     * @return bool
     */
    protected function isThinkPHPApp(string $path): bool
    {
        return file_exists($path . '/think') || 
               file_exists($path . '/app') || 
               file_exists($path . '/config/app.php');
    }

    /**
     * 创建配置发布器实例
     *
     * @param string|null $sourcePath
     * @param string|null $targetPath
     * @return static
     */
    public static function make(?string $sourcePath = null, ?string $targetPath = null): static
    {
        return new static($sourcePath, $targetPath);
    }

    /**
     * 生成独立的配置文件内容
     *
     * @return string
     * @throws ConfigException
     */
    protected function generateStandaloneConfig(): string
    {
        // 确保助手函数可用
        $helpersPath = dirname($this->sourcePath) . '/helpers.php';
        if (file_exists($helpersPath)) {
            require_once $helpersPath;
        }

        // 读取源配置文件
        $sourceConfig = require $this->sourcePath;

        if (!is_array($sourceConfig)) {
            throw ConfigException::invalidValue('source_config', $sourceConfig, 'array');
        }

        // 替换 env() 函数调用为实际值
        $processedConfig = $this->processEnvCalls($sourceConfig);

        // 生成 PHP 代码
        $content = "<?php\n\n";
        $content .= "declare(strict_types=1);\n\n";
        $content .= "/**\n";
        $content .= " * Scramble 配置文件\n";
        $content .= " * \n";
        $content .= " * 此文件由 Scramble 扩展包自动生成\n";
        $content .= " * 生成时间: " . date('Y-m-d H:i:s') . "\n";
        $content .= " */\n\n";
        $content .= "return " . $this->varExport($processedConfig, true) . ";\n";

        return $content;
    }

    /**
     * 处理配置中的 env() 函数调用
     *
     * @param array $config
     * @return array
     */
    protected function processEnvCalls(array $config): array
    {
        $processed = [];

        foreach ($config as $key => $value) {
            if (is_array($value)) {
                $processed[$key] = $this->processEnvCalls($value);
            } else {
                $processed[$key] = $value;
            }
        }

        return $processed;
    }

    /**
     * 改进的 var_export 函数，生成更美观的代码
     *
     * @param mixed $var
     * @param bool $return
     * @return string|null
     */
    protected function varExport(mixed $var, bool $return = false): ?string
    {
        $export = var_export($var, true);

        // 美化数组格式
        $export = preg_replace('/array \(/', '[', $export);
        $export = preg_replace('/\)$/', ']', $export);
        $export = preg_replace('/\),\n/', "],\n", $export);
        $export = preg_replace('/=> \n\s+\[/', '=> [', $export);

        if ($return) {
            return $export;
        }

        echo $export;
        return null;
    }
}
