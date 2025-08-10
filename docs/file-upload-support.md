# 文件上传支持

ThinkScramble 现在支持自动识别和文档化文件上传参数，包括注释支持和代码自动分析。

## 功能特性

- ✅ **注释支持** - 支持多种文件上传注释格式
- ✅ **自动识别** - 自动分析 `$request->file()` 调用
- ✅ **类型验证** - 支持文件类型和大小限制
- ✅ **OpenAPI 集成** - 自动生成 `multipart/form-data` 请求体
- ✅ **智能合并** - 注释和代码分析结果智能合并
- ✅ **OpenAPI 3.0 兼容** - 符合 OpenAPI 3.0 规范要求

## 支持的注释格式

### 1. @upload 标签

```php
/**
 * 上传用户头像
 * 
 * @upload avatar required jpg,png,gif max:2MB 用户头像文件
 */
public function uploadAvatar(Request $request)
{
    $avatar = $request->file('avatar');
    // 处理上传逻辑
}
```

### 2. @file 标签

```php
/**
 * 上传文档
 * 
 * @file document pdf,doc,docx max:50MB 文档文件
 * @param string title 文档标题
 */
public function uploadDocument(Request $request)
{
    $document = $request->file('document');
    $title = $request->param('title');
    // 处理上传逻辑
}
```

### 3. @param {file} 格式

```php
/**
 * 批量上传
 * 
 * @param {file} files 文件数组
 * @param string category 文件分类
 */
public function batchUpload(Request $request)
{
    $files = $request->file('files');
    $category = $request->param('category');
    // 处理批量上传
}
```

## 注释语法说明

### 基本格式

```
@upload 参数名 [required] [文件类型] [大小限制] 描述文本
@file 参数名 [文件类型] [大小限制] 描述文本
@param {file} 参数名 描述文本
```

### 参数说明

- **参数名**: 文件上传的参数名称
- **required**: 可选，标记为必填参数
- **文件类型**: 可选，支持的文件扩展名，用逗号分隔（如：`jpg,png,gif`）
- **大小限制**: 可选，最大文件大小（如：`max:2MB`、`max:50MB`、`max:1GB`）
- **描述文本**: 参数的描述信息

### 示例

```php
// 基本文件上传
@upload avatar 头像文件

// 必填文件上传
@upload avatar required 头像文件

// 带类型限制
@upload avatar jpg,png,gif 头像文件

// 带大小限制
@upload avatar max:2MB 头像文件

// 完整格式
@upload avatar required jpg,png,gif max:2MB 用户头像文件
```

## 自动代码分析

即使没有注释，ThinkScramble 也能自动识别文件上传参数：

```php
public function autoDetectUpload(Request $request)
{
    // 这些调用会被自动识别为文件上传参数
    $image = $request->file('image');
    $thumbnail = $request->file('thumbnail');
    $document = $request->file('document');
    
    // 普通参数不会被识别为文件上传
    $name = $request->param('name');
    $category = $request->param('category');
}
```

## 生成的 OpenAPI 文档

### 请求体格式

当检测到文件上传参数时，会自动添加 `multipart/form-data` 内容类型：

```yaml
requestBody:
  required: true
  content:
    application/json:
      schema:
        type: object
        properties: {}
    application/x-www-form-urlencoded:
      schema:
        type: object
        properties: {}
    multipart/form-data:
      schema:
        type: object
        properties:
          avatar:
            type: string
            format: binary
            description: 用户头像文件 (支持格式: jpg, png, gif) (最大大小: 2MB)
          document:
            type: string
            format: binary
            description: 文档文件
```

### 参数定义

文件上传参数会生成正确的 OpenAPI 参数定义（兼容旧版）：

```yaml
parameters:
  - name: avatar
    in: formData
    required: true
    description: 用户头像文件 (支持格式: jpg, png, gif) (最大大小: 2MB)
    schema:
      type: string
      format: binary
```

### OpenAPI 3.0 规范

在 OpenAPI 3.0 中，文件上传参数推荐使用请求体方式：

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

## 完整示例

```php
<?php

namespace app\controller;

use think\Request;
use think\Response;

class UploadController
{
    /**
     * 上传用户头像
     * 
     * @param Request $request
     * @return Response
     * 
     * @upload avatar required jpg,png,gif max:2MB 用户头像文件
     * @param string user_id 用户ID
     */
    public function uploadAvatar(Request $request): Response
    {
        $avatar = $request->file('avatar');
        $userId = $request->param('user_id');
        
        if (!$avatar || !$avatar->isValid()) {
            return json(['error' => '文件上传失败'], 400);
        }
        
        return json([
            'avatar_url' => '/uploads/avatar.jpg',
            'user_id' => $userId,
            'filename' => $avatar->getOriginalName(),
            'size' => $avatar->getSize()
        ]);
    }

    /**
     * 批量文件上传
     * 
     * @param Request $request
     * @return Response
     * 
     * @file files 支持多文件上传
     * @param string category 文件分类
     */
    public function batchUpload(Request $request): Response
    {
        $files = $request->file('files');
        $category = $request->param('category', 'general');
        
        // 处理批量上传逻辑
        
        return json([
            'category' => $category,
            'files' => $results,
            'total' => count($results)
        ]);
    }

    /**
     * 自动识别文件上传
     * 
     * @param Request $request
     * @return Response
     * 
     * 这个方法没有文件上传注释，但会自动识别 $request->file() 调用
     */
    public function autoUpload(Request $request): Response
    {
        // 这些会被自动识别为文件上传参数
        $image = $request->file('image');
        $thumbnail = $request->file('thumbnail');
        
        // 普通参数
        $name = $request->param('name');
        
        return json(['success' => true]);
    }
}
```

## 配置选项

可以在 `config/scramble.php` 中配置文件上传相关选项：

```php
return [
    'analysis' => [
        'parse_docblocks' => true,  // 启用注释解析
        'type_inference' => true,   // 启用类型推断
        // 其他配置...
    ],
    
    // 可以通过扩展来自定义文件上传处理
    'extensions' => [
        'operation_transformers' => [
            // 自定义操作转换器
        ],
    ],
];
```

## 注意事项

1. **文件类型验证**: 注释中的文件类型限制仅用于文档生成，实际验证需要在控制器中实现
2. **大小限制**: 注释中的大小限制仅用于文档说明，服务器配置和应用逻辑需要单独处理
3. **多文件上传**: 支持数组形式的多文件上传参数
4. **兼容性**: 与现有的参数提取逻辑完全兼容，不会影响其他参数的处理

## 故障排除

### 文件上传参数未被识别

1. 确保使用了 `$request->file()` 方法
2. 检查注释格式是否正确
3. 确保启用了 `parse_docblocks` 配置

### 生成的文档不正确

1. 检查控制器方法的可见性（必须是 public）
2. 确保类和方法可以被反射访问
3. 查看日志中的错误信息

### 性能问题

文件上传分析会增加一些处理时间，如果遇到性能问题：

1. 启用缓存：`'cache' => ['enabled' => true]`
2. 限制分析范围：配置 `exclude_paths`
3. 使用增量分析功能
