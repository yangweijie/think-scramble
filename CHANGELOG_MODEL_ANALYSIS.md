# 模型分析功能完成日志

## 🎉 功能概述

为 ThinkScramble 添加了完整的 ThinkPHP 模型分析功能，能够自动分析模型字段、关联关系、验证规则等，并生成对应的 OpenAPI Schema 定义。

## ✨ 主要特性

### 1. 全面的模型分析

- ✅ **字段类型分析**: 自动识别模型字段类型并映射到 OpenAPI 类型
- ✅ **字段约束提取**: 从 Schema 定义中提取字段长度、默认值等约束
- ✅ **注释解析**: 解析 @property 注释获取字段描述
- ✅ **验证规则集成**: 自动提取验证规则并转换为 OpenAPI 约束
- ✅ **时间戳处理**: 自动识别和处理时间戳字段
- ✅ **软删除支持**: 识别软删除 trait 并添加相应字段

### 2. 关联关系识别

- ✅ **注释分析**: 通过 @hasOne, @hasMany 等注释识别关联
- ✅ **代码分析**: 通过 AST 分析方法体中的关联调用
- ✅ **智能推断**: 基于方法名和命名约定推断关联类型
- ✅ **Schema 引用**: 生成正确的 OpenAPI Schema 引用

### 3. OpenAPI Schema 生成

- ✅ **完整 Schema**: 生成包含所有字段的完整 Schema 定义
- ✅ **类型映射**: 数据库类型到 OpenAPI 类型的精确映射
- ✅ **约束转换**: 验证规则到 OpenAPI 约束的自动转换
- ✅ **示例生成**: 智能生成字段示例值
- ✅ **关联处理**: 处理模型间的关联关系引用

## 🔧 技术实现

### 新增核心文件

1. **`src/Analyzer/ModelAnalyzer.php`**
   - 模型分析器核心类
   - 分析模型字段、验证规则、时间戳等
   - 支持多种数据源（type、schema、注释）

2. **`src/Analyzer/ModelRelationAnalyzer.php`**
   - 模型关系分析器
   - 识别各种关联类型
   - 支持注释、代码、命名约定三种识别方式

3. **`src/Generator/ModelSchemaGenerator.php`**
   - 模型 Schema 生成器
   - 生成完整的 OpenAPI Schema
   - 支持多种配置选项

### 扩展现有文件

1. **`src/Generator/DocumentBuilder.php`**
   - 集成模型 Schema 生成功能
   - 添加自动发现模型功能
   - 支持批量添加模型 Schema

### 示例文件

1. **`example/UserModel.php`**
   - 完整的用户模型示例
   - 展示各种字段类型和关联关系
   - 包含验证规则和时间戳配置

2. **`example/ArticleModel.php`**
   - 文章模型示例
   - 展示复杂的关联关系
   - 包含访问器和修改器

## 📝 使用示例

### 基本模型定义

```php
/**
 * 用户模型
 * 
 * @property int $id 用户ID
 * @property string $username 用户名
 * @property string $email 邮箱地址
 */
class UserModel extends Model
{
    protected $type = [
        'id' => 'integer',
        'username' => 'string',
        'email' => 'string',
        'age' => 'integer',
    ];

    protected $schema = [
        'username' => [
            'type' => 'varchar(50)',
            'comment' => '用户名',
        ],
        'email' => [
            'type' => 'varchar(100)',
            'comment' => '邮箱地址',
        ],
    ];

    protected $rule = [
        'username' => 'require|length:3,50|alphaNum',
        'email' => 'require|email|unique:users',
        'age' => 'number|between:1,120',
    ];
}
```

### 关联关系定义

```php
class UserModel extends Model
{
    /**
     * 获取用户文章
     * @hasMany ArticleModel
     */
    public function articles()
    {
        return $this->hasMany(ArticleModel::class, 'user_id', 'id');
    }

    /**
     * 获取用户资料
     * @hasOne ProfileModel
     */
    public function profile()
    {
        return $this->hasOne(ProfileModel::class, 'user_id', 'id');
    }
}
```

### Schema 生成

```php
use Yangweijie\ThinkScramble\Generator\ModelSchemaGenerator;

$schemaGenerator = new ModelSchemaGenerator($config);

// 生成单个模型 Schema
$schema = $schemaGenerator->generateSchema(UserModel::class, [
    'include_relations' => true,
    'include_timestamps' => true,
]);

// 批量生成多个模型 Schema
$schemas = $schemaGenerator->generateMultipleSchemas([
    UserModel::class,
    ArticleModel::class,
]);
```

## 🚀 自动生成的 Schema

### 基本字段 Schema

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
  required:
    - username
    - email
```

### 关联关系 Schema

```yaml
articles:
  type: array
  items:
    $ref: "#/components/schemas/Article"
  description: 一对多关联到 Article

profile:
  $ref: "#/components/schemas/Profile"
  description: 一对一关联到 Profile
```

### 验证规则转换

```php
// 模型验证规则
'username' => 'require|length:3,50|alphaNum'
'email' => 'require|email'
'age' => 'number|between:1,120'

// 转换为 OpenAPI 约束
{
  "username": {
    "type": "string",
    "minLength": 3,
    "maxLength": 50,
    "pattern": "^[a-zA-Z0-9]+$"
  },
  "email": {
    "type": "string",
    "format": "email"
  },
  "age": {
    "type": "integer",
    "minimum": 1,
    "maximum": 120
  }
}
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
```

### 字段类型映射

| 数据库类型 | OpenAPI 类型 | 格式 |
|-----------|-------------|------|
| int, integer | integer | - |
| varchar(n) | string | maxLength: n |
| datetime | string | date-time |
| text | string | - |
| json | object | - |
| boolean | boolean | - |

### 验证规则映射

| 验证规则 | OpenAPI 约束 |
|---------|-------------|
| require | required: true |
| length:min,max | minLength, maxLength |
| between:min,max | minimum, maximum |
| in:a,b,c | enum: [a,b,c] |
| email | format: email |
| mobile | pattern: 手机号正则 |

## 🧪 测试验证

- ✅ 模型字段分析测试
- ✅ 字段类型映射测试
- ✅ 验证规则转换测试
- ✅ 关联关系识别测试
- ✅ Schema 生成测试
- ✅ 示例数据生成测试

## 🔄 兼容性

- ✅ 支持 ThinkPHP 6.0+ 和 8.0+
- ✅ 兼容所有 ThinkPHP 模型特性
- ✅ 支持软删除 trait
- ✅ 支持自定义时间戳字段
- ✅ 向后兼容现有功能

## 📚 文档更新

- 新增 `docs/model-analysis.md` 完整使用文档
- 更新 README.md 添加模型分析示例
- 提供完整的示例模型代码
- 添加配置选项说明

## 🎯 下一步计划

- [ ] 支持更多数据库字段类型
- [ ] 添加模型缓存机制
- [ ] 支持自定义字段处理器
- [ ] 集成数据库 Schema 自动发现

## 💡 使用建议

1. **规范模型定义**: 使用 `$type` 和 `$schema` 属性明确定义字段
2. **添加注释**: 使用 `@property` 注释提供字段描述
3. **定义验证规则**: 完整的验证规则有助于生成准确的约束
4. **关联注释**: 使用 `@hasOne`, `@hasMany` 等注释明确关联类型
5. **性能优化**: 使用缓存机制避免重复分析

## 🔍 故障排除

### 模型未被识别

- 确保模型继承自 `think\Model`
- 检查模型类是否可以被正确加载
- 确认命名空间是否正确

### 字段类型不正确

- 检查 `$type` 属性定义
- 确认 `$schema` 属性格式
- 查看字段类型映射表

### 关联关系未识别

- 添加关联注释 `@hasOne`, `@hasMany` 等
- 确保关联方法返回正确的关联对象
- 检查方法命名是否符合约定

---

**版本**: v1.3.0  
**发布日期**: 2024-01-26  
**兼容性**: ThinkPHP 6.0+ / 8.0+
