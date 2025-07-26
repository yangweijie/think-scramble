<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Config;

use Yangweijie\ThinkScramble\Contracts\ConfigInterface;
use Yangweijie\ThinkScramble\Exception\ConfigException;

/**
 * Scramble 配置管理类
 * 
 * 提供配置读取、验证、缓存等功能
 */
class ScrambleConfig implements ConfigInterface
{
    /**
     * 配置数据
     */
    protected array $config = [];

    /**
     * 配置缓存
     */
    protected static ?array $cache = null;

    /**
     * 默认配置
     */
    protected array $defaults = [];

    /**
     * 必需的配置键
     */
    protected array $requiredKeys = [
        'api_path',
        'info.version',
        'info.title',
    ];

    /**
     * 构造函数
     *
     * @param array $config 配置数组
     */
    public function __construct(array $config = [])
    {
        $this->loadDefaults();
        $this->config = $this->mergeRecursive($this->defaults, $config);
        $this->applyEnvironmentOverrides();
    }

    /**
     * 创建配置实例
     *
     * @param array $config 配置数组
     * @return static
     */
    public static function make(array $config = []): static
    {
        return new static($config);
    }

    /**
     * 从文件加载配置
     *
     * @param string $path 配置文件路径
     * @return static
     * @throws ConfigException
     */
    public static function fromFile(string $path): static
    {
        if (!file_exists($path)) {
            throw ConfigException::missingKey("Configuration file not found: {$path}");
        }

        $config = require $path;

        if (!is_array($config)) {
            throw ConfigException::invalidValue($path, $config, 'array');
        }

        return new static($config);
    }

    /**
     * 获取配置值
     *
     * @param string $key 配置键名，支持点号分隔的嵌套键
     * @param mixed $default 默认值
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->getNestedValue($this->config, $key, $default);
    }

    /**
     * 设置配置值
     *
     * @param string $key 配置键名
     * @param mixed $value 配置值
     * @return void
     */
    public function set(string $key, mixed $value): void
    {
        $this->setNestedValue($this->config, $key, $value);
        
        // 清除缓存
        static::$cache = null;
    }

    /**
     * 检查配置键是否存在
     *
     * @param string $key 配置键名
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->getNestedValue($this->config, $key, '__NOT_FOUND__') !== '__NOT_FOUND__';
    }

    /**
     * 获取所有配置
     *
     * @return array
     */
    public function all(): array
    {
        return $this->config;
    }

    /**
     * 验证配置的有效性
     *
     * @return bool
     * @throws ConfigException
     */
    public function validate(): bool
    {
        try {
            // 检查必需的配置键
            foreach ($this->requiredKeys as $key) {
                if (!$this->has($key)) {
                    throw ConfigException::missingKey($key);
                }
            }

            // 验证 API 路径
            $apiPath = $this->get('api_path');
            if (!is_string($apiPath) || empty($apiPath)) {
                throw ConfigException::invalidValue('api_path', $apiPath, 'non-empty string');
            }

            // 验证版本号
            $version = $this->get('info.version');
            if (!is_string($version) || trim($version) === '') {
                throw ConfigException::invalidValue('info.version', $version, 'non-empty string');
            }

            // 验证标题
            $title = $this->get('info.title');
            if (!is_string($title) || trim($title) === '') {
                throw ConfigException::invalidValue('info.title', $title, 'non-empty string');
            }

            // 验证中间件配置
            $middleware = $this->get('middleware', []);
            if (!is_array($middleware)) {
                throw ConfigException::invalidValue('middleware', $middleware, 'array');
            }

            // 验证缓存配置
            $cacheEnabled = $this->get('cache.enabled', true);
            if (!is_bool($cacheEnabled)) {
                throw ConfigException::invalidValue('cache.enabled', $cacheEnabled, 'boolean');
            }

            $cacheTtl = $this->get('cache.ttl', 3600);
            if (!is_int($cacheTtl) || $cacheTtl < 0) {
                throw ConfigException::invalidValue('cache.ttl', $cacheTtl, 'positive integer');
            }

            return true;
        } catch (ConfigException $e) {
            return false;
        }
    }

    /**
     * 合并配置
     *
     * @param array $config 要合并的配置
     * @return static
     */
    public function merge(array $config): static
    {
        $this->config = array_merge_recursive($this->config, $config);
        static::$cache = null;
        
        return $this;
    }

    /**
     * 获取缓存的配置
     *
     * @return array
     */
    public function getCached(): array
    {
        if (static::$cache === null) {
            static::$cache = $this->config;
        }

        return static::$cache;
    }

    /**
     * 清除配置缓存
     *
     * @return void
     */
    public static function clearCache(): void
    {
        static::$cache = null;
    }

    /**
     * 加载默认配置
     *
     * @return void
     */
    protected function loadDefaults(): void
    {
        $defaultConfigPath = __DIR__ . '/config.php';

        if (file_exists($defaultConfigPath)) {
            $this->defaults = require $defaultConfigPath;
        } else {
            $this->defaults = [];
        }
    }

    /**
     * 获取嵌套配置值
     *
     * @param array $array 配置数组
     * @param string $key 键名
     * @param mixed $default 默认值
     * @return mixed
     */
    protected function getNestedValue(array $array, string $key, mixed $default = null): mixed
    {
        if (strpos($key, '.') === false) {
            return $array[$key] ?? $default;
        }

        $keys = explode('.', $key);
        $value = $array;

        foreach ($keys as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * 设置嵌套配置值
     *
     * @param array &$array 配置数组引用
     * @param string $key 键名
     * @param mixed $value 值
     * @return void
     */
    protected function setNestedValue(array &$array, string $key, mixed $value): void
    {
        if (strpos($key, '.') === false) {
            $array[$key] = $value;
            return;
        }

        $keys = explode('.', $key);
        $current = &$array;

        foreach ($keys as $segment) {
            if (!isset($current[$segment]) || !is_array($current[$segment])) {
                $current[$segment] = [];
            }
            $current = &$current[$segment];
        }

        $current = $value;
    }

    /**
     * 应用环境变量覆盖
     *
     * @return void
     */
    protected function applyEnvironmentOverrides(): void
    {
        // 环境变量映射表
        $envMappings = [
            'SCRAMBLE_API_PATH' => 'api_path',
            'SCRAMBLE_API_DOMAIN' => 'api_domain',
            'SCRAMBLE_API_VERSION' => 'info.version',
            'SCRAMBLE_API_TITLE' => 'info.title',
            'SCRAMBLE_API_DESCRIPTION' => 'info.description',
            'SCRAMBLE_CONTACT_NAME' => 'info.contact.name',
            'SCRAMBLE_CONTACT_EMAIL' => 'info.contact.email',
            'SCRAMBLE_CONTACT_URL' => 'info.contact.url',
            'SCRAMBLE_LICENSE_NAME' => 'info.license.name',
            'SCRAMBLE_LICENSE_URL' => 'info.license.url',
            'SCRAMBLE_DOCS_UI_PATH' => 'routes.ui',
            'SCRAMBLE_DOCS_JSON_PATH' => 'routes.json',
            'SCRAMBLE_DOCS_ENABLED' => 'routes.enabled',
            'SCRAMBLE_CACHE_ENABLED' => 'cache.enabled',
            'SCRAMBLE_CACHE_TTL' => 'cache.ttl',
            'SCRAMBLE_CACHE_PREFIX' => 'cache.prefix',
            'SCRAMBLE_CACHE_STORE' => 'cache.store',
            'SCRAMBLE_TYPE_INFERENCE' => 'analysis.type_inference',
            'SCRAMBLE_PARSE_DOCBLOCKS' => 'analysis.parse_docblocks',
            'SCRAMBLE_DEBUG' => 'debug.enabled',
            'SCRAMBLE_LOG_ANALYSIS' => 'debug.log_analysis',
            'SCRAMBLE_VERBOSE_ERRORS' => 'debug.verbose_errors',
        ];

        foreach ($envMappings as $envKey => $configKey) {
            $envValue = $this->getEnvironmentValue($envKey);
            if ($envValue !== null) {
                $this->setNestedValue($this->config, $configKey, $envValue);
            }
        }
    }

    /**
     * 获取环境变量值并转换类型
     *
     * @param string $key 环境变量键名
     * @return mixed
     */
    protected function getEnvironmentValue(string $key): mixed
    {
        $value = $_ENV[$key] ?? getenv($key);

        if ($value === false || $value === null) {
            return null;
        }

        // 类型转换
        return $this->convertEnvironmentValue($value);
    }

    /**
     * 转换环境变量值的类型
     *
     * @param string $value 环境变量值
     * @return mixed
     */
    protected function convertEnvironmentValue(string $value): mixed
    {
        // 布尔值转换
        if (in_array(strtolower($value), ['true', '1', 'yes', 'on'])) {
            return true;
        }

        if (in_array(strtolower($value), ['false', '0', 'no', 'off', ''])) {
            return false;
        }

        // 数字转换
        if (is_numeric($value)) {
            return strpos($value, '.') !== false ? (float) $value : (int) $value;
        }

        // 空值转换
        if (strtolower($value) === 'null') {
            return null;
        }

        // 返回字符串
        return $value;
    }

    /**
     * 从 ThinkPHP 配置系统加载配置
     *
     * @param string $configKey ThinkPHP 配置键名
     * @return static
     */
    public static function fromThinkPHP(string $configKey = 'scramble'): static
    {
        // 检查是否在 ThinkPHP 环境中
        if (function_exists('config')) {
            $config = config($configKey, []);
            return new static(is_array($config) ? $config : []);
        }

        // 如果不在 ThinkPHP 环境中，返回默认配置
        return new static();
    }

    /**
     * 检查是否为调试模式
     *
     * @return bool
     */
    public function isDebugMode(): bool
    {
        return (bool) $this->get('app.debug', false);
    }

    /**
     * 转换为数组
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->config;
    }

    /**
     * 从数组创建配置
     *
     * @param array $config 配置数组
     * @return static
     */
    public static function fromArray(array $config): static
    {
        return new static($config);
    }

    /**
     * 重置配置为默认值
     *
     * @return void
     */
    public function reset(): void
    {
        $this->defaults = [];
        $this->loadDefaults();
        $this->config = $this->defaults;
    }

    /**
     * 递归合并数组
     *
     * @param array $array1 基础数组
     * @param array $array2 要合并的数组
     * @return array
     */
    protected function mergeRecursive(array $array1, array $array2): array
    {
        $merged = $array1;

        foreach ($array2 as $key => $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = $this->mergeRecursive($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    /**
     * 获取 ThinkPHP 配置值
     *
     * @param string $key 配置键名
     * @param mixed $default 默认值
     * @return mixed
     */
    protected function getThinkPHPConfig(string $key, mixed $default = null): mixed
    {
        if (function_exists('config')) {
            return config($key, $default);
        }

        return $default;
    }
}
