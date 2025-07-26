<?php

declare(strict_types=1);

namespace app\controller;

use think\Request;
use think\Response;

/**
 * 注解功能示例控制器
 * 
 * @Route("/api/v1")
 * @Middleware("auth")
 */
class AnnotationController
{
    /**
     * 获取用户列表
     * 
     * @Get("/users")
     * @Middleware("throttle:60,1")
     * @Validate("UserValidate", scene="list")
     * 
     * @Api {get} /api/v1/users 获取用户列表
     * @ApiParam {Number} page 页码
     * @ApiParam {Number} limit 每页数量
     * @ApiParam {String} keyword 搜索关键词
     * @ApiSuccess {Object} data 响应数据
     * @ApiSuccess {Array} data.list 用户列表
     * @ApiSuccess {Number} data.total 总数量
     */
    public function index(Request $request): Response
    {
        $page = $request->param('page/d', 1);
        $limit = $request->param('limit/d', 10);
        $keyword = $request->param('keyword', '');

        return json([
            'data' => [
                'list' => [
                    ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'],
                    ['id' => 2, 'name' => 'Jane', 'email' => 'jane@example.com'],
                ],
                'total' => 2,
                'page' => $page,
                'limit' => $limit,
            ]
        ]);
    }

    /**
     * 创建用户
     * 
     * @Post("/users")
     * @Validate("UserValidate", scene="create")
     * 
     * @Api {post} /api/v1/users 创建用户
     * @ApiParam {String} name 用户名
     * @ApiParam {String} email 邮箱地址
     * @ApiParam {String} password 密码
     * @ApiParam {Number} age 年龄
     * @ApiSuccess {Object} data 用户信息
     * @ApiError {String} message 错误信息
     */
    public function create(Request $request): Response
    {
        $data = $request->only(['name', 'email', 'password', 'age']);
        
        // 模拟创建用户
        $user = array_merge($data, ['id' => 3, 'created_at' => date('Y-m-d H:i:s')]);
        unset($user['password']); // 不返回密码

        return json(['data' => $user], 201);
    }

    /**
     * 获取用户详情
     * 
     * @Get("/users/{id}")
     * 
     * @Api {get} /api/v1/users/:id 获取用户详情
     * @ApiParam {Number} id 用户ID
     * @ApiSuccess {Object} data 用户信息
     * @ApiError 404 {String} message 用户不存在
     */
    public function show(Request $request): Response
    {
        $id = $request->param('id/d');
        
        if (!$id) {
            return json(['message' => '用户ID不能为空'], 400);
        }

        return json([
            'data' => [
                'id' => $id,
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'age' => 30,
                'created_at' => '2024-01-01 12:00:00'
            ]
        ]);
    }

    /**
     * 更新用户
     * 
     * @Put("/users/{id}")
     * @Validate("UserValidate", scene="update")
     * 
     * @Api {put} /api/v1/users/:id 更新用户信息
     * @ApiParam {Number} id 用户ID
     * @ApiParam {String} [name] 用户名
     * @ApiParam {String} [email] 邮箱地址
     * @ApiParam {Number} [age] 年龄
     * @ApiSuccess {Object} data 更新后的用户信息
     */
    public function update(Request $request): Response
    {
        $id = $request->param('id/d');
        $data = $request->only(['name', 'email', 'age']);
        
        return json([
            'data' => array_merge($data, [
                'id' => $id,
                'updated_at' => date('Y-m-d H:i:s')
            ])
        ]);
    }

    /**
     * 删除用户
     * 
     * @Delete("/users/{id}")
     * @Middleware("admin")
     * 
     * @Api {delete} /api/v1/users/:id 删除用户
     * @ApiParam {Number} id 用户ID
     * @ApiSuccess {String} message 删除成功
     * @ApiError 403 {String} message 权限不足
     */
    public function delete(Request $request): Response
    {
        $id = $request->param('id/d');
        
        return json(['message' => "用户 {$id} 删除成功"]);
    }

    /**
     * 上传用户头像
     * 
     * @Post("/users/{id}/avatar")
     * @Middleware("auth")
     * 
     * @upload avatar required jpg,png,gif max:2MB 用户头像
     * @Api {post} /api/v1/users/:id/avatar 上传用户头像
     * @ApiParam {Number} id 用户ID
     * @ApiParam {File} avatar 头像文件
     * @ApiSuccess {String} avatar_url 头像地址
     */
    public function uploadAvatar(Request $request): Response
    {
        $id = $request->param('id/d');
        $avatar = $request->file('avatar');
        
        if (!$avatar || !$avatar->isValid()) {
            return json(['message' => '头像上传失败'], 400);
        }

        return json([
            'data' => [
                'user_id' => $id,
                'avatar_url' => '/uploads/avatars/' . $avatar->getOriginalName(),
                'file_size' => $avatar->getSize(),
            ]
        ]);
    }

    /**
     * 批量操作用户
     * 
     * @Post("/users/batch")
     * @Validate("UserValidate", scene="batch")
     * 
     * @Api {post} /api/v1/users/batch 批量操作用户
     * @ApiParam {String} action 操作类型 (delete|disable|enable)
     * @ApiParam {Array} ids 用户ID数组
     * @ApiSuccess {Number} affected 影响的用户数量
     */
    public function batch(Request $request): Response
    {
        $action = $request->param('action');
        $ids = $request->param('ids/a', []);
        
        if (!in_array($action, ['delete', 'disable', 'enable'])) {
            return json(['message' => '不支持的操作类型'], 400);
        }

        return json([
            'data' => [
                'action' => $action,
                'affected' => count($ids),
                'ids' => $ids,
            ]
        ]);
    }

    /**
     * 用户统计信息
     * 
     * @Get("/users/stats")
     * @Middleware("admin")
     * 
     * @Api {get} /api/v1/users/stats 获取用户统计信息
     * @ApiParam {String} [period] 统计周期 (day|week|month|year)
     * @ApiSuccess {Object} data 统计数据
     * @ApiSuccess {Number} data.total 总用户数
     * @ApiSuccess {Number} data.active 活跃用户数
     * @ApiSuccess {Number} data.new 新增用户数
     */
    public function stats(Request $request): Response
    {
        $period = $request->param('period', 'day');
        
        return json([
            'data' => [
                'period' => $period,
                'total' => 1000,
                'active' => 800,
                'new' => 50,
                'growth_rate' => 5.2,
            ]
        ]);
    }

    /**
     * 导出用户数据
     * 
     * @Get("/users/export")
     * @Middleware({"auth", "admin"})
     * 
     * @Api {get} /api/v1/users/export 导出用户数据
     * @ApiParam {String} format 导出格式 (csv|excel|json)
     * @ApiParam {String} [fields] 导出字段，逗号分隔
     * @ApiSuccess {String} download_url 下载地址
     */
    public function export(Request $request): Response
    {
        $format = $request->param('format', 'csv');
        $fields = $request->param('fields', 'id,name,email,created_at');
        
        return json([
            'data' => [
                'format' => $format,
                'fields' => explode(',', $fields),
                'download_url' => '/downloads/users_' . date('YmdHis') . '.' . $format,
                'expires_at' => date('Y-m-d H:i:s', time() + 3600),
            ]
        ]);
    }
}
