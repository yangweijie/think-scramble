<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Contracts;

/**
 * 分析器接口
 * 
 * 定义代码分析器的基本契约
 */
interface AnalyzerInterface
{
    /**
     * 分析指定的文件或类
     *
     * @param string $target 分析目标（文件路径或类名）
     * @return array 分析结果
     */
    public function analyze(string $target): array;

    /**
     * 检查是否支持分析指定的目标
     *
     * @param string $target 分析目标
     * @return bool
     */
    public function supports(string $target): bool;
}
