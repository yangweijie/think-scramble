<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Cache;

/**
 * 文件缓存驱动
 */
class FileCacheDriver implements CacheInterface
{
    /**
     * 缓存目录
     */
    protected string $cacheDir;

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
     * 构造函数
     *
     * @param string $cacheDir 缓存目录
     */
    public function __construct(string $cacheDir = '')
    {
        $this->cacheDir = $cacheDir ?: sys_get_temp_dir() . '/think-scramble-cache';
        $this->ensureCacheDir();
    }

    /**
     * 获取缓存值
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $filePath = $this->getFilePath($key);
        
        if (!file_exists($filePath)) {
            $this->stats['misses']++;
            return $default;
        }

        $data = unserialize(file_get_contents($filePath));
        
        // 检查是否过期
        if ($data['expires'] > 0 && $data['expires'] < time()) {
            unlink($filePath);
            $this->stats['misses']++;
            return $default;
        }

        $this->stats['hits']++;
        return $data['value'];
    }

    /**
     * 设置缓存值
     */
    public function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        $filePath = $this->getFilePath($key);
        $expires = $ttl > 0 ? time() + $ttl : 0;
        
        $data = [
            'value' => $value,
            'expires' => $expires,
            'created' => time(),
        ];

        $result = file_put_contents($filePath, serialize($data), LOCK_EX) !== false;
        
        if ($result) {
            $this->stats['sets']++;
        }
        
        return $result;
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
        $filePath = $this->getFilePath($key);
        
        if (file_exists($filePath)) {
            $result = unlink($filePath);
            if ($result) {
                $this->stats['deletes']++;
            }
            return $result;
        }
        
        return true;
    }

    /**
     * 清空所有缓存
     */
    public function clear(): bool
    {
        $files = glob($this->cacheDir . '/*');
        $success = true;
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $success = unlink($file) && $success;
            }
        }
        
        return $success;
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
        $success = true;
        
        foreach ($values as $key => $value) {
            $success = $this->set($key, $value, $ttl) && $success;
        }
        
        return $success;
    }

    /**
     * 批量删除缓存
     */
    public function deleteMultiple(array $keys): bool
    {
        $success = true;
        
        foreach ($keys as $key) {
            $success = $this->delete($key) && $success;
        }
        
        return $success;
    }

    /**
     * 获取缓存统计信息
     */
    public function getStats(): array
    {
        $cacheFiles = glob($this->cacheDir . '/*');
        $totalSize = 0;
        $totalFiles = 0;
        
        foreach ($cacheFiles as $file) {
            if (is_file($file)) {
                $totalSize += filesize($file);
                $totalFiles++;
            }
        }
        
        return array_merge($this->stats, [
            'total_files' => $totalFiles,
            'total_size' => $totalSize,
            'cache_dir' => $this->cacheDir,
        ]);
    }

    /**
     * 获取缓存文件路径
     */
    protected function getFilePath(string $key): string
    {
        $hash = md5($key);
        return $this->cacheDir . '/' . $hash . '.cache';
    }

    /**
     * 确保缓存目录存在
     */
    protected function ensureCacheDir(): void
    {
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * 清理过期缓存
     */
    public function cleanup(): int
    {
        $files = glob($this->cacheDir . '/*');
        $cleaned = 0;
        
        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }
            
            $data = unserialize(file_get_contents($file));
            
            if ($data['expires'] > 0 && $data['expires'] < time()) {
                unlink($file);
                $cleaned++;
            }
        }
        
        return $cleaned;
    }

    /**
     * 生成基于文件的缓存键
     */
    public static function generateFileKey(string $filePath): string
    {
        if (!file_exists($filePath)) {
            return md5($filePath);
        }
        
        return md5($filePath . ':' . filemtime($filePath));
    }
}
