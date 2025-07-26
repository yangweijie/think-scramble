<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Watcher;

/**
 * 文件监控器
 */
class FileWatcher
{
    /**
     * 监控的目录
     */
    protected array $watchDirectories = [];

    /**
     * 监控的文件扩展名
     */
    protected array $watchExtensions = ['php'];

    /**
     * 文件修改时间缓存
     */
    protected array $fileModificationTimes = [];

    /**
     * 变更回调
     */
    protected array $changeCallbacks = [];

    /**
     * 监控间隔（秒）
     */
    protected int $interval = 2;

    /**
     * 是否正在监控
     */
    protected bool $watching = false;

    /**
     * 添加监控目录
     */
    public function addDirectory(string $directory): void
    {
        if (is_dir($directory)) {
            $this->watchDirectories[] = realpath($directory);
        }
    }

    /**
     * 设置监控的文件扩展名
     */
    public function setWatchExtensions(array $extensions): void
    {
        $this->watchExtensions = $extensions;
    }

    /**
     * 设置监控间隔
     */
    public function setInterval(int $seconds): void
    {
        $this->interval = max(1, $seconds);
    }

    /**
     * 添加变更回调
     */
    public function onChange(callable $callback): void
    {
        $this->changeCallbacks[] = $callback;
    }

    /**
     * 开始监控
     */
    public function start(): void
    {
        $this->watching = true;
        
        // 初始化文件修改时间
        $this->initializeFileModificationTimes();
        
        echo "File watcher started. Monitoring directories:\n";
        foreach ($this->watchDirectories as $dir) {
            echo "  - {$dir}\n";
        }
        echo "Press Ctrl+C to stop.\n\n";

        while ($this->watching) {
            $this->checkForChanges();
            sleep($this->interval);
        }
    }

    /**
     * 停止监控
     */
    public function stop(): void
    {
        $this->watching = false;
    }

    /**
     * 检查文件变更
     */
    protected function checkForChanges(): void
    {
        $changes = [];
        
        foreach ($this->watchDirectories as $directory) {
            $files = $this->getWatchableFiles($directory);
            
            foreach ($files as $file) {
                $currentModTime = filemtime($file);
                $lastModTime = $this->fileModificationTimes[$file] ?? 0;
                
                if ($currentModTime > $lastModTime) {
                    $changes[] = [
                        'file' => $file,
                        'type' => $lastModTime === 0 ? 'created' : 'modified',
                        'timestamp' => $currentModTime,
                    ];
                    
                    $this->fileModificationTimes[$file] = $currentModTime;
                }
            }
        }

        // 检查删除的文件
        foreach ($this->fileModificationTimes as $file => $modTime) {
            if (!file_exists($file)) {
                $changes[] = [
                    'file' => $file,
                    'type' => 'deleted',
                    'timestamp' => time(),
                ];
                
                unset($this->fileModificationTimes[$file]);
            }
        }

        if (!empty($changes)) {
            $this->notifyChanges($changes);
        }
    }

    /**
     * 获取可监控的文件
     */
    protected function getWatchableFiles(string $directory): array
    {
        $files = [];
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $extension = strtolower($file->getExtension());
                
                if (in_array($extension, $this->watchExtensions)) {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
    }

    /**
     * 初始化文件修改时间
     */
    protected function initializeFileModificationTimes(): void
    {
        $this->fileModificationTimes = [];
        
        foreach ($this->watchDirectories as $directory) {
            $files = $this->getWatchableFiles($directory);
            
            foreach ($files as $file) {
                $this->fileModificationTimes[$file] = filemtime($file);
            }
        }
    }

    /**
     * 通知文件变更
     */
    protected function notifyChanges(array $changes): void
    {
        foreach ($changes as $change) {
            echo "[" . date('Y-m-d H:i:s') . "] {$change['type']}: {$change['file']}\n";
        }

        foreach ($this->changeCallbacks as $callback) {
            try {
                call_user_func($callback, $changes);
            } catch (\Exception $e) {
                echo "Error in change callback: " . $e->getMessage() . "\n";
            }
        }
    }

    /**
     * 获取监控统计信息
     */
    public function getStats(): array
    {
        return [
            'watching' => $this->watching,
            'directories' => count($this->watchDirectories),
            'files' => count($this->fileModificationTimes),
            'extensions' => $this->watchExtensions,
            'interval' => $this->interval,
        ];
    }

    /**
     * 单次检查变更
     */
    public function checkOnce(): array
    {
        if (empty($this->fileModificationTimes)) {
            $this->initializeFileModificationTimes();
            return [];
        }

        $changes = [];
        
        foreach ($this->watchDirectories as $directory) {
            $files = $this->getWatchableFiles($directory);
            
            foreach ($files as $file) {
                $currentModTime = filemtime($file);
                $lastModTime = $this->fileModificationTimes[$file] ?? 0;
                
                if ($currentModTime > $lastModTime) {
                    $changes[] = [
                        'file' => $file,
                        'type' => $lastModTime === 0 ? 'created' : 'modified',
                        'timestamp' => $currentModTime,
                    ];
                    
                    $this->fileModificationTimes[$file] = $currentModTime;
                }
            }
        }

        // 检查删除的文件
        foreach ($this->fileModificationTimes as $file => $modTime) {
            if (!file_exists($file)) {
                $changes[] = [
                    'file' => $file,
                    'type' => 'deleted',
                    'timestamp' => time(),
                ];
                
                unset($this->fileModificationTimes[$file]);
            }
        }

        return $changes;
    }

    /**
     * 过滤变更
     */
    public function filterChanges(array $changes, array $patterns = []): array
    {
        if (empty($patterns)) {
            return $changes;
        }

        return array_filter($changes, function ($change) use ($patterns) {
            foreach ($patterns as $pattern) {
                if (fnmatch($pattern, $change['file'])) {
                    return true;
                }
            }
            return false;
        });
    }

    /**
     * 批量处理变更
     */
    public function batchChanges(array $changes, int $batchSize = 10): array
    {
        return array_chunk($changes, $batchSize);
    }

    /**
     * 获取变更摘要
     */
    public function getChangeSummary(array $changes): array
    {
        $summary = [
            'total' => count($changes),
            'created' => 0,
            'modified' => 0,
            'deleted' => 0,
            'directories' => [],
            'extensions' => [],
        ];

        foreach ($changes as $change) {
            $summary[$change['type']]++;
            
            $directory = dirname($change['file']);
            if (!in_array($directory, $summary['directories'])) {
                $summary['directories'][] = $directory;
            }
            
            $extension = pathinfo($change['file'], PATHINFO_EXTENSION);
            if ($extension && !in_array($extension, $summary['extensions'])) {
                $summary['extensions'][] = $extension;
            }
        }

        return $summary;
    }
}
