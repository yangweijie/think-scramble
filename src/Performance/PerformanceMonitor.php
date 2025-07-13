<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Performance;

use Yangweijie\ThinkScramble\Cache\CacheManager;

/**
 * 性能监控器
 * 
 * 监控和分析 Scramble 的性能指标
 */
class PerformanceMonitor
{
    /**
     * 缓存管理器
     */
    protected CacheManager $cache;

    /**
     * 性能指标
     */
    protected array $metrics = [];

    /**
     * 计时器
     */
    protected array $timers = [];

    /**
     * 内存使用记录
     */
    protected array $memoryUsage = [];

    /**
     * 构造函数
     *
     * @param CacheManager $cache 缓存管理器
     */
    public function __construct(CacheManager $cache)
    {
        $this->cache = $cache;
        $this->initializeMetrics();
    }

    /**
     * 初始化性能指标
     *
     * @return void
     */
    protected function initializeMetrics(): void
    {
        $this->metrics = [
            'generation_time' => [],
            'memory_usage' => [],
            'cache_performance' => [],
            'file_operations' => [],
            'api_calls' => [],
        ];
    }

    /**
     * 开始计时
     *
     * @param string $name 计时器名称
     * @return void
     */
    public function startTimer(string $name): void
    {
        $this->timers[$name] = [
            'start' => microtime(true),
            'memory_start' => memory_get_usage(true),
        ];
    }

    /**
     * 结束计时
     *
     * @param string $name 计时器名称
     * @return array 计时结果
     */
    public function endTimer(string $name): array
    {
        if (!isset($this->timers[$name])) {
            return [
                'error' => 'Timer not found',
                'duration' => 0,
                'memory_used' => 0,
            ];
        }

        $timer = $this->timers[$name];
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);

        $result = [
            'duration' => round(($endTime - $timer['start']) * 1000, 2), // 毫秒
            'memory_used' => $endMemory - $timer['memory_start'],
            'start_time' => $timer['start'],
            'end_time' => $endTime,
        ];

        // 记录到指标中
        $this->recordMetric('generation_time', $name, $result['duration']);
        $this->recordMetric('memory_usage', $name, $result['memory_used']);

        unset($this->timers[$name]);

        return $result;
    }

    /**
     * 记录性能指标
     *
     * @param string $category 指标类别
     * @param string $name 指标名称
     * @param mixed $value 指标值
     * @return void
     */
    public function recordMetric(string $category, string $name, $value): void
    {
        if (!isset($this->metrics[$category])) {
            $this->metrics[$category] = [];
        }

        if (!isset($this->metrics[$category][$name])) {
            $this->metrics[$category][$name] = [];
        }

        $this->metrics[$category][$name][] = [
            'value' => $value,
            'timestamp' => time(),
        ];

        // 限制记录数量，避免内存溢出
        if (count($this->metrics[$category][$name]) > 1000) {
            array_shift($this->metrics[$category][$name]);
        }
    }

    /**
     * 获取性能报告
     *
     * @return array
     */
    public function getPerformanceReport(): array
    {
        return [
            'system_info' => $this->getSystemInfo(),
            'metrics_summary' => $this->getMetricsSummary(),
            'cache_performance' => $this->cache->getStats(),
            'memory_usage' => $this->getMemoryUsageReport(),
            'recommendations' => $this->getPerformanceRecommendations(),
        ];
    }

    /**
     * 获取系统信息
     *
     * @return array
     */
    protected function getSystemInfo(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'current_memory_usage' => memory_get_usage(true),
            'peak_memory_usage' => memory_get_peak_usage(true),
            'opcache_enabled' => function_exists('opcache_get_status') && opcache_get_status(),
        ];
    }

    /**
     * 获取指标摘要
     *
     * @return array
     */
    protected function getMetricsSummary(): array
    {
        $summary = [];

        foreach ($this->metrics as $category => $metrics) {
            $summary[$category] = [];

            foreach ($metrics as $name => $values) {
                if (empty($values)) {
                    continue;
                }

                $numericValues = [];
                foreach ($values as $record) {
                    $value = $record['value'];
                    if (is_array($value)) {
                        // 如果值是数组，尝试提取 duration 字段
                        $numericValues[] = $value['duration'] ?? 0;
                    } else {
                        $numericValues[] = is_numeric($value) ? $value : 0;
                    }
                }

                if (!empty($numericValues)) {
                    $summary[$category][$name] = [
                        'count' => count($numericValues),
                        'min' => min($numericValues),
                        'max' => max($numericValues),
                        'avg' => round(array_sum($numericValues) / count($numericValues), 2),
                        'total' => array_sum($numericValues),
                    ];
                } else {
                    $summary[$category][$name] = [
                        'count' => 0,
                        'min' => 0,
                        'max' => 0,
                        'avg' => 0,
                        'total' => 0,
                    ];
                }
            }
        }

        return $summary;
    }

    /**
     * 获取内存使用报告
     *
     * @return array
     */
    protected function getMemoryUsageReport(): array
    {
        return [
            'current_usage' => memory_get_usage(true),
            'peak_usage' => memory_get_peak_usage(true),
            'limit' => $this->parseMemoryLimit(ini_get('memory_limit')),
            'usage_percentage' => $this->getMemoryUsagePercentage(),
        ];
    }

    /**
     * 解析内存限制
     *
     * @param string $limit 内存限制字符串
     * @return int 字节数
     */
    protected function parseMemoryLimit(string $limit): int
    {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $value = (int) $limit;

        switch ($last) {
            case 'g':
                $value *= 1024;
                // no break
            case 'm':
                $value *= 1024;
                // no break
            case 'k':
                $value *= 1024;
        }

        return $value;
    }

    /**
     * 获取内存使用百分比
     *
     * @return float
     */
    protected function getMemoryUsagePercentage(): float
    {
        $current = memory_get_usage(true);
        $limit = $this->parseMemoryLimit(ini_get('memory_limit'));

        if ($limit <= 0) {
            return 0.0;
        }

        return round(($current / $limit) * 100, 2);
    }

    /**
     * 获取性能建议
     *
     * @return array
     */
    protected function getPerformanceRecommendations(): array
    {
        $recommendations = [];
        $memoryUsage = $this->getMemoryUsagePercentage();
        $cacheStats = $this->cache->getStats();

        // 内存使用建议
        if ($memoryUsage > 80) {
            $recommendations[] = [
                'type' => 'memory',
                'level' => 'warning',
                'message' => 'High memory usage detected. Consider increasing memory_limit or optimizing code.',
            ];
        }

        // 缓存命中率建议
        if (isset($cacheStats['hit_rate']) && $cacheStats['hit_rate'] < 70) {
            $recommendations[] = [
                'type' => 'cache',
                'level' => 'info',
                'message' => 'Low cache hit rate. Consider adjusting cache TTL or warming up cache.',
            ];
        }

        // OPcache 建议
        if (!function_exists('opcache_get_status') || !opcache_get_status()) {
            $recommendations[] = [
                'type' => 'opcache',
                'level' => 'warning',
                'message' => 'OPcache is not enabled. Enable it for better performance.',
            ];
        }

        return $recommendations;
    }

    /**
     * 记录 API 调用
     *
     * @param string $endpoint 端点
     * @param float $duration 持续时间（毫秒）
     * @param int $statusCode 状态码
     * @return void
     */
    public function recordApiCall(string $endpoint, float $duration, int $statusCode): void
    {
        $this->recordMetric('api_calls', $endpoint, [
            'duration' => $duration,
            'status_code' => $statusCode,
            'timestamp' => time(),
        ]);
    }

    /**
     * 记录文件操作
     *
     * @param string $operation 操作类型
     * @param string $file 文件路径
     * @param float $duration 持续时间（毫秒）
     * @return void
     */
    public function recordFileOperation(string $operation, string $file, float $duration): void
    {
        $this->recordMetric('file_operations', $operation, [
            'file' => $file,
            'duration' => $duration,
            'timestamp' => time(),
        ]);
    }

    /**
     * 获取慢操作报告
     *
     * @param float $threshold 阈值（毫秒）
     * @return array
     */
    public function getSlowOperationsReport(float $threshold = 1000): array
    {
        $slowOperations = [];

        foreach ($this->metrics as $category => $metrics) {
            foreach ($metrics as $name => $values) {
                foreach ($values as $record) {
                    $duration = is_array($record['value']) 
                        ? ($record['value']['duration'] ?? 0) 
                        : $record['value'];

                    if ($duration > $threshold) {
                        $slowOperations[] = [
                            'category' => $category,
                            'name' => $name,
                            'duration' => $duration,
                            'timestamp' => $record['timestamp'],
                            'details' => $record['value'],
                        ];
                    }
                }
            }
        }

        // 按持续时间排序
        usort($slowOperations, function ($a, $b) {
            return $b['duration'] <=> $a['duration'];
        });

        return $slowOperations;
    }

    /**
     * 清除性能指标
     *
     * @return void
     */
    public function clearMetrics(): void
    {
        $this->initializeMetrics();
        $this->timers = [];
        $this->memoryUsage = [];
    }

    /**
     * 导出性能数据
     *
     * @return array
     */
    public function exportData(): array
    {
        return [
            'metrics' => $this->metrics,
            'system_info' => $this->getSystemInfo(),
            'export_time' => time(),
        ];
    }

    /**
     * 导入性能数据
     *
     * @param array $data 性能数据
     * @return bool
     */
    public function importData(array $data): bool
    {
        try {
            if (isset($data['metrics']) && is_array($data['metrics'])) {
                $this->metrics = array_merge($this->metrics, $data['metrics']);
                return true;
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
}
