<?php

declare(strict_types=1);

/**
 * Scramble 配置文件
 * 
 * 此文件由 Scramble 扩展包自动生成
 * 生成时间: 2025-07-13 03:28:13
 */

return [
  'api_path' => 'api',
  'api_domain' => NULL,
  'info' => [
    'version' => '1.0.0',
    'title' => 'API Documentation',
    'description' => '',
    'contact' => [
      'name' => '',
      'email' => '',
      'url' => '',
    ],
    'license' => [
      'name' => '',
      'url' => '',
    ],
  ],
  'servers' => NULL,
  'middleware' => [
    0 => 'web',
  ],
  'routes' => [
    'ui' => 'docs/api',
    'json' => 'docs/api.json',
    'enabled' => true,
  ],
  'cache' => [
    'enabled' => true,
    'ttl' => 3600,
    'prefix' => 'scramble',
    'store' => 'default',
  ],
  'analysis' => [
    'type_inference' => true,
    'parse_docblocks' => true,
    'exclude_paths' => [
      0 => 'vendor/*',
      1 => 'node_modules/*',
      2 => 'storage/*',
      3 => 'runtime/*',
    ],
    'include_extensions' => [
      0 => 'php',
    ],
  ],
  'security' => [
    'default_schemes' => [
    ],
    'schemes' => [
    ],
  ],
  'extensions' => [
    'document_transformers' => [
    ],
    'operation_transformers' => [
    ],
    'type_inference_extensions' => [
    ],
  ],
  'debug' => [
    'enabled' => false,
    'log_analysis' => false,
    'verbose_errors' => false,
  ],
];
