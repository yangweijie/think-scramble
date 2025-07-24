<?php

require_once __DIR__ . '/vendor/autoload.php';

use think\App;
use Yangweijie\ThinkScramble\Adapter\RouteAnalyzer;
use Yangweijie\ThinkScramble\Config\ScrambleConfig;

// 初始化应用
$app = new App();
$app->initialize();

// 初始化配置
$config = new ScrambleConfig();

// 创建路由分析器
$analyzer = new RouteAnalyzer($app, $config);

echo "=== 调试路由分析器 ===\n\n";

try {
    // 分析路由
    $routes = $analyzer->analyze();
    
    echo "检测到的路由数量: " . count($routes) . "\n\n";
    
    if (empty($routes)) {
        echo "没有检测到路由。让我们检查路由系统...\n\n";
        
        // 获取路由实例
        $route = $app->route;
        echo "路由实例类型: " . get_class($route) . "\n";
        
        // 获取所有路由规则
        $rules = $route->getRuleList();
        echo "路由规则数量: " . count($rules) . "\n";
        
        foreach ($rules as $domain => $domainRules) {
            echo "\n域名: {$domain}\n";
            echo "规则数量: " . count($domainRules) . "\n";
            
            foreach ($domainRules as $i => $rule) {
                echo "  规则 {$i}: " . get_class($rule) . "\n";
                
                if (method_exists($rule, 'getRule')) {
                    echo "    规则内容: " . $rule->getRule() . "\n";
                }
                
                if (method_exists($rule, 'getMethod')) {
                    $methods = $rule->getMethod();
                    echo "    方法: " . (is_array($methods) ? implode(',', $methods) : $methods) . "\n";
                }
                
                if (method_exists($rule, 'getRoute')) {
                    echo "    路由: " . $rule->getRoute() . "\n";
                }
            }
        }
    } else {
        echo "检测到的路由:\n";
        foreach ($routes as $route) {
            echo "- {$route['method']} {$route['uri']} -> {$route['controller']}@{$route['action']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
    echo "堆栈跟踪:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== 调试完成 ===\n";
