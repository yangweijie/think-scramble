<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Service;

use Yangweijie\ThinkScramble\Contracts\ConfigInterface;
use Yangweijie\ThinkScramble\Contracts\AnalyzerInterface;
use Yangweijie\ThinkScramble\Contracts\GeneratorInterface;
use Yangweijie\ThinkScramble\Config\ScrambleConfig;
use Yangweijie\ThinkScramble\Exception\ScrambleException;
use cebe\openapi\spec\OpenApi;

/**
 * Scramble 核心服务类
 * 
 * 提供文档生成的核心业务逻辑
 */
class ScrambleService
{
    /**
     * 配置实例
     */
    protected ConfigInterface $config;

    /**
     * 分析器实例
     */
    protected ?AnalyzerInterface $analyzer = null;

    /**
     * 生成器实例
     */
    protected ?GeneratorInterface $generator = null;

    /**
     * 是否已初始化
     */
    protected bool $initialized = false;

    /**
     * 构造函数
     *
     * @param ConfigInterface $config 配置实例
     */
    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * 初始化服务
     *
     * @return void
     * @throws ScrambleException
     */
    public function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        // 验证配置
        $this->config->validate();

        // TODO: 在后续任务中初始化分析器和生成器
        
        $this->initialized = true;
    }

    /**
     * 生成 API 文档
     *
     * @param array $options 生成选项
     * @return OpenApi
     * @throws ScrambleException
     */
    public function generateDocumentation(array $options = []): OpenApi
    {
        $this->ensureInitialized();

        // TODO: 在后续任务中实现具体的生成逻辑
        throw new \RuntimeException('Document generation not implemented yet');
    }

    /**
     * 获取配置实例
     *
     * @return ConfigInterface
     */
    public function getConfig(): ConfigInterface
    {
        return $this->config;
    }

    /**
     * 设置分析器
     *
     * @param AnalyzerInterface $analyzer
     * @return static
     */
    public function setAnalyzer(AnalyzerInterface $analyzer): static
    {
        $this->analyzer = $analyzer;
        return $this;
    }

    /**
     * 获取分析器
     *
     * @return AnalyzerInterface|null
     */
    public function getAnalyzer(): ?AnalyzerInterface
    {
        return $this->analyzer;
    }

    /**
     * 设置生成器
     *
     * @param GeneratorInterface $generator
     * @return static
     */
    public function setGenerator(GeneratorInterface $generator): static
    {
        $this->generator = $generator;
        return $this;
    }

    /**
     * 获取生成器
     *
     * @return GeneratorInterface|null
     */
    public function getGenerator(): ?GeneratorInterface
    {
        return $this->generator;
    }

    /**
     * 检查服务是否已初始化
     *
     * @return bool
     */
    public function isInitialized(): bool
    {
        return $this->initialized;
    }

    /**
     * 重置服务状态
     *
     * @return void
     */
    public function reset(): void
    {
        $this->analyzer = null;
        $this->generator = null;
        $this->initialized = false;
    }

    /**
     * 获取服务状态信息
     *
     * @return array
     */
    public function getStatus(): array
    {
        return [
            'initialized' => $this->initialized,
            'has_analyzer' => $this->analyzer !== null,
            'has_generator' => $this->generator !== null,
            'config_valid' => $this->isConfigValid(),
        ];
    }

    /**
     * 确保服务已初始化
     *
     * @return void
     * @throws ScrambleException
     */
    protected function ensureInitialized(): void
    {
        if (!$this->initialized) {
            $this->initialize();
        }
    }

    /**
     * 检查配置是否有效
     *
     * @return bool
     */
    protected function isConfigValid(): bool
    {
        try {
            $this->config->validate();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
