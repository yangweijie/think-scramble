<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Contracts;

use cebe\openapi\spec\OpenApi;

/**
 * 生成器接口
 * 
 * 定义文档生成器的基本契约
 */
interface GeneratorInterface
{
    /**
     * 生成 OpenAPI 文档
     *
     * @param array $analysisResults 分析结果
     * @return OpenApi
     */
    public function generate(array $analysisResults): OpenApi;

    /**
     * 设置生成选项
     *
     * @param array $options 选项配置
     * @return static
     */
    public function setOptions(array $options): static;
}
