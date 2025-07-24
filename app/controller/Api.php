<?php

declare(strict_types=1);

namespace app\controller;

use think\Response;
use app\dto\UserResponse;
use app\dto\ApiResponse;

/**
 * API 控制器示例
 * 
 * @package app\controller
 */
class Api
{
    /**
     * 获取用户列表
     *
     * @return Response
     */
    public function users(): Response
    {
        $users = [
            ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
            ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com'],
        ];

        return json([
            'code' => 200,
            'message' => 'success',
            'data' => $users
        ]);
    }

    /**
     * 获取单个用户
     *
     * @param int $id 用户ID
     * @return Response
     */
    public function user(int $id): Response
    {
        $user = ['id' => $id, 'name' => 'User ' . $id, 'email' => "user{$id}@example.com"];

        return json([
            'code' => 200,
            'message' => 'success',
            'data' => $user
        ]);
    }

    /**
     * 创建用户
     *
     * @return Response
     */
    public function createUser(): Response
    {
        // 模拟创建用户
        $user = [
            'id' => rand(100, 999),
            'name' => 'New User',
            'email' => 'newuser@example.com'
        ];

        return json([
            'code' => 201,
            'message' => 'User created successfully',
            'data' => $user
        ]);
    }
}
