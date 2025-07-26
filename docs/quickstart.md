# ⚡ 快速开始

本指南将帮助你在 5 分钟内开始使用 ThinkScramble 生成 API 文档。

## 🎯 安装

### PIE 安装（推荐）

```bash
# 安装 PIE
composer global require pie/pie

# 安装 ThinkScramble
pie install yangweijie/think-scramble

# 验证安装
scramble --version
```

### Composer 安装

```bash
composer require yangweijie/think-scramble
```

### PHAR 安装

```bash
# 下载 PHAR 文件
curl -L https://github.com/yangweijie/think-scramble/releases/latest/download/scramble.phar -o scramble.phar
chmod +x scramble.phar
sudo mv scramble.phar /usr/local/bin/scramble
```

## 🚀 第一个文档

### 1. 创建控制器

```php
<?php

namespace app\controller;

use think\Request;
use think\Response;

/**
 * 用户管理
 * @tag Users
 */
class User
{
    /**
     * 获取用户列表
     * @summary 用户列表
     * @description 获取所有用户的分页列表
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        $page = $request->param('page', 1);
        $limit = $request->param('limit', 10);
        
        $users = [
            ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'],
            ['id' => 2, 'name' => 'Jane', 'email' => 'jane@example.com'],
        ];

        return json([
            'code' => 200,
            'message' => 'success',
            'data' => $users
        ]);
    }

    /**
     * 创建用户
     * @summary 创建新用户
     * @description 创建一个新的用户账户
     * @requestBody {
     *   "name": "string",
     *   "email": "string",
     *   "password": "string"
     * }
     * @response 201 {
     *   "code": 201,
     *   "message": "User created successfully",
     *   "data": {
     *     "id": 1,
     *     "name": "string",
     *     "email": "string"
     *   }
     * }
     */
    public function create(Request $request): Response
    {
        $data = $request->post();
        
        // 创建用户逻辑
        $user = [
            'id' => 1,
            'name' => $data['name'],
            'email' => $data['email']
        ];

        return json([
            'code' => 201,
            'message' => 'User created successfully',
            'data' => $user
        ], 201);
    }
}
```

### 2. 配置路由

```php
<?php
// route/app.php

use think\facade\Route;

Route::group('api', function () {
    Route::get('users', 'User/index');
    Route::post('users', 'User/create');
    Route::get('users/:id', 'User/read');
    Route::put('users/:id', 'User/update');
    Route::delete('users/:id', 'User/delete');
});
```

### 3. 生成文档

```bash
# 进入项目目录
cd /path/to/your/thinkphp/project

# 生成基本文档
scramble --output=public/api.json

# 生成包含中间件分析的文档
scramble --output=public/api.json --middleware

# 生成 YAML 格式
scramble --output=public/api.yaml

# 导出 Postman Collection
scramble --format=postman --output=public/api.postman.json
```

### 4. 查看文档

生成的文档可以通过以下方式查看：

#### Swagger UI

```html
<!DOCTYPE html>
<html>
<head>
    <title>API Documentation</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@3.52.5/swagger-ui.css" />
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@3.52.5/swagger-ui-bundle.js"></script>
    <script>
        SwaggerUIBundle({
            url: './api.json',
            dom_id: '#swagger-ui',
            presets: [
                SwaggerUIBundle.presets.apis,
                SwaggerUIBundle.presets.standalone
            ]
        });
    </script>
</body>
</html>
```

#### Stoplight Elements

```html
<!DOCTYPE html>
<html>
<head>
    <title>API Documentation</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <script src="https://unpkg.com/@stoplight/elements/web-components.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/@stoplight/elements/styles.min.css">
</head>
<body>
    <elements-api
        apiDescriptionUrl="./api.json"
        router="hash"
        layout="sidebar"
    />
</body>
</html>
```

## 🔧 基础配置

创建 `scramble.php` 配置文件：

```php
<?php

return [
    'info' => [
        'title' => 'My API',
        'version' => '1.0.0',
        'description' => 'API documentation for my ThinkPHP application',
        'contact' => [
            'name' => 'API Support',
            'email' => 'support@example.com',
        ],
    ],
    
    'servers' => [
        [
            'url' => 'http://localhost:8000',
            'description' => 'Development server',
        ],
        [
            'url' => 'https://api.example.com',
            'description' => 'Production server',
        ],
    ],
    
    'paths' => [
        'controllers' => 'app/controller',
        'models' => 'app/model',
    ],
    
    'security' => [
        'enabled_schemes' => [
            'BearerAuth',
            'ApiKeyAuth',
        ],
    ],
];
```

## 📊 高级功能

### 实时监控

```bash
# 监控文件变化，自动重新生成文档
scramble --watch --output=public/api.json
```

### 性能统计

```bash
# 查看生成统计信息
scramble --stats
```

### 配置验证

```bash
# 验证配置文件
scramble --validate --config=scramble.php
```

## 🎯 注解示例

### 基本注解

```php
/**
 * 获取用户信息
 * @summary 用户详情
 * @description 根据用户ID获取用户详细信息
 * @param int $id 用户ID
 * @return Response
 * @throws \think\exception\HttpException 404 用户不存在
 */
public function read(int $id): Response
{
    // 实现逻辑
}
```

### 请求体注解

```php
/**
 * 更新用户信息
 * @summary 更新用户
 * @requestBody {
 *   "name": "string|required|用户姓名",
 *   "email": "string|required|email|用户邮箱",
 *   "age": "integer|min:18|max:100|用户年龄"
 * }
 */
public function update(Request $request, int $id): Response
{
    // 实现逻辑
}
```

### 响应注解

```php
/**
 * 删除用户
 * @summary 删除用户
 * @response 200 {
 *   "code": 200,
 *   "message": "User deleted successfully"
 * }
 * @response 404 {
 *   "code": 404,
 *   "message": "User not found"
 * }
 */
public function delete(int $id): Response
{
    // 实现逻辑
}
```

## 🛡️ 安全配置

### Bearer Token

```php
/**
 * 需要认证的接口
 * @security BearerAuth
 */
public function profile(Request $request): Response
{
    // 需要 Bearer Token 认证
}
```

### API Key

```php
/**
 * API Key 认证
 * @security ApiKeyAuth
 */
public function adminAction(Request $request): Response
{
    // 需要 API Key 认证
}
```

## 📤 导出格式

### Postman Collection

```bash
scramble --format=postman --output=api.postman.json
```

### Insomnia Workspace

```bash
scramble --format=insomnia --output=api.insomnia.json
```

### YAML 格式

```bash
scramble --output=api.yaml
```

## 🎉 下一步

现在你已经成功生成了第一个 API 文档！接下来可以：

- 📖 查看 [完整文档](https://yangweijie.github.io/think-scramble/) 了解更多功能
- 🥧 尝试 [PIE 安装](pie-installation.md) 获得更好的体验
- ❓ 查看 [常见问题](faq.md) 解决疑问
- 📝 阅读 [更新日志](changelog.md) 了解新功能

## ❓ 遇到问题？

- ❓ 阅读 [常见问题](faq.md)
- 🐛 [提交问题](https://github.com/yangweijie/think-scramble/issues)
- 💬 [参与讨论](https://github.com/yangweijie/think-scramble/discussions)
- 📚 查看 [完整文档](https://yangweijie.github.io/think-scramble/)
