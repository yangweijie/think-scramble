<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Plugin;

use Yangweijie\ThinkScramble\Contracts\ConfigInterface;

/**
 * 插件接口
 */
interface PluginInterface
{
    /**
     * 插件名称
     */
    public function getName(): string;

    /**
     * 插件版本
     */
    public function getVersion(): string;

    /**
     * 插件描述
     */
    public function getDescription(): string;

    /**
     * 插件作者
     */
    public function getAuthor(): string;

    /**
     * 初始化插件
     */
    public function initialize(ConfigInterface $config): void;

    /**
     * 注册钩子
     */
    public function registerHooks(HookManager $hookManager): void;

    /**
     * 插件是否启用
     */
    public function isEnabled(): bool;

    /**
     * 启用插件
     */
    public function enable(): void;

    /**
     * 禁用插件
     */
    public function disable(): void;

    /**
     * 获取插件配置
     */
    public function getConfig(): array;

    /**
     * 设置插件配置
     */
    public function setConfig(array $config): void;

    /**
     * 插件依赖
     */
    public function getDependencies(): array;

    /**
     * 插件卸载
     */
    public function uninstall(): void;
}
