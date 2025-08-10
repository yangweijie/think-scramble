# 文件上传支持功能更新日志

## 🎉 新增功能

### 📁 文件上传参数自动识别和文档化

为 ThinkScramble 添加了完整的文件上传支持，包括注释解析和代码自动分析功能。

## ✨ 主要特性

### 1. 多种注释格式支持

- **@upload 标签**: `@upload avatar required jpg,png,gif max:2MB 用户头像文件`
- **@file 标签**: `@file document pdf,doc,docx max:50MB 文档文件`
- **@param {file} 格式**: `@param {file} images 图片文件`

### 2. 自动代码分析

- 自动识别 `$request->file()` 调用
- 提取文件参数名称
- 智能合并注释和代码分析结果

### 3. OpenAPI 集成

- 自动生成 `multipart/form-data` 请求体
- 正确的 `binary` 格式参数定义
- 文件类型和大小限制说明
- 支持 OpenAPI 3.0 规范

### 4. 智能参数处理

- 文件类型验证（如：jpg,png,gif）
- 文件大小限制（如：max:2MB, max:50MB）
- 必填/可选参数标记
- 参数描述自动生成

## 🔧 技术实现

### 新增文件

1. **`src/Analyzer/FileUploadAnalyzer.php`**
   - 文件上传分析器核心类
   - 负责注释解析和代码分析
   - 生成 OpenAPI 参数定义

2. **`docs/file-upload-support.md`**
   - 完整的使用文档
   - 注释格式说明
   - 示例代码和配置

3. **`example/UploadController.php`**
   - 文件上传控制器示例
   - 展示各种注释格式
   - 演示自动识别功能

### 修改文件

1. **`src/Analyzer/DocBlockParser.php`**
   - 添加 `@upload` 和 `@file` 标签支持
   - 扩展 `@param {file}` 格式解析
   - 新增文件大小和类型解析

2. **`src/Generator/ParameterExtractor.php`**
   - 集成文件上传分析器
   - 添加文件上传参数提取方法
   - 支持文件参数的 OpenAPI 生成
   - 改进与请求体的兼容性

3. **`src/Generator/DocumentBuilder.php`**
   - 添加 `multipart/form-data` 请求体支持
   - 自动检测文件上传参数
   - 生成文件上传属性定义
   - 改进 OpenAPI 3.0 兼容性

4. **`src/Generator/SchemaGenerator.php`**
   - 添加文件类型映射
   - 支持 `binary` 格式生成
   - 文件类型的特殊处理

## 📝 使用示例

### 基本文件上传

```php
/**
 * 上传头像
 * 
 * @upload avatar required jpg,png,gif max:2MB 用户头像文件
 */
public function uploadAvatar(Request $request)
{
    $avatar = $request->file('avatar');
    // 处理上传逻辑
}
```

### 自动识别

```php
public function autoUpload(Request $request)
{
    // 这些调用会被自动识别为文件上传参数
    $image = $request->file('image');
    $document = $request->file('document');
}
```

### 生成的 OpenAPI 文档

```yaml
requestBody:
  required: true
  content:
    multipart/form-data:
      schema:
        type: object
        properties:
          avatar:
            type: string
            format: binary
            description: 用户头像文件 (支持格式: jpg, png, gif) (最大大小: 2MB)
```

## 🧪 测试验证

- ✅ DocBlock 注释解析测试
- ✅ 文件上传参数识别测试
- ✅ OpenAPI 参数生成测试
- ✅ 代码自动分析测试
- ✅ 参数合并逻辑测试

## 🛠 改进功能

### 1. OpenAPI 3.0 兼容性

- 改进了文件上传参数在 OpenAPI 3.0 规范中的表示方式
- 优化了 `multipart/form-data` 请求体的生成逻辑
- 增强了与请求体参数的兼容性

### 2. 参数处理优化

- 添加了更灵活的文件上传参数处理方式
- 改进了参数提取器与文档构建器的协作
- 增强了向后兼容性

## 🔄 兼容性

- ✅ 与现有参数提取逻辑完全兼容
- ✅ 不影响其他类型参数的处理
- ✅ 向后兼容，不破坏现有功能
- ✅ 可选功能，可通过配置控制

## 📋 配置选项

```php
// config/scramble.php
return [
    'analysis' => [
        'parse_docblocks' => true,  // 启用注释解析
        'type_inference' => true,   // 启用类型推断
    ],
];
```

## 🚀 性能优化

- 智能缓存机制
- 增量分析支持
- 错误处理和容错
- 最小化性能影响

## 📚 文档更新

- 更新 README.md 添加文件上传示例
- 新增完整的使用文档
- 提供示例控制器代码
- 添加故障排除指南

## 🎯 下一步计划

- [ ] 支持更多文件类型验证
- [ ] 添加文件上传中间件分析
- [ ] 集成验证器规则提取
- [ ] 支持自定义文件处理器

## 🐛 已知限制

1. 文件类型验证仅用于文档生成，实际验证需要在控制器中实现
2. 文件大小限制仅用于文档说明，服务器配置需要单独处理
3. 复杂的文件处理逻辑可能需要手动注释

## 💡 使用建议

1. 优先使用 `@upload` 标签，语法最简洁
2. 对于复杂场景，结合注释和代码分析
3. 启用缓存以提高性能
4. 定期检查生成的文档是否符合预期

---

**版本**: v1.1.0  
**发布日期**: 2024-01-26  
**兼容性**: ThinkPHP 6.0+ / 8.0+
