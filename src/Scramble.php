<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble;

use Yangweijie\ThinkScramble\Contracts\ConfigInterface;
use Yangweijie\ThinkScramble\Contracts\AnalyzerInterface;
use Yangweijie\ThinkScramble\Contracts\GeneratorInterface;
use cebe\openapi\spec\OpenApi;

/**
 * Scramble 主入口类
 * 
 * 提供简洁的静态 API 接口，作为整个扩展包的门面
 */
class Scramble
{
    /**
     * 版本号
     */
    public const VERSION = '1.0.0';

    /**
     * 配置实例
     */
    protected static ?ConfigInterface $config = null;

    /**
     * 分析器实例
     */
    protected static ?AnalyzerInterface $analyzer = null;

    /**
     * 生成器实例
     */
    protected static ?GeneratorInterface $generator = null;

    /**
     * 是否已初始化
     */
    protected static bool $initialized = false;

    /**
     * 初始化 Scramble
     *
     * @param array $config 配置选项
     * @return void
     */
    public static function init(array $config = []): void
    {
        if (static::$initialized) {
            return;
        }

        // TODO: 在后续任务中实现具体的初始化逻辑
        static::$initialized = true;
    }

    /**
     * 设置配置实例
     *
     * @param ConfigInterface $config
     * @return void
     */
    public static function setConfig(ConfigInterface $config): void
    {
        static::$config = $config;
    }

    /**
     * 获取配置实例
     *
     * @return ConfigInterface|null
     */
    public static function getConfig(): ?ConfigInterface
    {
        return static::$config;
    }

    /**
     * 设置分析器实例
     *
     * @param AnalyzerInterface $analyzer
     * @return void
     */
    public static function setAnalyzer(AnalyzerInterface $analyzer): void
    {
        static::$analyzer = $analyzer;
    }

    /**
     * 获取分析器实例
     *
     * @return AnalyzerInterface|null
     */
    public static function getAnalyzer(): ?AnalyzerInterface
    {
        return static::$analyzer;
    }

    /**
     * 设置生成器实例
     *
     * @param GeneratorInterface $generator
     * @return void
     */
    public static function setGenerator(GeneratorInterface $generator): void
    {
        static::$generator = $generator;
    }

    /**
     * 获取生成器实例
     *
     * @return GeneratorInterface|null
     */
    public static function getGenerator(): ?GeneratorInterface
    {
        return static::$generator;
    }

    /**
     * 生成 API 文档
     *
     * @param array $options 生成选项
     * @return OpenApi
     * @throws \Yangweijie\ThinkScramble\Exception\ScrambleException
     */
    public static function generate(array $options = []): OpenApi
    {
        static::ensureInitialized();

        // TODO: 在后续任务中实现具体的生成逻辑
        throw new \RuntimeException('Document generation not implemented yet');
    }

    /**
     * 获取版本号
     *
     * @return string
     */
    public static function version(): string
    {
        return static::VERSION;
    }

    /**
     * 重置所有静态状态（主要用于测试）
     *
     * @return void
     */
    public static function reset(): void
    {
        static::$config = null;
        static::$analyzer = null;
        static::$generator = null;
        static::$initialized = false;
    }

    /**
     * 确保已初始化
     *
     * @return void
     * @throws \RuntimeException
     */
    protected static function ensureInitialized(): void
    {
        if (!static::$initialized) {
            throw new \RuntimeException('Scramble not initialized. Call Scramble::init() first.');
        }
    }
}
