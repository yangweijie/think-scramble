# ThinkPHP 注解支持

ThinkScramble 现在完全支持 think-annotation 扩展的所有注解类型，包括路由注解、中间件注解、验证注解等，并能自动生成对应的 OpenAPI 文档。

## 🎯 支持的注解类型

### 1. 路由注解

支持所有 HTTP 方法的路由注解：

```php
/**
 * 用户控制器
 * 
 * @Route("/api/v1")
 * @Middleware("auth")
 */
class UserController
{
    /**
     * 获取用户列表
     * 
     * @Get("/users")
     * @Middleware("throttle:60,1")
     */
    public function index(Request $request): Response
    {
        // 实现逻辑
    }

    /**
     * 创建用户
     * 
     * @Post("/users")
     * @Validate("UserValidate", scene="create")
     */
    public function create(Request $request): Response
    {
        // 实现逻辑
    }

    /**
     * 更新用户
     * 
     * @Put("/users/{id}")
     * @Middleware({"auth", "admin"})
     */
    public function update(Request $request): Response
    {
        // 实现逻辑
    }

    /**
     * 删除用户
     * 
     * @Delete("/users/{id}")
     */
    public function delete(Request $request): Response
    {
        // 实现逻辑
    }
}
```

**支持的路由注解**：
- `@Route` - 通用路由注解
- `@Get` - GET 请求
- `@Post` - POST 请求
- `@Put` - PUT 请求
- `@Delete` - DELETE 请求
- `@Patch` - PATCH 请求
- `@Options` - OPTIONS 请求
- `@Head` - HEAD 请求

### 2. 中间件注解

支持单个和多个中间件配置：

```php
/**
 * 管理员控制器
 * 
 * @Middleware("auth")  // 类级别中间件
 */
class AdminController
{
    /**
     * 获取统计数据
     * 
     * @Get("/stats")
     * @Middleware("admin")  // 单个中间件
     */
    public function stats(): Response
    {
        // 实现逻辑
    }

    /**
     * 敏感操作
     * 
     * @Post("/sensitive")
     * @Middleware({"auth", "admin", "throttle:10,1"})  // 多个中间件
     */
    public function sensitive(): Response
    {
        // 实现逻辑
    }
}
```

### 3. 验证注解

支持验证器类和验证场景：

```php
/**
 * 用户注册
 * 
 * @Post("/register")
 * @Validate("UserValidate", scene="register")
 */
public function register(Request $request): Response
{
    // 验证规则会自动应用并生成 OpenAPI 参数
}

/**
 * 用户登录
 * 
 * @Post("/login")
 * @Validate("UserValidate", scene="login", batch=true)
 */
public function login(Request $request): Response
{
    // 支持批量验证
}
```

### 4. 资源路由注解

支持 RESTful 资源路由：

```php
/**
 * 文章资源控制器
 * 
 * @Resource("articles", only=["index", "show", "store", "update"])
 */
class ArticleController
{
    // 自动生成 RESTful 路由
}
```

### 5. 依赖注入注解

支持服务注入：

```php
/**
 * 用户服务控制器
 */
class UserServiceController
{
    /**
     * 获取用户信息
     * 
     * @Get("/user/{id}")
     * @Inject("UserService")
     */
    public function getUserInfo(Request $request, $userService): Response
    {
        // $userService 会自动注入
    }
}
```

### 6. API 文档注解

支持详细的 API 文档注解：

```php
/**
 * 获取用户列表
 * 
 * @Get("/users")
 * 
 * @Api {get} /api/users 获取用户列表
 * @ApiParam {Number} page 页码，默认为1
 * @ApiParam {Number} limit 每页数量，默认为10
 * @ApiParam {String} [keyword] 搜索关键词
 * @ApiParam {String} [status] 用户状态 (active|inactive)
 * 
 * @ApiSuccess {Object} data 响应数据
 * @ApiSuccess {Array} data.list 用户列表
 * @ApiSuccess {Number} data.total 总数量
 * @ApiSuccess {Number} data.page 当前页码
 * @ApiSuccess {Number} data.limit 每页数量
 * 
 * @ApiError 400 {String} message 参数错误
 * @ApiError 401 {String} message 未授权
 * @ApiError 500 {String} message 服务器错误
 */
public function index(Request $request): Response
{
    // 实现逻辑
}
```

### 7. 文件上传注解

支持文件上传参数：

```php
/**
 * 上传用户头像
 * 
 * @Post("/users/{id}/avatar")
 * @Middleware("auth")
 * 
 * @upload avatar required jpg,png,gif max:2MB 用户头像文件
 * @param {file} document 可选的文档文件
 * 
 * @Api {post} /api/users/:id/avatar 上传用户头像
 * @ApiParam {Number} id 用户ID
 * @ApiParam {File} avatar 头像文件
 * @ApiParam {File} [document] 可选的文档文件
 */
public function uploadAvatar(Request $request): Response
{
    $avatar = $request->file('avatar');
    $document = $request->file('document');
    // 处理文件上传
}
```

## 🔧 配置选项

在 `config/scramble.php` 中配置注解支持：

```php
return [
    'analysis' => [
        'parse_docblocks' => true,      // 启用注释解析
        'parse_annotations' => true,    // 启用注解解析
        'type_inference' => true,       // 启用类型推断
    ],
    
    'annotation' => [
        'route_prefix' => '',           // 全局路由前缀
        'middleware_global' => [],      // 全局中间件
        'validate_auto_extract' => true, // 自动提取验证规则
    ],
    
    'openapi' => [
        'auto_generate_tags' => true,   // 自动生成标签
        'group_by_controller' => true,  // 按控制器分组
    ],
];
```

## 📝 完整示例

```php
<?php

namespace app\controller;

use think\Request;
use think\Response;

/**
 * 用户管理控制器
 * 
 * @Route("/api/v1/users")
 * @Middleware("auth")
 */
class UserController
{
    /**
     * 获取用户列表
     * 
     * @Get("")
     * @Middleware("throttle:60,1")
     * @Validate("UserValidate", scene="list")
     * 
     * @Api {get} /api/v1/users 获取用户列表
     * @ApiParam {Number} [page=1] 页码
     * @ApiParam {Number} [limit=10] 每页数量
     * @ApiParam {String} [keyword] 搜索关键词
     * @ApiSuccess {Object} data 响应数据
     * @ApiSuccess {Array} data.list 用户列表
     */
    public function index(Request $request): Response
    {
        $page = $request->param('page/d', 1);
        $limit = $request->param('limit/d', 10);
        $keyword = $request->param('keyword', '');

        return json([
            'data' => [
                'list' => [],
                'total' => 0,
                'page' => $page,
                'limit' => $limit,
            ]
        ]);
    }

    /**
     * 创建用户
     * 
     * @Post("")
     * @Validate("UserValidate", scene="create")
     * 
     * @Api {post} /api/v1/users 创建用户
     * @ApiParam {String} name 用户名
     * @ApiParam {String} email 邮箱地址
     * @ApiParam {String} password 密码
     * @ApiSuccess {Object} data 用户信息
     */
    public function create(Request $request): Response
    {
        $data = $request->only(['name', 'email', 'password']);
        
        // 创建用户逻辑
        
        return json(['data' => $data], 201);
    }

    /**
     * 上传头像
     * 
     * @Post("/{id}/avatar")
     * @Middleware("owner")
     * 
     * @upload avatar required jpg,png,gif max:2MB 用户头像
     * @Api {post} /api/v1/users/:id/avatar 上传用户头像
     * @ApiParam {Number} id 用户ID
     * @ApiParam {File} avatar 头像文件
     */
    public function uploadAvatar(Request $request): Response
    {
        $id = $request->param('id/d');
        $avatar = $request->file('avatar');
        
        // 处理头像上传
        
        return json(['data' => ['avatar_url' => '/uploads/avatar.jpg']]);
    }
}
```

## 🚀 自动生成的功能

### 1. 路由自动注册

注解路由会自动注册到 ThinkPHP 路由系统：

```php
// 自动生成的路由
Route::get('/api/v1/users', 'UserController@index')
    ->middleware(['auth', 'throttle:60,1']);

Route::post('/api/v1/users', 'UserController@create')
    ->middleware(['auth']);
```

### 2. OpenAPI 文档自动生成

```yaml
paths:
  /api/v1/users:
    get:
      tags:
        - User
      summary: 获取用户列表
      parameters:
        - name: page
          in: query
          required: false
          schema:
            type: integer
            default: 1
        - name: limit
          in: query
          required: false
          schema:
            type: integer
            default: 10
      responses:
        200:
          description: 成功
          content:
            application/json:
              schema:
                type: object
                properties:
                  data:
                    type: object
```

### 3. 验证规则自动提取

从验证器类自动提取验证规则并生成 OpenAPI 参数：

```php
// UserValidate.php
protected $rule = [
    'name' => 'require|length:2,50',
    'email' => 'require|email',
    'password' => 'require|length:6,20',
];

// 自动生成的 OpenAPI 参数
parameters:
  - name: name
    required: true
    schema:
      type: string
      minLength: 2
      maxLength: 50
  - name: email
    required: true
    schema:
      type: string
      format: email
```

## 🔍 故障排除

### 1. 注解未被识别

- 确保启用了 `parse_annotations` 配置
- 检查注解语法是否正确
- 确保控制器方法是 public

### 2. 验证规则未提取

- 确保验证器类存在且可访问
- 检查验证器类的命名空间
- 确保验证规则格式正确

### 3. 路由未生成

- 检查路由注解的路径格式
- 确保控制器类可以被反射访问
- 查看错误日志获取详细信息

## 📋 最佳实践

1. **统一注解风格** - 在项目中保持一致的注解格式
2. **合理使用中间件** - 在类和方法级别合理配置中间件
3. **详细的 API 文档** - 使用 `@Api*` 注解提供详细的接口文档
4. **验证器复用** - 通过场景机制复用验证器
5. **文件上传规范** - 明确指定文件类型和大小限制

## 🎉 总结

ThinkScramble 的注解支持功能提供了：

- ✅ 完整的 think-annotation 兼容性
- ✅ 自动路由生成和注册
- ✅ 智能的 OpenAPI 文档生成
- ✅ 验证规则自动提取
- ✅ 文件上传参数识别
- ✅ 中间件自动应用
- ✅ 灵活的配置选项

通过注解支持，您可以用更简洁、更直观的方式定义 API，同时自动生成高质量的 OpenAPI 文档。
