<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Performance;

use think\App;
use Yangweijie\ThinkScramble\Cache\CacheManager;
use Yangweijie\ThinkScramble\Contracts\ConfigInterface;
use Yangweijie\ThinkScramble\Analyzer\CodeAnalyzer;
use Yangweijie\ThinkScramble\Exception\PerformanceException;

/**
 * 增量解析器
 * 
 * 实现增量解析功能，只解析变更的文件以提高性能
 */
class IncrementalParser
{
    /**
     * ThinkPHP 应用实例
     */
    protected App $app;

    /**
     * 缓存管理器
     */
    protected CacheManager $cache;

    /**
     * 配置接口
     */
    protected ConfigInterface $config;

    /**
     * 代码分析器
     */
    protected CodeAnalyzer $analyzer;

    /**
     * 文件变更检测器
     */
    protected FileChangeDetector $changeDetector;

    /**
     * 解析结果缓存键前缀
     */
    protected string $cachePrefix = 'parse_result_';

    /**
     * 文件哈希缓存键前缀
     */
    protected string $hashPrefix = 'file_hash_';

    /**
     * 构造函数
     *
     * @param App $app ThinkPHP 应用实例
     * @param CacheManager $cache 缓存管理器
     * @param ConfigInterface $config 配置接口
     */
    public function __construct(App $app, CacheManager $cache, ConfigInterface $config)
    {
        $this->app = $app;
        $this->cache = $cache;
        $this->config = $config;
        $this->analyzer = new CodeAnalyzer();
        $this->changeDetector = new FileChangeDetector($cache);
    }

    /**
     * 增量解析文件列表
     *
     * @param array $files 文件路径列表
     * @param bool $forceRefresh 是否强制刷新
     * @return array 解析结果
     * @throws PerformanceException
     */
    public function parseFiles(array $files, bool $forceRefresh = false): array
    {
        try {
            $results = [];
            $changedFiles = [];
            $cachedFiles = [];

            foreach ($files as $file) {
                if (!file_exists($file)) {
                    continue;
                }

                $hasChanged = $forceRefresh || $this->changeDetector->hasFileChanged($file);
                
                if ($hasChanged) {
                    $changedFiles[] = $file;
                } else {
                    $cachedResult = $this->getCachedResult($file);
                    if ($cachedResult !== null) {
                        $results[$file] = $cachedResult;
                        $cachedFiles[] = $file;
                    } else {
                        $changedFiles[] = $file;
                    }
                }
            }

            // 解析变更的文件
            foreach ($changedFiles as $file) {
                $result = $this->parseFile($file);
                $results[$file] = $result;
                
                // 缓存解析结果
                $this->cacheResult($file, $result);
                
                // 更新文件哈希
                $this->changeDetector->updateFileHash($file);
            }

            return [
                'results' => $results,
                'stats' => [
                    'total_files' => count($files),
                    'changed_files' => count($changedFiles),
                    'cached_files' => count($cachedFiles),
                    'cache_hit_rate' => count($files) > 0 ? round((count($cachedFiles) / count($files)) * 100, 2) : 0,
                ],
            ];

        } catch (\Exception $e) {
            throw new PerformanceException("Incremental parsing failed: " . $e->getMessage());
        }
    }

    /**
     * 解析单个文件
     *
     * @param string $file 文件路径
     * @return array 解析结果
     * @throws PerformanceException
     */
    public function parseFile(string $file): array
    {
        try {
            if (!file_exists($file)) {
                throw new PerformanceException("File not found: {$file}");
            }

            $startTime = microtime(true);
            $result = $this->analyzer->analyzeFile($file);
            $endTime = microtime(true);

            return [
                'file' => $file,
                'result' => $result,
                'parse_time' => round(($endTime - $startTime) * 1000, 2), // 毫秒
                'timestamp' => time(),
                'file_size' => filesize($file),
                'file_hash' => $this->changeDetector->getFileHash($file),
            ];

        } catch (\Exception $e) {
            throw new PerformanceException("Failed to parse file {$file}: " . $e->getMessage());
        }
    }

    /**
     * 获取缓存的解析结果
     *
     * @param string $file 文件路径
     * @return array|null
     */
    protected function getCachedResult(string $file): ?array
    {
        $cacheKey = $this->cachePrefix . md5($file);
        return $this->cache->get($cacheKey);
    }

    /**
     * 缓存解析结果
     *
     * @param string $file 文件路径
     * @param array $result 解析结果
     * @return bool
     */
    protected function cacheResult(string $file, array $result): bool
    {
        $cacheKey = $this->cachePrefix . md5($file);
        $ttl = $this->config->get('cache.parse_result_ttl', 86400); // 24小时
        
        return $this->cache->set($cacheKey, $result, $ttl);
    }

    /**
     * 清除文件的缓存结果
     *
     * @param string $file 文件路径
     * @return bool
     */
    public function clearFileCache(string $file): bool
    {
        $cacheKey = $this->cachePrefix . md5($file);
        $hashKey = $this->hashPrefix . md5($file);
        
        $result1 = $this->cache->delete($cacheKey);
        $result2 = $this->cache->delete($hashKey);
        
        return $result1 && $result2;
    }

    /**
     * 清除所有解析缓存
     *
     * @return bool
     */
    public function clearAllCache(): bool
    {
        try {
            // 清除解析结果缓存
            $this->cache->flushByTags(['parse_result']);
            
            // 清除文件哈希缓存
            $this->cache->flushByTags(['file_hash']);
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 获取解析统计信息
     *
     * @return array
     */
    public function getStats(): array
    {
        return [
            'cache_stats' => $this->cache->getStats(),
            'change_detector_stats' => $this->changeDetector->getStats(),
        ];
    }

    /**
     * 预热解析缓存
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
                    $result = $this->parseFile($file);
                    $this->cacheResult($file, $result);
                    $this->changeDetector->updateFileHash($file);
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

    /**
     * 批量解析文件
     *
     * @param array $files 文件列表
     * @param int $batchSize 批次大小
     * @return array 解析结果
     */
    public function batchParseFiles(array $files, int $batchSize = 10): array
    {
        $results = [];
        $batches = array_chunk($files, $batchSize);
        
        foreach ($batches as $batch) {
            $batchResult = $this->parseFiles($batch);
            $results = array_merge($results, $batchResult['results']);
            
            // 可以在这里添加内存清理或进度报告
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }
        
        return $results;
    }

    /**
     * 获取文件依赖关系
     *
     * @param string $file 文件路径
     * @return array 依赖的文件列表
     */
    public function getFileDependencies(string $file): array
    {
        try {
            $result = $this->parseFile($file);
            $dependencies = [];
            
            // 从解析结果中提取依赖关系
            if (isset($result['result']['dependencies'])) {
                $dependencies = $result['result']['dependencies'];
            }
            
            return $dependencies;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * 检查文件是否需要重新解析
     *
     * @param string $file 文件路径
     * @return bool
     */
    public function needsReparsing(string $file): bool
    {
        if (!file_exists($file)) {
            return false;
        }
        
        return $this->changeDetector->hasFileChanged($file);
    }

    /**
     * 获取解析性能报告
     *
     * @param array $files 文件列表
     * @return array 性能报告
     */
    public function getPerformanceReport(array $files): array
    {
        $totalFiles = count($files);
        $changedFiles = 0;
        $cachedFiles = 0;
        $totalParseTime = 0;
        $totalFileSize = 0;
        
        foreach ($files as $file) {
            if (!file_exists($file)) {
                continue;
            }
            
            $totalFileSize += filesize($file);
            
            if ($this->needsReparsing($file)) {
                $changedFiles++;
            } else {
                $cachedFiles++;
            }
        }
        
        return [
            'total_files' => $totalFiles,
            'changed_files' => $changedFiles,
            'cached_files' => $cachedFiles,
            'cache_hit_rate' => $totalFiles > 0 ? round(($cachedFiles / $totalFiles) * 100, 2) : 0,
            'total_file_size' => $totalFileSize,
            'average_file_size' => $totalFiles > 0 ? round($totalFileSize / $totalFiles, 2) : 0,
        ];
    }
}
