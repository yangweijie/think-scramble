# 使用教程

本教程将指导您如何使用 ThinkScramble 生成和管理 API 文档。

## 快速开始

### 1. 创建 API 控制器

首先，创建一个简单的 API 控制器：

```php
<?php
// app/controller/Api.php

namespace app\controller;

use think\Request;
use think\Response;

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
        $user = [
            'id' => $id, 
            'name' => 'User ' . $id, 
            'email' => "user{$id}@example.com"
        ];

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
```

### 2. 配置路由

在 `route/app.php` 中添加 API 路由：

```php
<?php
// route/app.php

use think\facade\Route;

// API 路由组
Route::group('api', function () {
    Route::get('users', 'Api/users');
    Route::get('users/<id>', 'Api/user');
    Route::post('users', 'Api/createUser');
});
```

### 3. 生成文档

使用命令行生成文档：

```bash
php think scramble:generate
```

### 4. 查看文档

启动开发服务器：

```bash
php think run
```

在浏览器中访问：`http://localhost:8000/docs/api`

## 命令行工具

### 生成文档

```bash
# 基本生成
php think scramble:generate

# 指定输出文件
php think scramble:generate --output=custom-docs.json

# 生成 YAML 格式
php think scramble:generate --format=yaml

# 强制覆盖现有文件
php think scramble:generate --force

# 美化 JSON 输出
php think scramble:generate --pretty

# 验证生成的文档
php think scramble:generate --validate
```

### 导出文档

```bash
# 导出为 JSON
php think scramble:export --format=json

# 导出为 YAML
php think scramble:export --format=yaml

# 导出为 HTML
php think scramble:export --format=html

# 导出为 Postman 集合
php think scramble:export --format=postman

# 导出为 Insomnia 工作空间
php think scramble:export --format=insomnia

# 指定输出目录
php think scramble:export --format=html --output=public/api-docs

# 包含示例
php think scramble:export --format=html --include-examples

# 压缩输出
php think scramble:export --format=html --compress
```

## Web 界面

### 访问文档

默认情况下，您可以通过以下 URL 访问文档：

- **Swagger UI**: `http://your-domain/docs/api`
- **JSON 格式**: `http://your-domain/docs/api.json`
- **YAML 格式**: `http://your-domain/docs/api.yaml`

### 自定义文档路由

在配置文件中修改路由前缀：

```php
// config/scramble.php
return [
    'routes' => [
        'prefix' => 'documentation', // 改为 /documentation/api
    ],
];
```

## 高级用法

### 1. 添加请求验证

```php
<?php

namespace app\controller;

use think\Request;
use think\Response;
use think\exception\ValidateException;

class UserController
{
    /**
     * 创建用户
     * 
     * @param Request $request
     * @return Response
     * @throws ValidateException
     */
    public function create(Request $request): Response
    {
        // 验证请求数据
        $data = $request->post();
        
        $validate = \think\facade\Validate::make([
            'name' => 'require|max:50',
            'email' => 'require|email|unique:user',
            'age' => 'integer|between:1,120',
        ]);

        if (!$validate->check($data)) {
            throw new ValidateException($validate->getError());
        }

        // 创建用户逻辑
        $user = [
            'id' => rand(1000, 9999),
            'name' => $data['name'],
            'email' => $data['email'],
            'age' => $data['age'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        return json([
            'code' => 201,
            'message' => 'User created successfully',
            'data' => $user
        ]);
    }
}
```

### 2. 使用模型

```php
<?php

namespace app\controller;

use app\model\User;
use think\Request;
use think\Response;

class UserController
{
    /**
     * 获取用户列表
     * 
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        $page = $request->param('page', 1);
        $limit = $request->param('limit', 20);

        $users = User::paginate([
            'list_rows' => $limit,
            'page' => $page,
        ]);

        return json([
            'code' => 200,
            'message' => 'success',
            'data' => $users->items(),
            'pagination' => [
                'current_page' => $users->getCurrentPage(),
                'total' => $users->total(),
                'per_page' => $users->listRows(),
                'last_page' => $users->lastPage(),
            ]
        ]);
    }

    /**
     * 获取用户详情
     * 
     * @param int $id
     * @return Response
     */
    public function show(int $id): Response
    {
        $user = User::find($id);
        
        if (!$user) {
            return json([
                'code' => 404,
                'message' => 'User not found'
            ], 404);
        }

        return json([
            'code' => 200,
            'message' => 'success',
            'data' => $user
        ]);
    }
}
```

### 3. 添加中间件

```php
<?php
// app/middleware/ApiAuth.php

namespace app\middleware;

use think\Request;
use think\Response;

class ApiAuth
{
    public function handle(Request $request, \Closure $next): Response
    {
        $token = $request->header('Authorization');
        
        if (!$token || !$this->validateToken($token)) {
            return json([
                'code' => 401,
                'message' => 'Unauthorized'
            ], 401);
        }

        return $next($request);
    }

    private function validateToken(string $token): bool
    {
        // 验证 token 逻辑
        return str_starts_with($token, 'Bearer ');
    }
}
```

在路由中使用中间件：

```php
// route/app.php
Route::group('api', function () {
    Route::get('users', 'User/index');
    Route::get('users/<id>', 'User/show');
    Route::post('users', 'User/create');
})->middleware(\app\middleware\ApiAuth::class);
```

## 文档注解

### 基本注解

```php
/**
 * 用户管理接口
 * 
 * 这个接口用于管理用户信息，包括创建、查询、更新和删除用户。
 * 
 * @param Request $request HTTP 请求对象
 * @return Response JSON 响应
 * @throws ValidateException 验证异常
 * @throws \Exception 其他异常
 */
public function create(Request $request): Response
{
    // 方法实现
}
```

### 参数说明

```php
/**
 * 获取用户列表
 * 
 * @param int $page 页码，默认为 1
 * @param int $limit 每页数量，默认为 20，最大 100
 * @param string $search 搜索关键词，可选
 * @param string $sort 排序字段，可选值：id, name, created_at
 * @param string $order 排序方向，可选值：asc, desc
 * @return Response
 */
public function index(Request $request): Response
{
    // 实现代码
}
```

## 最佳实践

### 1. 统一响应格式

```php
<?php
// app/common.php

/**
 * 统一 API 响应格式
 */
function api_response($data = null, string $message = 'success', int $code = 200): \think\Response
{
    return json([
        'code' => $code,
        'message' => $message,
        'data' => $data,
        'timestamp' => time(),
    ], $code >= 400 ? $code : 200);
}

/**
 * 成功响应
 */
function api_success($data = null, string $message = 'success'): \think\Response
{
    return api_response($data, $message, 200);
}

/**
 * 错误响应
 */
function api_error(string $message = 'error', int $code = 400, $data = null): \think\Response
{
    return api_response($data, $message, $code);
}
```

### 2. 异常处理

```php
<?php
// app/ExceptionHandle.php

namespace app;

use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\Response;
use Throwable;

class ExceptionHandle extends Handle
{
    public function render($request, Throwable $e): Response
    {
        // API 请求的异常处理
        if ($request->isAjax() || str_contains($request->pathinfo(), 'api/')) {
            return $this->renderApiException($e);
        }

        return parent::render($request, $e);
    }

    protected function renderApiException(Throwable $e): Response
    {
        if ($e instanceof ValidateException) {
            return api_error($e->getMessage(), 422);
        }

        if ($e instanceof DataNotFoundException || $e instanceof ModelNotFoundException) {
            return api_error('Resource not found', 404);
        }

        if ($e instanceof HttpException) {
            return api_error($e->getMessage(), $e->getStatusCode());
        }

        // 生产环境隐藏详细错误信息
        $message = app()->isDebug() ? $e->getMessage() : 'Internal Server Error';
        return api_error($message, 500);
    }
}
```

### 3. 版本控制

```php
// route/app.php
Route::group('api/v1', function () {
    Route::resource('users', 'v1.User');
    Route::resource('posts', 'v1.Post');
});

Route::group('api/v2', function () {
    Route::resource('users', 'v2.User');
    Route::resource('posts', 'v2.Post');
});
```

## 调试和测试

### 启用调试模式

```env
SCRAMBLE_DEBUG=true
SCRAMBLE_LOG_ANALYSIS=true
SCRAMBLE_VERBOSE_ERRORS=true
```

### 验证文档

```bash
# 验证生成的文档
php think scramble:generate --validate

# 检查特定控制器
php think scramble:analyze app\\controller\\UserController
```

### 测试 API

使用生成的文档测试 API：

1. 访问 Swagger UI：`http://localhost:8000/docs/api`
2. 点击 "Try it out" 按钮
3. 填写参数并执行请求
4. 查看响应结果

## 常见问题

### 1. 路由未被检测

确保路由符合 API 路径配置：
```php
// config/scramble.php
'api_path' => 'api', // 只检测 /api/* 路由
```

### 2. 文档更新不及时

清除缓存：
```bash
php think clear
php think scramble:generate --force
```

### 3. 权限问题

检查输出目录权限：
```bash
chmod 755 public/docs/
```

---

**下一步**: 查看 [API 参考](api-reference.md) 了解所有可用的 API 和配置选项。
