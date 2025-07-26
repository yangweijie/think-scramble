<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Cache;

/**
 * 内存缓存驱动
 */
class MemoryCacheDriver implements CacheInterface
{
    /**
     * 缓存数据
     */
    protected array $cache = [];

    /**
     * 缓存过期时间
     */
    protected array $expires = [];

    /**
     * 缓存统计
     */
    protected array $stats = [
        'hits' => 0,
        'misses' => 0,
        'sets' => 0,
        'deletes' => 0,
    ];

    /**
     * 获取缓存值
     */
    public function get(string $key, mixed $default = null): mixed
    {
        // 检查是否过期
        if (isset($this->expires[$key]) && $this->expires[$key] < time()) {
            unset($this->cache[$key], $this->expires[$key]);
            $this->stats['misses']++;
            return $default;
        }

        if (array_key_exists($key, $this->cache)) {
            $this->stats['hits']++;
            return $this->cache[$key];
        }

        $this->stats['misses']++;
        return $default;
    }

    /**
     * 设置缓存值
     */
    public function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        $this->cache[$key] = $value;
        
        if ($ttl > 0) {
            $this->expires[$key] = time() + $ttl;
        } else {
            unset($this->expires[$key]);
        }

        $this->stats['sets']++;
        return true;
    }

    /**
     * 检查缓存是否存在
     */
    public function has(string $key): bool
    {
        return $this->get($key, '__NOT_FOUND__') !== '__NOT_FOUND__';
    }

    /**
     * 删除缓存
     */
    public function delete(string $key): bool
    {
        if (array_key_exists($key, $this->cache)) {
            unset($this->cache[$key], $this->expires[$key]);
            $this->stats['deletes']++;
            return true;
        }

        return false;
    }

    /**
     * 清空所有缓存
     */
    public function clear(): bool
    {
        $this->cache = [];
        $this->expires = [];
        return true;
    }

    /**
     * 批量获取缓存
     */
    public function getMultiple(array $keys, mixed $default = null): array
    {
        $result = [];
        
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        
        return $result;
    }

    /**
     * 批量设置缓存
     */
    public function setMultiple(array $values, int $ttl = 3600): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
        
        return true;
    }

    /**
     * 批量删除缓存
     */
    public function deleteMultiple(array $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
        
        return true;
    }

    /**
     * 获取缓存统计信息
     */
    public function getStats(): array
    {
        $totalMemory = 0;
        
        foreach ($this->cache as $value) {
            $totalMemory += strlen(serialize($value));
        }

        return array_merge($this->stats, [
            'total_keys' => count($this->cache),
            'memory_usage' => $totalMemory,
            'expired_keys' => $this->countExpiredKeys(),
        ]);
    }

    /**
     * 清理过期缓存
     */
    public function cleanup(): int
    {
        $cleaned = 0;
        $now = time();
        
        foreach ($this->expires as $key => $expireTime) {
            if ($expireTime < $now) {
                unset($this->cache[$key], $this->expires[$key]);
                $cleaned++;
            }
        }
        
        return $cleaned;
    }

    /**
     * 统计过期键数量
     */
    protected function countExpiredKeys(): int
    {
        $count = 0;
        $now = time();
        
        foreach ($this->expires as $expireTime) {
            if ($expireTime < $now) {
                $count++;
            }
        }
        
        return $count;
    }

    /**
     * 获取所有缓存键
     */
    public function getKeys(): array
    {
        return array_keys($this->cache);
    }

    /**
     * 获取缓存大小
     */
    public function size(): int
    {
        return count($this->cache);
    }

    /**
     * 检查内存使用情况
     */
    public function getMemoryUsage(): array
    {
        $totalSize = 0;
        $sizes = [];
        
        foreach ($this->cache as $key => $value) {
            $size = strlen(serialize($value));
            $sizes[$key] = $size;
            $totalSize += $size;
        }
        
        return [
            'total_size' => $totalSize,
            'average_size' => count($sizes) > 0 ? $totalSize / count($sizes) : 0,
            'largest_key' => count($sizes) > 0 ? array_keys($sizes, max($sizes))[0] : null,
            'largest_size' => count($sizes) > 0 ? max($sizes) : 0,
        ];
    }
}
