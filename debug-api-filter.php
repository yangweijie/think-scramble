<?php

require_once __DIR__ . '/vendor/autoload.php';

use think\App;
use Yangweijie\ThinkScramble\Adapter\RouteAnalyzer;

// 初始化应用
$app = new App();
$app->initialize();

echo "=== 调试 API 路由过滤 ===\n\n";

// 创建路由分析器
$analyzer = new RouteAnalyzer($app);

// 分析所有路由
$routes = $analyzer->analyzeRoutes();

echo "总路由数量: " . count($routes) . "\n\n";

$apiCount = 0;
foreach ($routes as $i => $route) {
    $isApi = $analyzer->isApiRoute($route);
    echo "路由 {$i}: {$route['rule']} ({$route['method']}) - " . ($isApi ? "✅ API" : "❌ 非API") . "\n";
    
    if ($isApi) {
        $apiCount++;
        echo "  控制器: {$route['controller']}\n";
        echo "  方法: {$route['action']}\n";
    }
    echo "\n";
}

echo "API 路由总数: {$apiCount}\n";

echo "\n=== 调试完成 ===\n";
