<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Cache;

/**
 * 缓存接口
 */
interface CacheInterface
{
    /**
     * 获取缓存值
     *
     * @param string $key 缓存键
     * @param mixed $default 默认值
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * 设置缓存值
     *
     * @param string $key 缓存键
     * @param mixed $value 缓存值
     * @param int $ttl 过期时间（秒）
     * @return bool
     */
    public function set(string $key, mixed $value, int $ttl = 3600): bool;

    /**
     * 检查缓存是否存在
     *
     * @param string $key 缓存键
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * 删除缓存
     *
     * @param string $key 缓存键
     * @return bool
     */
    public function delete(string $key): bool;

    /**
     * 清空所有缓存
     *
     * @return bool
     */
    public function clear(): bool;

    /**
     * 批量获取缓存
     *
     * @param array $keys 缓存键数组
     * @param mixed $default 默认值
     * @return array
     */
    public function getMultiple(array $keys, mixed $default = null): array;

    /**
     * 批量设置缓存
     *
     * @param array $values 键值对数组
     * @param int $ttl 过期时间（秒）
     * @return bool
     */
    public function setMultiple(array $values, int $ttl = 3600): bool;

    /**
     * 批量删除缓存
     *
     * @param array $keys 缓存键数组
     * @return bool
     */
    public function deleteMultiple(array $keys): bool;

    /**
     * 获取缓存统计信息
     *
     * @return array
     */
    public function getStats(): array;
}
