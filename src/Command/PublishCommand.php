<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use Yangweijie\ThinkScramble\Service\AssetPublisher;

/**
 * 发布资源文件命令
 */
class PublishCommand extends Command
{
    /**
     * 配置命令
     */
    protected function configure(): void
    {
        $this->setName('scramble:publish')
            ->setDescription('Publish Scramble static assets to public directory')
            ->addOption('force', 'f', null, 'Force republish assets even if they exist');
    }

    /**
     * 执行命令
     *
     * @param Input $input 输入
     * @param Output $output 输出
     * @return int
     */
    protected function execute(Input $input, Output $output): int
    {
        try {
            $app = app();
            $publisher = new AssetPublisher($app);
            
            $force = $input->getOption('force');
            
            if ($force) {
                $output->writeln('<info>Force publishing Scramble assets...</info>');
                $success = $publisher->forcePublishAssets();
            } else {
                $output->writeln('<info>Publishing Scramble assets...</info>');
                $success = $publisher->publishAssets();
            }
            
            if ($success) {
                $output->writeln('<success>Assets published successfully!</success>');
                
                // 显示发布的文件
                $publicPath = $app->getRootPath() . 'public/swagger-ui';
                if (is_dir($publicPath)) {
                    $output->writeln('<info>Published files:</info>');
                    $files = scandir($publicPath);
                    foreach ($files as $file) {
                        if ($file !== '.' && $file !== '..') {
                            $size = filesize($publicPath . '/' . $file);
                            $output->writeln("  - {$file} (" . $this->formatBytes($size) . ")");
                        }
                    }
                }
                
                return 0;
            } else {
                $output->writeln('<error>Failed to publish assets!</error>');
                return 1;
            }
            
        } catch (\Exception $e) {
            $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
            return 1;
        }
    }
    
    /**
     * 格式化字节大小
     *
     * @param int $bytes 字节数
     * @return string
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
