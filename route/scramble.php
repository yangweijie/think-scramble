<?php

/**
 * Scramble API 文档路由
 */

use think\facade\Route;
use Yangweijie\ThinkScramble\Controller\DocsController;

// 测试路由
Route::get('docs/test', [DocsController::class, 'test'])->name('scramble.docs.test');

// API 文档 UI 路由
Route::get('docs/api', [DocsController::class, 'ui'])->name('scramble.docs.ui');

// API 文档 JSON 路由
Route::get('docs/api.json', [DocsController::class, 'json'])->name('scramble.docs.json');

// API 文档 YAML 路由（如果支持）
Route::get('docs/api.yaml', [DocsController::class, 'yaml'])->name('scramble.docs.yaml');

// 可选：重定向根文档路径到 UI
Route::get('docs', function () {
    return redirect('/docs/api');
})->name('scramble.docs.index');
