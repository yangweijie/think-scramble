# ThinkPHP 中间件分析

ThinkScramble 现在支持自动分析 ThinkPHP 中间件，识别安全方案并自动生成对应的 OpenAPI 安全定义。

## 🎯 功能特性

### 1. 中间件识别

自动识别和分析各种类型的中间件：

```php
<?php

namespace app\controller;

use think\annotation\route\Middleware;

/**
 * 安全控制器
 * 
 * @middleware auth
 * @middleware throttle:60,1
 */
class SecureController
{
    /**
     * 获取用户信息
     * 
     * @Route("users/profile", method="GET")
     * @return Response
     */
    public function profile(): Response
    {
        return json(['user' => 'data']);
    }

    /**
     * 管理员接口
     * 
     * @Route("admin/users", method="GET")
     * @middleware admin
     * @middleware log:admin_access
     * @return Response
     */
    public function adminUsers(): Response
    {
        return json(['admin' => 'data']);
    }
}
```

### 2. 安全方案生成

自动生成 OpenAPI 安全方案定义：

```yaml
components:
  securitySchemes:
    BearerAuth:
      type: http
      scheme: bearer
      bearerFormat: JWT
      description: JWT Bearer Token 认证
    
    ApiKeyAuth:
      type: apiKey
      in: header
      name: X-API-Key
      description: API Key 认证
    
    SessionAuth:
      type: apiKey
      in: cookie
      name: PHPSESSID
      description: 会话认证

security:
  - BearerAuth: []
```

### 3. 中间件类型支持

支持多种中间件类型的自动识别：

| 中间件类型 | 示例 | 安全方案 | 描述 |
|-----------|------|----------|------|
| 认证中间件 | `auth`, `custom_auth` | Bearer Token | 用户身份验证 |
| 授权中间件 | `admin`, `role`, `permission` | Bearer Token | 权限控制 |
| 频率限制 | `throttle:60,1` | - | 请求频率限制 |
| 跨域处理 | `cors` | - | CORS 支持 |
| CSRF 保护 | `csrf` | - | CSRF 防护 |
| 会话管理 | `session` | Session | 会话处理 |
| API Key | `api_key` | API Key | API 密钥认证 |
| OAuth2 | `oauth2:read,write` | OAuth2 | OAuth2 认证 |

## 🔧 使用方法

### 1. 基本使用

```php
use Yangweijie\ThinkScramble\Analyzer\MiddlewareAnalyzer;
use Yangweijie\ThinkScramble\Generator\SecuritySchemeGenerator;

// 分析控制器中间件
$middlewareAnalyzer = new MiddlewareAnalyzer();
$middlewareInfo = $middlewareAnalyzer->analyzeController(SecureController::class);

// 生成安全方案
$securityGenerator = new SecuritySchemeGenerator($config);
$securitySchemes = $securityGenerator->generateSecuritySchemes([
    SecureController::class,
    AdminController::class,
]);
```

### 2. 集成到文档生成

```php
use Yangweijie\ThinkScramble\Generator\DocumentBuilder;

$documentBuilder = new DocumentBuilder($config);

// 添加安全方案
$documentBuilder->addSecuritySchemes([
    SecureController::class,
    AdminController::class,
]);

// 生成中间件摘要
$summary = $documentBuilder->generateMiddlewareSummary([
    SecureController::class,
    AdminController::class,
]);

// 验证安全配置
$validation = $documentBuilder->validateSecurity();

$document = $documentBuilder->build();
```

### 3. 自定义中间件配置

```php
// 配置启用的安全方案
$config = [
    'security' => [
        'enabled_schemes' => [
            'BearerAuth',
            'ApiKeyAuth',
            'SessionAuth',
            'OAuth2',
        ],
    ],
];
```

## 📋 中间件注解

### 1. 类级别中间件

```php
/**
 * 控制器类
 * 
 * @middleware auth
 * @middleware throttle:60,1
 */
class UserController
{
    // 所有方法都会应用这些中间件
}
```

### 2. 方法级别中间件

```php
class UserController
{
    /**
     * 管理员专用方法
     * 
     * @middleware admin
     * @middleware audit_log:user_management
     */
    public function deleteUser(int $id): Response
    {
        // 只有这个方法应用这些中间件
    }
}
```

### 3. 参数化中间件

```php
/**
 * 频率限制示例
 * 
 * @middleware throttle:10,1    // 每分钟10次
 * @middleware role:admin,manager  // 多个角色
 * @middleware permission:read,write  // 多个权限
 */
public function sensitiveOperation(): Response
{
    return json(['status' => 'success']);
}
```

## 🛡️ 安全方案类型

### 1. Bearer Token 认证

```yaml
BearerAuth:
  type: http
  scheme: bearer
  bearerFormat: JWT
  description: JWT Bearer Token 认证
```

**使用示例**：
```php
/**
 * @middleware auth
 */
public function protectedEndpoint(): Response
{
    // 需要 Authorization: Bearer <token>
}
```

### 2. API Key 认证

```yaml
ApiKeyAuth:
  type: apiKey
  in: header
  name: X-API-Key
  description: API Key 认证
```

**使用示例**：
```php
/**
 * @middleware api_key
 */
public function apiEndpoint(): Response
{
    // 需要 X-API-Key: <key>
}
```

### 3. OAuth2 认证

```yaml
OAuth2:
  type: oauth2
  flows:
    authorizationCode:
      authorizationUrl: /oauth/authorize
      tokenUrl: /oauth/token
      scopes:
        read: 读取权限
        write: 写入权限
        admin: 管理员权限
```

**使用示例**：
```php
/**
 * @middleware oauth2:read,write
 */
public function oauthProtected(): Response
{
    // 需要 OAuth2 授权，具有 read 和 write 权限
}
```

### 4. 会话认证

```yaml
SessionAuth:
  type: apiKey
  in: cookie
  name: PHPSESSID
  description: 会话认证
```

**使用示例**：
```php
/**
 * @middleware session
 */
public function sessionProtected(): Response
{
    // 需要有效的会话 Cookie
}
```

## 📊 中间件统计

### 1. 生成统计报告

```php
$summary = $documentBuilder->generateMiddlewareSummary([
    UserController::class,
    AdminController::class,
    ApiController::class,
]);

/*
返回结果：
[
    'total_controllers' => 3,
    'middleware_usage' => [
        'auth' => 15,
        'admin' => 5,
        'throttle' => 8,
        'cors' => 3,
    ],
    'security_schemes' => [
        'BearerAuth' => [...],
        'ApiKeyAuth' => [...],
    ],
    'middleware_types' => [
        'authentication' => 15,
        'authorization' => 5,
        'rate_limiting' => 8,
        'cors' => 3,
    ],
    'coverage' => [
        'authentication' => ['count' => 15, 'percentage' => 75.0],
        'authorization' => ['count' => 5, 'percentage' => 25.0],
        'rate_limiting' => ['count' => 8, 'percentage' => 40.0],
        'cors' => ['count' => 3, 'percentage' => 15.0],
        'csrf' => ['count' => 2, 'percentage' => 10.0],
    ],
]
*/
```

### 2. 安全配置验证

```php
$validation = $documentBuilder->validateSecurity();

/*
返回结果：
[
    'valid' => true,
    'errors' => [],
    'warnings' => [
        '安全方案 BasicAuth 使用 Basic 认证，建议使用更安全的方案',
        '安全方案 QueryApiKey 在查询参数中传递 API Key，存在安全风险',
    ],
]
*/
```

## 🔍 高级功能

### 1. 自定义中间件识别

```php
class CustomMiddlewareAnalyzer extends MiddlewareAnalyzer
{
    protected function analyzeCustomMiddleware(string $middlewareName): array
    {
        $info = parent::analyzeCustomMiddleware($middlewareName);
        
        // 自定义中间件识别逻辑
        if (str_contains($middlewareName, 'jwt')) {
            $info['type'] = 'authentication';
            $info['security'] = [
                'type' => 'http',
                'scheme' => 'bearer',
                'bearerFormat' => 'JWT',
            ];
        }
        
        return $info;
    }
}
```

### 2. 自定义安全方案

```php
class CustomSecurityGenerator extends SecuritySchemeGenerator
{
    protected $predefinedSchemes = [
        'CustomAuth' => [
            'type' => 'http',
            'scheme' => 'bearer',
            'bearerFormat' => 'Custom',
            'description' => '自定义认证方案',
        ],
        // ... 其他方案
    ];
}
```

### 3. 中间件参数解析

```php
// 支持复杂的中间件参数
/**
 * @middleware throttle:requests=100,minutes=1,key=user_id
 * @middleware permission:resource=users,action=read,scope=own
 * @middleware cache:ttl=3600,tags=user_data,vary=user_id
 */
public function complexMiddleware(): Response
{
    return json(['data' => 'protected']);
}
```

## 📝 最佳实践

### 1. 中间件命名规范

```php
// 推荐的中间件命名
/**
 * @middleware auth              // 基础认证
 * @middleware auth:jwt          // JWT 认证
 * @middleware role:admin        // 角色检查
 * @middleware permission:read   // 权限检查
 * @middleware throttle:60,1     // 频率限制
 * @middleware audit:sensitive   // 审计日志
 */
```

### 2. 安全层级设计

```php
class SecurityController
{
    /**
     * 公开接口
     * @middleware cors
     */
    public function publicInfo(): Response { }

    /**
     * 需要认证
     * @middleware auth
     */
    public function userInfo(): Response { }

    /**
     * 需要权限
     * @middleware auth
     * @middleware permission:admin
     */
    public function adminInfo(): Response { }

    /**
     * 高安全级别
     * @middleware auth
     * @middleware permission:super_admin
     * @middleware audit:critical
     * @middleware throttle:5,1
     */
    public function criticalOperation(): Response { }
}
```

### 3. 性能优化

```php
// 缓存中间件分析结果
$analyzer = new MiddlewareAnalyzer();
$cache = new MiddlewareCache();

$middlewareInfo = $cache->remember($controllerClass, function() use ($analyzer, $controllerClass) {
    return $analyzer->analyzeController($controllerClass);
});
```

## 🚀 集成示例

### 完整的安全控制器

```php
<?php

namespace app\controller;

use think\annotation\Route;

/**
 * 用户管理控制器
 * 
 * @middleware auth
 * @middleware throttle:100,1
 */
class UserController
{
    /**
     * 获取用户列表
     * 
     * @Route("users", method="GET")
     * @middleware permission:user_read
     * @return Response
     */
    public function index(): Response
    {
        // 自动生成安全要求：BearerAuth
        return json(['users' => []]);
    }

    /**
     * 创建用户
     * 
     * @Route("users", method="POST")
     * @middleware permission:user_create
     * @middleware csrf
     * @middleware audit:user_create
     * @return Response
     */
    public function create(): Response
    {
        // 自动生成安全要求：BearerAuth + CSRF
        return json(['message' => 'created'], 201);
    }

    /**
     * 删除用户
     * 
     * @Route("users/{id}", method="DELETE")
     * @middleware permission:user_delete
     * @middleware audit:user_delete
     * @middleware throttle:10,1
     * @return Response
     */
    public function delete(int $id): Response
    {
        // 自动生成安全要求：BearerAuth + 严格频率限制
        return json(['message' => 'deleted']);
    }
}
```

### 生成的 OpenAPI 文档

```yaml
paths:
  /users:
    get:
      summary: 获取用户列表
      security:
        - BearerAuth: []
      responses:
        '200':
          description: 成功
    post:
      summary: 创建用户
      security:
        - BearerAuth: []
      responses:
        '201':
          description: 创建成功
  
  /users/{id}:
    delete:
      summary: 删除用户
      security:
        - BearerAuth: []
      responses:
        '200':
          description: 删除成功

components:
  securitySchemes:
    BearerAuth:
      type: http
      scheme: bearer
      bearerFormat: JWT
      description: JWT Bearer Token 认证
```

通过中间件分析，ThinkScramble 能够：

1. **自动识别安全要求** - 无需手动配置安全方案
2. **生成准确的文档** - 基于实际的中间件配置
3. **提供安全建议** - 检测潜在的安全问题
4. **统计安全覆盖** - 了解 API 的安全状况

这大大简化了 API 安全文档的维护工作，确保文档与实际的安全配置保持一致。
