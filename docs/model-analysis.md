# ThinkPHP 模型分析

ThinkScramble 现在支持自动分析 ThinkPHP 模型，提取字段信息、关联关系、验证规则等，并自动生成对应的 OpenAPI Schema 定义。

## 🎯 功能特性

### 1. 字段分析

自动分析模型字段并生成对应的 OpenAPI Schema：

```php
<?php

namespace app\model;

use think\Model;

/**
 * 用户模型
 * 
 * @property int $id 用户ID
 * @property string $username 用户名
 * @property string $email 邮箱地址
 * @property int $age 年龄
 * @property int $status 状态
 */
class UserModel extends Model
{
    /**
     * 字段类型定义
     */
    protected $type = [
        'id' => 'integer',
        'username' => 'string',
        'email' => 'string',
        'age' => 'integer',
        'status' => 'integer',
    ];

    /**
     * 字段 Schema 定义
     */
    protected $schema = [
        'id' => [
            'type' => 'int',
            'comment' => '用户ID',
        ],
        'username' => [
            'type' => 'varchar(50)',
            'comment' => '用户名',
        ],
        'email' => [
            'type' => 'varchar(100)',
            'comment' => '邮箱地址',
        ],
        'age' => [
            'type' => 'int',
            'comment' => '年龄',
        ],
        'status' => [
            'type' => 'tinyint',
            'comment' => '状态',
            'default' => 1,
        ],
    ];
}
```

### 2. 关联关系分析

自动识别模型之间的关联关系：

```php
class UserModel extends Model
{
    /**
     * 获取用户的文章
     * 
     * @hasMany ArticleModel
     * @return \think\model\relation\HasMany
     */
    public function articles()
    {
        return $this->hasMany(ArticleModel::class, 'user_id', 'id');
    }

    /**
     * 获取用户的个人资料
     * 
     * @hasOne ProfileModel
     * @return \think\model\relation\HasOne
     */
    public function profile()
    {
        return $this->hasOne(ProfileModel::class, 'user_id', 'id');
    }

    /**
     * 获取用户的角色
     * 
     * @belongsToMany RoleModel
     * @return \think\model\relation\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(RoleModel::class, 'user_roles', 'user_id', 'role_id');
    }
}
```

### 3. 验证规则提取

自动提取模型验证规则并生成 OpenAPI 参数：

```php
class UserModel extends Model
{
    /**
     * 验证规则
     */
    protected $rule = [
        'username' => 'require|length:3,50|alphaNum|unique:users',
        'email' => 'require|email|unique:users',
        'password' => 'require|length:6,20',
        'age' => 'number|between:1,120',
        'status' => 'in:0,1',
        'phone' => 'mobile',
    ];

    /**
     * 验证消息
     */
    protected $message = [
        'username.require' => '用户名不能为空',
        'username.length' => '用户名长度必须在3-50个字符之间',
        'email.require' => '邮箱不能为空',
        'email.email' => '邮箱格式不正确',
        // ...
    ];
}
```

### 4. 时间戳和软删除

自动处理时间戳字段和软删除：

```php
use think\model\concern\SoftDelete;

class UserModel extends Model
{
    use SoftDelete;

    /**
     * 自动时间戳
     */
    protected $autoWriteTimestamp = true;

    /**
     * 创建时间字段
     */
    protected $createTime = 'created_at';

    /**
     * 更新时间字段
     */
    protected $updateTime = 'updated_at';

    /**
     * 软删除时间字段
     */
    protected $deleteTime = 'deleted_at';
}
```

## 🔧 使用方法

### 1. 基本使用

```php
use Yangweijie\ThinkScramble\Analyzer\ModelAnalyzer;
use Yangweijie\ThinkScramble\Generator\ModelSchemaGenerator;

// 分析单个模型
$modelAnalyzer = new ModelAnalyzer();
$modelInfo = $modelAnalyzer->analyzeModel(UserModel::class);

// 生成 OpenAPI Schema
$schemaGenerator = new ModelSchemaGenerator($config);
$schema = $schemaGenerator->generateSchema(UserModel::class);
```

### 2. 批量分析

```php
// 批量生成多个模型的 Schema
$modelClasses = [
    UserModel::class,
    ArticleModel::class,
    CategoryModel::class,
];

$schemas = $schemaGenerator->generateMultipleSchemas($modelClasses);
```

### 3. 集成到文档生成

```php
use Yangweijie\ThinkScramble\Generator\DocumentBuilder;

$documentBuilder = new DocumentBuilder($config);

// 自动发现并添加模型 Schema
$documentBuilder->autoDiscoverModels('app/model');

// 或手动添加特定模型
$documentBuilder->addModelSchemas([
    UserModel::class,
    ArticleModel::class,
]);

$document = $documentBuilder->build();
```

## 📋 生成的 OpenAPI Schema

### 基本字段

```yaml
User:
  type: object
  title: User
  description: User 模型 (表: users)
  properties:
    id:
      type: integer
      description: 用户ID
      example: 1
    username:
      type: string
      description: 用户名
      maxLength: 50
      example: "示例名称"
    email:
      type: string
      format: email
      description: 邮箱地址
      example: "user@example.com"
    age:
      type: integer
      description: 年龄
      minimum: 1
      maximum: 120
      example: 25
    status:
      type: integer
      description: 状态
      enum: [0, 1]
      example: 1
  required:
    - username
    - email
```

### 时间戳字段

```yaml
created_at:
  type: string
  format: date-time
  description: 创建时间
  example: "2024-01-01T12:00:00Z"
updated_at:
  type: string
  format: date-time
  description: 更新时间
  example: "2024-01-01T12:00:00Z"
```

### 关联字段

```yaml
articles:
  type: array
  items:
    $ref: "#/components/schemas/Article"
  description: 一对多关联到 Article

profile:
  $ref: "#/components/schemas/Profile"
  description: 一对一关联到 Profile

roles:
  type: array
  items:
    $ref: "#/components/schemas/Role"
  description: 多对多关联到 Role
```

## 🎛️ 配置选项

### Schema 生成选项

```php
$options = [
    'include_relations' => true,    // 包含关联字段
    'include_timestamps' => true,   // 包含时间戳字段
    'include_hidden' => false,      // 包含隐藏字段
    'fields' => ['id', 'name'],     // 只包含指定字段
];

$schema = $schemaGenerator->generateSchema(UserModel::class, $options);
```

### 字段类型映射

支持的数据库字段类型自动映射：

| 数据库类型 | OpenAPI 类型 | 格式 |
|-----------|-------------|------|
| int, integer, bigint | integer | - |
| float, double, decimal | number | - |
| varchar, char, text | string | - |
| datetime, timestamp | string | date-time |
| date | string | date |
| time | string | time |
| json | object | - |
| bool, boolean | boolean | - |
| enum | string | enum |
| set | array | - |

### 验证规则映射

支持的验证规则自动转换：

| 验证规则 | OpenAPI 约束 |
|---------|-------------|
| require | required: true |
| length:min,max | minLength, maxLength |
| min:value | minimum |
| max:value | maximum |
| between:min,max | minimum, maximum |
| in:a,b,c | enum: [a,b,c] |
| email | format: email |
| url | format: uri |
| date | format: date |
| mobile | pattern: 手机号正则 |

## 🔍 高级功能

### 1. 自定义字段处理

```php
class CustomModelAnalyzer extends ModelAnalyzer
{
    protected function parseFieldDefinition(string $field, $definition): array
    {
        $info = parent::parseFieldDefinition($field, $definition);
        
        // 自定义字段处理逻辑
        if ($field === 'avatar') {
            $info['format'] = 'uri';
            $info['description'] = '头像URL地址';
        }
        
        return $info;
    }
}
```

### 2. 关联关系自定义

```php
class CustomRelationAnalyzer extends ModelRelationAnalyzer
{
    protected function analyzeFromMethodName(ReflectionMethod $method): ?array
    {
        $relation = parent::analyzeFromMethodName($method);
        
        // 自定义关联推断逻辑
        if ($relation && $relation['confidence'] === 'low') {
            // 提高推断准确性
            $relation['confidence'] = 'medium';
        }
        
        return $relation;
    }
}
```

### 3. Schema 自定义

```php
class CustomSchemaGenerator extends ModelSchemaGenerator
{
    protected function generateExample(array $modelInfo, array $options = []): array
    {
        $example = parent::generateExample($modelInfo, $options);
        
        // 自定义示例数据
        if (isset($example['password'])) {
            unset($example['password']); // 移除敏感字段
        }
        
        return $example;
    }
}
```

## 📝 最佳实践

### 1. 模型定义规范

```php
class UserModel extends Model
{
    // 1. 明确定义表名
    protected $table = 'users';
    
    // 2. 定义字段类型
    protected $type = [
        'id' => 'integer',
        'username' => 'string',
        // ...
    ];
    
    // 3. 定义字段 Schema（可选，用于更详细的信息）
    protected $schema = [
        'username' => [
            'type' => 'varchar(50)',
            'comment' => '用户名',
        ],
        // ...
    ];
    
    // 4. 定义验证规则
    protected $rule = [
        'username' => 'require|length:3,50',
        // ...
    ];
    
    // 5. 使用 @property 注释
    /**
     * @property int $id 用户ID
     * @property string $username 用户名
     */
}
```

### 2. 关联关系注释

```php
/**
 * 获取用户文章
 * 
 * @hasMany ArticleModel
 * @return HasMany
 */
public function articles()
{
    return $this->hasMany(ArticleModel::class);
}
```

### 3. 性能优化

```php
// 使用缓存避免重复分析
$schemaGenerator->generateSchema(UserModel::class);
$schemaGenerator->generateSchema(UserModel::class); // 使用缓存

// 批量处理提高效率
$schemas = $schemaGenerator->generateMultipleSchemas($modelClasses);

// 清除缓存释放内存
$schemaGenerator->clearCache();
```

## 🚀 集成示例

### 控制器中使用

```php
class UserController
{
    /**
     * 获取用户信息
     * 
     * @Get("/users/{id}")
     * @return UserModel 用户信息
     */
    public function show(int $id): Response
    {
        $user = UserModel::find($id);
        return json($user);
    }

    /**
     * 创建用户
     * 
     * @Post("/users")
     * @param UserModel $user 用户数据
     * @return UserModel 创建的用户
     */
    public function create(Request $request): Response
    {
        $user = UserModel::create($request->post());
        return json($user, 201);
    }
}
```

### 自动文档生成

通过模型分析，ThinkScramble 会自动：

1. **识别返回类型**：`@return UserModel` → 生成对应的响应 Schema
2. **识别请求参数**：`@param UserModel` → 生成请求体 Schema
3. **应用验证规则**：模型验证规则 → OpenAPI 参数约束
4. **处理关联数据**：关联关系 → 嵌套 Schema 引用

这样就能自动生成完整、准确的 API 文档，大大减少手动维护的工作量。
