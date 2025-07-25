<?php

declare(strict_types=1);

namespace app\controller;

use think\Response;
use think\annotation\Route;

/**
 * 首页控制器
 * 
 * @package app\controller
 */
class Index
{
    /**
     * 首页接口
     * 获取应用基本信息
     *
     * @return Response
     */
    #[Route("GET", "api/index")]
    public function index(): Response
    {
        return json([
            'code' => 200,
            'message' => 'Hello from annotation route!',
            'data' => [
                'route_type' => 'annotation',
                'timestamp' => date('Y-m-d H:i:s'),
                'app_name' => 'ThinkPHP Scramble Demo',
                'version' => '1.0.0'
            ]
        ]);
    }
    
    /**
     * 获取系统信息
     * 
     * @return Response
     */
    #[Route("GET", "api/system/info")]
    public function systemInfo(): Response
    {
        return json([
            'code' => 200,
            'message' => 'System information',
            'data' => [
                'php_version' => PHP_VERSION,
                'thinkphp_version' => app()->version(),
                'server_time' => date('Y-m-d H:i:s'),
                'route_type' => 'annotation'
            ]
        ]);
    }
    
    /**
     * 创建测试数据
     * 
     * @return Response
     */
    #[Route("POST", "api/test/create")]
    public function createTest(): Response
    {
        return json([
            'code' => 201,
            'message' => 'Test data created via annotation route',
            'data' => [
                'id' => rand(1000, 9999),
                'created_at' => date('Y-m-d H:i:s'),
                'route_type' => 'annotation'
            ]
        ]);
    }
}
