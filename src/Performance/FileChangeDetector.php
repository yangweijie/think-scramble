<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Performance;

use Yangweijie\ThinkScramble\Cache\CacheManager;

/**
 * 文件变更检测器
 * 
 * 检测文件是否发生变更，用于增量解析
 */
class FileChangeDetector
{
    /**
     * 缓存管理器
     */
    protected CacheManager $cache;

    /**
     * 文件哈希缓存键前缀
     */
    protected string $hashPrefix = 'file_hash_';

    /**
     * 文件修改时间缓存键前缀
     */
    protected string $mtimePrefix = 'file_mtime_';

    /**
     * 统计信息
     */
    protected array $stats = [
        'checks' => 0,
        'changes' => 0,
        'cache_hits' => 0,
        'cache_misses' => 0,
    ];

    /**
     * 构造函数
     *
     * @param CacheManager $cache 缓存管理器
     */
    public function __construct(CacheManager $cache)
    {
        $this->cache = $cache;
    }

    /**
     * 检查文件是否发生变更
     *
     * @param string $file 文件路径
     * @return bool
     */
    public function hasFileChanged(string $file): bool
    {
        $this->stats['checks']++;

        if (!file_exists($file)) {
            return false;
        }

        $currentHash = $this->getFileHash($file);
        $cachedHash = $this->getCachedHash($file);

        if ($cachedHash === null) {
            $this->stats['cache_misses']++;
            $this->stats['changes']++;
            return true;
        }

        $this->stats['cache_hits']++;

        if ($currentHash !== $cachedHash) {
            $this->stats['changes']++;
            return true;
        }

        return false;
    }

    /**
     * 获取文件哈希值
     *
     * @param string $file 文件路径
     * @return string
     */
    public function getFileHash(string $file): string
    {
        if (!file_exists($file)) {
            return '';
        }

        // 使用文件内容和修改时间生成哈希
        $content = file_get_contents($file);
        $mtime = filemtime($file);
        $size = filesize($file);

        return md5($content . $mtime . $size);
    }

    /**
     * 获取缓存的文件哈希值
     *
     * @param string $file 文件路径
     * @return string|null
     */
    protected function getCachedHash(string $file): ?string
    {
        $cacheKey = $this->hashPrefix . md5($file);
        return $this->cache->get($cacheKey);
    }

    /**
     * 更新文件哈希缓存
     *
     * @param string $file 文件路径
     * @return bool
     */
    public function updateFileHash(string $file): bool
    {
        if (!file_exists($file)) {
            return false;
        }

        $hash = $this->getFileHash($file);
        $cacheKey = $this->hashPrefix . md5($file);

        return $this->cache->set($cacheKey, $hash, 86400); // 24小时
    }

    /**
     * 批量检查文件变更
     *
     * @param array $files 文件路径列表
     * @return array 变更结果
     */
    public function batchCheckChanges(array $files): array
    {
        $results = [];

        foreach ($files as $file) {
            $results[$file] = $this->hasFileChanged($file);
        }

        return $results;
    }

    /**
     * 获取文件修改时间
     *
     * @param string $file 文件路径
     * @return int|false
     */
    public function getFileModificationTime(string $file)
    {
        if (!file_exists($file)) {
            return false;
        }

        return filemtime($file);
    }

    /**
     * 检查文件是否比缓存新
     *
     * @param string $file 文件路径
     * @param int $cacheTime 缓存时间戳
     * @return bool
     */
    public function isFileNewerThanCache(string $file, int $cacheTime): bool
    {
        $mtime = $this->getFileModificationTime($file);
        
        if ($mtime === false) {
            return false;
        }

        return $mtime > $cacheTime;
    }

    /**
     * 清除文件的变更检测缓存
     *
     * @param string $file 文件路径
     * @return bool
     */
    public function clearFileCache(string $file): bool
    {
        $hashKey = $this->hashPrefix . md5($file);
        $mtimeKey = $this->mtimePrefix . md5($file);

        $result1 = $this->cache->delete($hashKey);
        $result2 = $this->cache->delete($mtimeKey);

        return $result1 && $result2;
    }

    /**
     * 清除所有变更检测缓存
     *
     * @return bool
     */
    public function clearAllCache(): bool
    {
        try {
            $this->cache->flushByTags(['file_hash', 'file_mtime']);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 获取统计信息
     *
     * @return array
     */
    public function getStats(): array
    {
        $hitRate = $this->stats['checks'] > 0 
            ? round(($this->stats['cache_hits'] / $this->stats['checks']) * 100, 2) 
            : 0;

        $changeRate = $this->stats['checks'] > 0 
            ? round(($this->stats['changes'] / $this->stats['checks']) * 100, 2) 
            : 0;

        return array_merge($this->stats, [
            'cache_hit_rate' => $hitRate,
            'change_rate' => $changeRate,
        ]);
    }

    /**
     * 重置统计信息
     *
     * @return void
     */
    public function resetStats(): void
    {
        $this->stats = [
            'checks' => 0,
            'changes' => 0,
            'cache_hits' => 0,
            'cache_misses' => 0,
        ];
    }

    /**
     * 监控目录变更
     *
     * @param string $directory 目录路径
     * @param array $extensions 文件扩展名过滤
     * @return array 变更的文件列表
     */
    public function monitorDirectoryChanges(string $directory, array $extensions = ['php']): array
    {
        $changedFiles = [];

        if (!is_dir($directory)) {
            return $changedFiles;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $extension = strtolower($file->getExtension());
                
                if (empty($extensions) || in_array($extension, $extensions)) {
                    $filePath = $file->getPathname();
                    
                    if ($this->hasFileChanged($filePath)) {
                        $changedFiles[] = $filePath;
                    }
                }
            }
        }

        return $changedFiles;
    }

    /**
     * 获取文件信息摘要
     *
     * @param string $file 文件路径
     * @return array
     */
    public function getFileInfo(string $file): array
    {
        if (!file_exists($file)) {
            return [
                'exists' => false,
                'error' => 'File not found',
            ];
        }

        return [
            'exists' => true,
            'path' => $file,
            'size' => filesize($file),
            'mtime' => filemtime($file),
            'hash' => $this->getFileHash($file),
            'cached_hash' => $this->getCachedHash($file),
            'has_changed' => $this->hasFileChanged($file),
        ];
    }

    /**
     * 比较两个文件的变更状态
     *
     * @param string $file1 文件1路径
     * @param string $file2 文件2路径
     * @return array 比较结果
     */
    public function compareFiles(string $file1, string $file2): array
    {
        $info1 = $this->getFileInfo($file1);
        $info2 = $this->getFileInfo($file2);

        return [
            'file1' => $info1,
            'file2' => $info2,
            'same_hash' => ($info1['hash'] ?? '') === ($info2['hash'] ?? ''),
            'same_size' => ($info1['size'] ?? 0) === ($info2['size'] ?? 0),
            'same_mtime' => ($info1['mtime'] ?? 0) === ($info2['mtime'] ?? 0),
        ];
    }

    /**
     * 预热文件哈希缓存
     *
     * @param array $files 文件列表
     * @return array 预热结果
     */
    public function warmupCache(array $files): array
    {
        $results = [];

        foreach ($files as $file) {
            try {
                if (file_exists($file)) {
                    $this->updateFileHash($file);
                    $results[$file] = 'warmed';
                } else {
                    $results[$file] = 'file_not_found';
                }
            } catch (\Exception $e) {
                $results[$file] = 'error: ' . $e->getMessage();
            }
        }

        return $results;
    }
}
