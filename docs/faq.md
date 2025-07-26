# ❓ 常见问题

本页面收集了 ThinkScramble 使用过程中的常见问题和解决方案。

## 📦 安装相关

### Q: 支持哪些 PHP 版本？

**A:** ThinkScramble 要求 PHP 8.0 或更高版本。推荐使用 PHP 8.1+ 以获得最佳性能。

```bash
# 检查 PHP 版本
php --version

# 如果版本过低，需要升级
# Ubuntu/Debian
sudo apt install php8.1

# CentOS/RHEL  
sudo yum install php81

# macOS
brew install php@8.1
```

### Q: 支持哪些 ThinkPHP 版本？

**A:** 支持 ThinkPHP 6.0+ 和 8.0+。推荐使用 ThinkPHP 8.0 以获得最佳兼容性。

### Q: PIE 安装失败怎么办？

**A:** 尝试以下解决方案：

```bash
# 1. 检查 PIE 是否正确安装
pie --version

# 2. 重新安装 PIE
composer global remove pie/pie
composer global require pie/pie

# 3. 使用 Composer 替代安装
composer require yangweijie/think-scramble

# 4. 使用 PHAR 文件
curl -L https://github.com/yangweijie/think-scramble/releases/latest/download/scramble.phar -o scramble.phar
chmod +x scramble.phar
```

### Q: Windows 下安装有什么注意事项？

**A:** Windows 用户需要注意：

1. 确保 PHP 在 PATH 环境变量中
2. 使用 PowerShell 或 Git Bash
3. 可能需要管理员权限

```powershell
# PowerShell 安装示例
# 以管理员身份运行
pie install yangweijie/think-scramble

# 或下载 Windows 版本
Invoke-WebRequest -Uri "https://github.com/yangweijie/think-scramble/releases/latest/download/scramble.bat" -OutFile "scramble.bat"
```

## 🔧 配置相关

### Q: 如何自定义配置？

**A:** 创建 `scramble.php` 配置文件：

```php
<?php

return [
    'info' => [
        'title' => 'My API',
        'version' => '1.0.0',
        'description' => 'API documentation',
    ],
    
    'servers' => [
        ['url' => 'http://localhost:8000', 'description' => 'Development'],
        ['url' => 'https://api.example.com', 'description' => 'Production'],
    ],
    
    'paths' => [
        'controllers' => 'app/controller',
        'models' => 'app/model',
    ],
    
    'security' => [
        'enabled_schemes' => ['BearerAuth', 'ApiKeyAuth'],
    ],
    
    'cache' => [
        'driver' => 'file',
        'ttl' => 3600,
    ],
];
```

### Q: 如何配置多个服务器环境？

**A:** 在配置文件中定义多个服务器：

```php
'servers' => [
    [
        'url' => 'http://localhost:8000',
        'description' => 'Development server',
    ],
    [
        'url' => 'https://staging-api.example.com',
        'description' => 'Staging server',
    ],
    [
        'url' => 'https://api.example.com',
        'description' => 'Production server',
    ],
],
```

### Q: 如何禁用缓存？

**A:** 在配置中设置缓存驱动为 `none`：

```php
'cache' => [
    'driver' => 'none',
],
```

或使用命令行参数：

```bash
scramble --no-cache --output=api.json
```

## 📝 使用相关

### Q: 为什么生成的文档是空的？

**A:** 可能的原因和解决方案：

1. **控制器路径不正确**
   ```bash
   # 指定正确的控制器路径
   scramble --controllers=app/controller --output=api.json
   ```

2. **没有公共方法**
   ```php
   // 确保控制器有公共方法
   class UserController
   {
       public function index() { /* ... */ }  // ✅ 会被扫描
       private function helper() { /* ... */ } // ❌ 不会被扫描
   }
   ```

3. **路由未配置**
   ```php
   // 确保路由已配置
   Route::get('users', 'User/index');
   ```

### Q: 如何添加认证信息？

**A:** 使用安全注解：

```php
/**
 * 需要认证的接口
 * @security BearerAuth
 */
public function profile(Request $request)
{
    // 实现逻辑
}
```

配置安全方案：

```php
'security' => [
    'enabled_schemes' => [
        'BearerAuth' => [
            'type' => 'http',
            'scheme' => 'bearer',
            'bearerFormat' => 'JWT',
        ],
        'ApiKeyAuth' => [
            'type' => 'apiKey',
            'in' => 'header',
            'name' => 'X-API-Key',
        ],
    ],
],
```

### Q: 如何处理文件上传？

**A:** 使用 `@requestBody` 注解：

```php
/**
 * 上传文件
 * @summary 文件上传
 * @requestBody multipart/form-data {
 *   "file": "file|required|上传的文件",
 *   "description": "string|文件描述"
 * }
 */
public function upload(Request $request)
{
    $file = $request->file('file');
    // 处理文件上传
}
```

### Q: 如何自定义响应格式？

**A:** 使用 `@response` 注解：

```php
/**
 * 获取用户信息
 * @response 200 {
 *   "code": 200,
 *   "message": "success",
 *   "data": {
 *     "id": 1,
 *     "name": "John Doe",
 *     "email": "john@example.com"
 *   }
 * }
 * @response 404 {
 *   "code": 404,
 *   "message": "User not found"
 * }
 */
public function show(int $id)
{
    // 实现逻辑
}
```

## ⚡ 性能相关

### Q: 文档生成很慢怎么办？

**A:** 尝试以下优化方案：

1. **启用缓存**
   ```bash
   # 确保缓存已启用
   scramble --stats  # 查看缓存命中率
   ```

2. **限制扫描范围**
   ```bash
   # 只扫描特定目录
   scramble --controllers=app/api --models=app/model --output=api.json
   ```

3. **使用增量模式**
   ```bash
   # 只重新分析修改过的文件
   scramble --incremental --output=api.json
   ```

4. **启用 OPcache**
   ```ini
   ; php.ini
   opcache.enable=1
   opcache.enable_cli=1
   ```

### Q: 内存使用过高怎么办？

**A:** 调整内存限制和优化配置：

```bash
# 增加内存限制
php -d memory_limit=512M scramble --output=api.json

# 或在配置中优化
```

```php
'performance' => [
    'memory_limit' => '512M',
    'max_execution_time' => 300,
    'batch_size' => 50,
],
```

### Q: 如何查看性能统计？

**A:** 使用统计命令：

```bash
# 查看详细统计
scramble --stats

# 生成性能报告
scramble --output=api.json --performance-report
```

## 🔌 插件相关

### Q: 如何开发自定义插件？

**A:** 实现插件接口：

```php
<?php

namespace MyPlugin;

use Yangweijie\ThinkScramble\Plugin\PluginInterface;
use Yangweijie\ThinkScramble\Plugin\HookManager;

class MyCustomPlugin implements PluginInterface
{
    public function getName(): string
    {
        return 'My Custom Plugin';
    }
    
    public function getVersion(): string
    {
        return '1.0.0';
    }
    
    public function registerHooks(HookManager $hookManager): void
    {
        $hookManager->register('before_document_build', [$this, 'beforeBuild']);
    }
    
    public function beforeBuild($data, $context)
    {
        // 自定义逻辑
        return $data;
    }
}
```

### Q: 如何加载插件？

**A:** 在配置中启用插件：

```php
'plugins' => [
    'enabled' => [
        'MyPlugin\\MyCustomPlugin',
    ],
    'directories' => [
        './plugins',
    ],
],
```

## 📤 导出相关

### Q: 支持哪些导出格式？

**A:** 目前支持以下格式：

- **JSON** (默认) - OpenAPI 3.0 JSON 格式
- **YAML** - OpenAPI 3.0 YAML 格式  
- **Postman** - Postman Collection v2.1
- **Insomnia** - Insomnia Workspace

```bash
# 不同格式导出
scramble --output=api.json          # JSON
scramble --output=api.yaml          # YAML
scramble --format=postman --output=api.postman.json    # Postman
scramble --format=insomnia --output=api.insomnia.json  # Insomnia
```

### Q: 如何批量导出多种格式？

**A:** 使用脚本或多次运行：

```bash
#!/bin/bash
# 批量导出脚本

scramble --output=api.json
scramble --output=api.yaml  
scramble --format=postman --output=api.postman.json
scramble --format=insomnia --output=api.insomnia.json

echo "所有格式导出完成！"
```

## 🚨 错误处理

### Q: 遇到语法错误怎么办？

**A:** 检查 PHP 语法：

```bash
# 检查文件语法
php -l app/controller/UserController.php

# 批量检查
find app/controller -name "*.php" -exec php -l {} \;
```

### Q: 注解解析失败怎么办？

**A:** 检查注解格式：

```php
// ❌ 错误格式
/**
 * @requestBody {
 *   name: "string"  // 缺少引号
 * }
 */

// ✅ 正确格式  
/**
 * @requestBody {
 *   "name": "string"
 * }
 */
```

### Q: 如何启用调试模式？

**A:** 使用调试参数：

```bash
# 启用详细输出
scramble --verbose --output=api.json

# 启用调试模式
scramble --debug --output=api.json

# 保存调试日志
scramble --debug --output=api.json 2> debug.log
```

## 🔄 更新相关

### Q: 如何检查是否有新版本？

**A:** 使用不同的方法检查：

```bash
# PIE 方式
pie outdated yangweijie/think-scramble

# Composer 方式
composer outdated yangweijie/think-scramble

# GitHub 方式
curl -s https://api.github.com/repos/yangweijie/think-scramble/releases/latest | grep tag_name
```

### Q: 更新后配置不兼容怎么办？

**A:** 检查配置变更：

1. 查看 [更新日志](changelog.md)
2. 备份现有配置
3. 使用新的配置格式

```bash
# 备份配置
cp scramble.php scramble.php.backup

# 验证新配置
scramble --validate --config=scramble.php
```

## 📞 获取更多帮助

如果以上答案没有解决你的问题：

1. 📚 查看 [完整文档](/)
2. 🔍 搜索 [GitHub Issues](https://github.com/yangweijie/think-scramble/issues)
3. 💬 参与 [GitHub Discussions](https://github.com/yangweijie/think-scramble/discussions)
4. 🐛 [提交新问题](https://github.com/yangweijie/think-scramble/issues/new)

---

💡 **提示**: 如果你发现了新的常见问题，欢迎通过 [GitHub Issues](https://github.com/yangweijie/think-scramble/issues) 反馈！
