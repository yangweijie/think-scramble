<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Contracts;

/**
 * 配置接口
 * 
 * 定义配置管理的基本契约
 */
interface ConfigInterface
{
    /**
     * 获取配置值
     *
     * @param string $key 配置键名，支持点号分隔的嵌套键
     * @param mixed $default 默认值
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * 设置配置值
     *
     * @param string $key 配置键名
     * @param mixed $value 配置值
     * @return void
     */
    public function set(string $key, mixed $value): void;

    /**
     * 检查配置键是否存在
     *
     * @param string $key 配置键名
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * 获取所有配置
     *
     * @return array
     */
    public function all(): array;

    /**
     * 验证配置的有效性
     *
     * @return bool
     * @throws \Yangweijie\ThinkScramble\Exception\ConfigException
     */
    public function validate(): bool;
}
