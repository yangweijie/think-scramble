<?php

declare(strict_types=1);

/**
 * Scramble 服务发现配置
 * 
 * 此文件用于 ThinkPHP 的服务发现机制
 */

return [
    // 服务提供者列表
    'providers' => [
        \Yangweijie\ThinkScramble\Service\ScrambleServiceProvider::class,
    ],

    // 服务别名
    'aliases' => [
        'Scramble' => \Yangweijie\ThinkScramble\Scramble::class,
    ],

    // 自动加载的助手函数
    'helpers' => [
        __DIR__ . '/../Config/helpers.php',
    ],

    // 配置文件发布
    'publishes' => [
        'config' => [
            __DIR__ . '/../Config/config.php' => 'config/scramble.php',
        ],
    ],
];
