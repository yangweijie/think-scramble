<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Plugin;

/**
 * 钩子管理器
 */
class HookManager
{
    /**
     * 注册的钩子
     */
    protected array $hooks = [];

    /**
     * 钩子执行统计
     */
    protected array $stats = [];

    /**
     * 注册钩子
     *
     * @param string $hookName 钩子名称
     * @param callable $callback 回调函数
     * @param int $priority 优先级（数字越小优先级越高）
     */
    public function register(string $hookName, callable $callback, int $priority = 10): void
    {
        if (!isset($this->hooks[$hookName])) {
            $this->hooks[$hookName] = [];
        }

        $this->hooks[$hookName][] = [
            'callback' => $callback,
            'priority' => $priority,
        ];

        // 按优先级排序
        usort($this->hooks[$hookName], function ($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });
    }

    /**
     * 执行钩子
     *
     * @param string $hookName 钩子名称
     * @param mixed $data 传递给钩子的数据
     * @param array $context 上下文信息
     * @return mixed 处理后的数据
     */
    public function execute(string $hookName, mixed $data = null, array $context = []): mixed
    {
        if (!isset($this->hooks[$hookName])) {
            return $data;
        }

        $startTime = microtime(true);
        $originalData = $data;

        foreach ($this->hooks[$hookName] as $hook) {
            try {
                $data = call_user_func($hook['callback'], $data, $context);
            } catch (\Exception $e) {
                // 记录错误但继续执行其他钩子
                error_log("Hook execution error in {$hookName}: " . $e->getMessage());
            }
        }

        // 记录统计信息
        $this->recordStats($hookName, microtime(true) - $startTime, $originalData, $data);

        return $data;
    }

    /**
     * 检查钩子是否存在
     */
    public function hasHook(string $hookName): bool
    {
        return isset($this->hooks[$hookName]) && !empty($this->hooks[$hookName]);
    }

    /**
     * 获取钩子数量
     */
    public function getHookCount(string $hookName): int
    {
        return isset($this->hooks[$hookName]) ? count($this->hooks[$hookName]) : 0;
    }

    /**
     * 移除钩子
     */
    public function remove(string $hookName, ?callable $callback = null): void
    {
        if (!isset($this->hooks[$hookName])) {
            return;
        }

        if ($callback === null) {
            // 移除所有钩子
            unset($this->hooks[$hookName]);
        } else {
            // 移除特定回调
            $this->hooks[$hookName] = array_filter($this->hooks[$hookName], function ($hook) use ($callback) {
                return $hook['callback'] !== $callback;
            });

            if (empty($this->hooks[$hookName])) {
                unset($this->hooks[$hookName]);
            }
        }
    }

    /**
     * 获取所有钩子
     */
    public function getAllHooks(): array
    {
        return $this->hooks;
    }

    /**
     * 获取钩子统计信息
     */
    public function getStats(): array
    {
        return $this->stats;
    }

    /**
     * 记录统计信息
     */
    protected function recordStats(string $hookName, float $executionTime, mixed $originalData, mixed $processedData): void
    {
        if (!isset($this->stats[$hookName])) {
            $this->stats[$hookName] = [
                'executions' => 0,
                'total_time' => 0,
                'average_time' => 0,
                'data_changed' => 0,
            ];
        }

        $this->stats[$hookName]['executions']++;
        $this->stats[$hookName]['total_time'] += $executionTime;
        $this->stats[$hookName]['average_time'] = $this->stats[$hookName]['total_time'] / $this->stats[$hookName]['executions'];

        if ($originalData !== $processedData) {
            $this->stats[$hookName]['data_changed']++;
        }
    }

    /**
     * 清除统计信息
     */
    public function clearStats(): void
    {
        $this->stats = [];
    }

    /**
     * 获取钩子执行报告
     */
    public function getExecutionReport(): array
    {
        $report = [
            'total_hooks' => count($this->hooks),
            'total_callbacks' => 0,
            'hook_details' => [],
        ];

        foreach ($this->hooks as $hookName => $callbacks) {
            $callbackCount = count($callbacks);
            $report['total_callbacks'] += $callbackCount;
            
            $report['hook_details'][$hookName] = [
                'callback_count' => $callbackCount,
                'stats' => $this->stats[$hookName] ?? null,
            ];
        }

        return $report;
    }

    /**
     * 预定义的钩子点
     */
    public const HOOKS = [
        // 文档生成钩子
        'before_document_build' => '文档构建前',
        'after_document_build' => '文档构建后',
        'before_path_analysis' => '路径分析前',
        'after_path_analysis' => '路径分析后',
        
        // 模型分析钩子
        'before_model_analysis' => '模型分析前',
        'after_model_analysis' => '模型分析后',
        'before_field_analysis' => '字段分析前',
        'after_field_analysis' => '字段分析后',
        
        // 中间件分析钩子
        'before_middleware_analysis' => '中间件分析前',
        'after_middleware_analysis' => '中间件分析后',
        'before_security_scheme_generation' => '安全方案生成前',
        'after_security_scheme_generation' => '安全方案生成后',
        
        // Schema 生成钩子
        'before_schema_generation' => 'Schema 生成前',
        'after_schema_generation' => 'Schema 生成后',
        'before_parameter_extraction' => '参数提取前',
        'after_parameter_extraction' => '参数提取后',
        
        // 导出钩子
        'before_export' => '导出前',
        'after_export' => '导出后',
        
        // 缓存钩子
        'before_cache_get' => '缓存获取前',
        'after_cache_get' => '缓存获取后',
        'before_cache_set' => '缓存设置前',
        'after_cache_set' => '缓存设置后',
        
        // 性能监控钩子
        'before_performance_monitor' => '性能监控前',
        'after_performance_monitor' => '性能监控后',
    ];

    /**
     * 获取可用的钩子点
     */
    public function getAvailableHooks(): array
    {
        return self::HOOKS;
    }

    /**
     * 验证钩子名称
     */
    public function isValidHook(string $hookName): bool
    {
        return array_key_exists($hookName, self::HOOKS);
    }
}
