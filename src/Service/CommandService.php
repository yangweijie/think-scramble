<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Service;

use think\Service;
use Yangweijie\ThinkScramble\Command\GenerateCommand;
use Yangweijie\ThinkScramble\Command\ExportCommand;

/**
 * 命令服务
 * 
 * 注册 Scramble 相关的命令行工具
 */
class CommandService extends Service
{
    /**
     * 注册服务
     *
     * @return void
     */
    public function register(): void
    {
        // 注册命令
        $this->commands([
            GenerateCommand::class,
            ExportCommand::class,
        ]);
    }

    /**
     * 启动服务
     *
     * @return void
     */
    public function boot(): void
    {
        // 服务启动时的操作
    }
}
