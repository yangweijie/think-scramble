<?php

declare(strict_types=1);

use Yangweijie\ThinkScramble\Config\ScrambleConfig;

if (!function_exists('scramble_config')) {
    /**
     * 获取 Scramble 配置值
     *
     * @param string|null $key 配置键名，为 null 时返回配置实例
     * @param mixed $default 默认值
     * @return mixed
     */
    function scramble_config(?string $key = null, mixed $default = null): mixed
    {
        static $config = null;

        if ($config === null) {
            $config = ScrambleConfig::fromThinkPHP();
        }

        if ($key === null) {
            return $config;
        }

        return $config->get($key, $default);
    }
}

if (!function_exists('env')) {
    /**
     * 获取环境变量值（如果 ThinkPHP 的 env 函数不存在）
     *
     * @param string $key 环境变量键名
     * @param mixed $default 默认值
     * @return mixed
     */
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? getenv($key);

        if ($value === false) {
            return $default;
        }

        // 类型转换
        if (in_array(strtolower($value), ['true', '1', 'yes', 'on'])) {
            return true;
        }

        if (in_array(strtolower($value), ['false', '0', 'no', 'off', ''])) {
            return false;
        }

        if (is_numeric($value)) {
            return strpos($value, '.') !== false ? (float) $value : (int) $value;
        }

        if (strtolower($value) === 'null') {
            return null;
        }

        return $value;
    }
}
