# 故障排除

本文档包含 ThinkScramble 常见问题的解决方案。

## 安装问题

### 问题 1: Composer 安装失败

**错误信息**:
```
Package yangweijie/think-scramble not found
```

**解决方案**:
1. 更新 Composer：
   ```bash
   composer self-update
   ```

2. 清除 Composer 缓存：
   ```bash
   composer clear-cache
   ```

3. 检查 Packagist 连接：
   ```bash
   composer diagnose
   ```

4. 使用国内镜像（如果在中国）：
   ```bash
   composer config repo.packagist composer https://mirrors.aliyun.com/composer/
   ```

### 问题 2: 自动加载失败

**错误信息**:
```
Class 'Yangweijie\ThinkScramble\Service\ScrambleServiceProvider' not found
```

**解决方案**:
1. 重新生成自动加载文件：
   ```bash
   composer dump-autoload
   ```

2. 检查 vendor 目录是否存在：
   ```bash
   ls -la vendor/yangweijie/think-scramble/
   ```

3. 手动注册服务提供者：
   ```php
   // config/service.php
   return [
       \Yangweijie\ThinkScramble\Service\ScrambleServiceProvider::class,
   ];
   ```

### 问题 3: PHP 版本不兼容

**错误信息**:
```
Your requirements could not be resolved to an installable set of packages.
```

**解决方案**:
1. 检查 PHP 版本：
   ```bash
   php -v
   ```

2. 确保 PHP >= 8.1：
   ```bash
   # Ubuntu/Debian
   sudo apt update && sudo apt install php8.1

   # CentOS/RHEL
   sudo yum install php81
   ```

3. 检查必需的 PHP 扩展：
   ```bash
   php -m | grep -E "(json|mbstring|openssl)"
   ```

## 配置问题

### 问题 4: 配置文件不生效

**症状**: 修改配置后没有变化

**解决方案**:
1. 清除应用缓存：
   ```bash
   php think clear
   ```

2. 检查配置文件路径：
   ```bash
   ls -la config/scramble.php
   ```

3. 验证配置语法：
   ```bash
   php -l config/scramble.php
   ```

4. 检查环境变量：
   ```bash
   # 在 .env 文件中
   SCRAMBLE_DEBUG=true
   ```

### 问题 5: 路由中间件冲突

**错误信息**:
```
Middleware 'auth' not found
```

**解决方案**:
1. 检查中间件是否存在：
   ```php
   // config/scramble.php
   'routes' => [
       'middleware' => ['web'], // 移除不存在的中间件
   ],
   ```

2. 创建缺失的中间件：
   ```bash
   php think make:middleware Auth
   ```

3. 注册中间件：
   ```php
   // config/middleware.php
   return [
       'auth' => \app\middleware\Auth::class,
   ];
   ```

## 文档生成问题

### 问题 6: 生成的文档为空

**症状**: `Found 0 API endpoints and 0 schemas`

**解决方案**:
1. 检查 API 路径配置：
   ```php
   // config/scramble.php
   'api_path' => 'api', // 确保与实际路由匹配
   ```

2. 验证路由是否存在：
   ```bash
   php think route:list | grep api
   ```

3. 检查控制器路径：
   ```bash
   ls -la app/controller/
   ```

4. 启用调试模式：
   ```php
   // config/scramble.php
   'debug' => [
       'enabled' => true,
       'log_analysis' => true,
   ],
   ```

### 问题 7: 路由检测失败

**症状**: 路由存在但未被检测到

**解决方案**:
1. 检查路由定义格式：
   ```php
   // route/app.php
   Route::group('api', function () {
       Route::get('users', 'Api/users');
   });
   ```

2. 确保控制器方法为 public：
   ```php
   public function users(): Response
   {
       // 方法实现
   }
   ```

3. 检查命名空间：
   ```php
   <?php
   namespace app\controller;
   
   class Api
   {
       // 类实现
   }
   ```

### 问题 8: 文档生成超时

**错误信息**:
```
Maximum execution time exceeded
```

**解决方案**:
1. 增加 PHP 执行时间：
   ```bash
   php -d max_execution_time=300 think scramble:generate
   ```

2. 禁用缓存（临时）：
   ```php
   // config/scramble.php
   'cache' => [
       'enabled' => false,
   ],
   ```

3. 分批处理大型项目：
   ```bash
   php think scramble:generate --controller=UserController
   ```

## 访问问题

### 问题 9: 文档页面 404

**症状**: 访问 `/docs/api` 返回 404

**解决方案**:
1. 检查路由是否注册：
   ```bash
   php think route:list | grep docs
   ```

2. 清除路由缓存：
   ```bash
   php think clear
   ```

3. 检查 Web 服务器配置：
   ```nginx
   # Nginx
   location / {
       try_files $uri $uri/ /index.php?$query_string;
   }
   ```

4. 验证服务提供者注册：
   ```bash
   php think service:discover
   ```

### 问题 10: 权限被拒绝

**错误信息**:
```
Access denied
```

**解决方案**:
1. 检查中间件配置：
   ```php
   // config/scramble.php
   'routes' => [
       'middleware' => ['web'], // 移除认证中间件
   ],
   ```

2. 临时禁用中间件：
   ```php
   'routes' => [
       'middleware' => [],
   ],
   ```

3. 检查用户权限（如果使用认证）：
   ```php
   // 在控制器中
   if (!auth()->check()) {
       return redirect('/login');
   }
   ```

## 性能问题

### 问题 11: 文档加载缓慢

**解决方案**:
1. 启用缓存：
   ```php
   // config/scramble.php
   'cache' => [
       'enabled' => true,
       'ttl' => 7200, // 2小时
   ],
   ```

2. 使用 Redis 缓存：
   ```php
   // config/cache.php
   'default' => 'redis',
   ```

3. 优化输出：
   ```bash
   php think scramble:export --format=html --compress
   ```

### 问题 12: 内存不足

**错误信息**:
```
Fatal error: Allowed memory size exhausted
```

**解决方案**:
1. 增加内存限制：
   ```bash
   php -d memory_limit=512M think scramble:generate
   ```

2. 优化代码分析：
   ```php
   // config/scramble.php
   'analysis' => [
       'max_depth' => 3, // 限制分析深度
       'exclude_paths' => ['vendor/', 'storage/'],
   ],
   ```

## 输出问题

### 问题 13: 文件写入失败

**错误信息**:
```
Failed to write output file
```

**解决方案**:
1. 检查目录权限：
   ```bash
   chmod 755 public/docs/
   chown www-data:www-data public/docs/
   ```

2. 创建输出目录：
   ```bash
   mkdir -p public/docs/
   ```

3. 检查磁盘空间：
   ```bash
   df -h
   ```

### 问题 14: 输出格式错误

**症状**: JSON 格式不正确或 YAML 解析失败

**解决方案**:
1. 验证 JSON：
   ```bash
   php think scramble:generate --validate
   ```

2. 检查 YAML 扩展：
   ```bash
   php -m | grep yaml
   ```

3. 安装 YAML 扩展：
   ```bash
   # Ubuntu/Debian
   sudo apt install php-yaml
   
   # CentOS/RHEL
   sudo yum install php-yaml
   ```

## 开发问题

### 问题 15: 热重载不工作

**症状**: 修改代码后文档不更新

**解决方案**:
1. 禁用缓存：
   ```php
   // config/scramble.php
   'cache' => [
       'enabled' => false,
   ],
   ```

2. 强制重新生成：
   ```bash
   php think scramble:generate --force
   ```

3. 清除所有缓存：
   ```bash
   php think clear
   ```

### 问题 16: 调试信息不显示

**解决方案**:
1. 启用调试模式：
   ```env
   SCRAMBLE_DEBUG=true
   SCRAMBLE_LOG_ANALYSIS=true
   SCRAMBLE_VERBOSE_ERRORS=true
   ```

2. 检查日志文件：
   ```bash
   tail -f runtime/log/$(date +%Y%m%d).log
   ```

3. 使用详细输出：
   ```bash
   php think scramble:generate -vvv
   ```

## 集成问题

### 问题 17: 与其他扩展冲突

**解决方案**:
1. 检查服务提供者顺序：
   ```php
   // config/service.php
   return [
       // 其他服务提供者
       \Yangweijie\ThinkScramble\Service\ScrambleServiceProvider::class,
   ];
   ```

2. 检查路由冲突：
   ```bash
   php think route:list | grep -E "(docs|api)"
   ```

3. 使用不同的路由前缀：
   ```php
   // config/scramble.php
   'routes' => [
       'prefix' => 'api-docs', // 避免冲突
   ],
   ```

## 获取帮助

### 调试信息收集

在报告问题时，请提供以下信息：

1. **系统信息**:
   ```bash
   php -v
   composer --version
   php think version
   ```

2. **扩展信息**:
   ```bash
   composer show yangweijie/think-scramble
   ```

3. **错误日志**:
   ```bash
   tail -n 50 runtime/log/$(date +%Y%m%d).log
   ```

4. **配置信息**:
   ```bash
   php think config:show scramble
   ```

### 联系支持

- **GitHub Issues**: [提交问题](https://github.com/yangweijie/think-scramble/issues)
- **文档**: [在线文档](https://github.com/yangweijie/think-scramble/docs)
- **示例项目**: [参考示例](https://github.com/yangweijie/think-scramble-example)

### 常用调试命令

```bash
# 检查服务注册
php think service:discover

# 清除所有缓存
php think clear

# 生成详细日志
php think scramble:generate --debug

# 验证配置
php think config:show scramble

# 检查路由
php think route:list

# 测试文档生成
php think scramble:generate --validate --force
```

---

**提示**: 大多数问题都可以通过清除缓存和重新生成文档来解决。如果问题持续存在，请启用调试模式并查看详细的错误信息。
