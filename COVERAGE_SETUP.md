# 代码覆盖率设置指南

## 📊 关于代码覆盖率

代码覆盖率是衡量测试质量的重要指标，它显示了测试执行期间实际运行的代码百分比。ThinkScramble 支持通过 Xdebug 或 PCOV 生成详细的覆盖率报告。

## 🔧 安装覆盖率驱动

### 方案 1: 安装 Xdebug（推荐）

#### Windows (XAMPP/WAMP)

1. **确定 PHP 版本和架构**
   ```bash
   php -v
   php -m | findstr -i thread
   ```

2. **下载 Xdebug**
   - 访问 [Xdebug 下载页面](https://xdebug.org/download)
   - 选择与您的 PHP 版本匹配的 DLL 文件
   - 下载对应的 `php_xdebug-x.x.x-x.x-vs16-x86_64.dll`

3. **安装 Xdebug**
   ```bash
   # 将 DLL 文件复制到 PHP 扩展目录
   copy php_xdebug-*.dll C:\xampp\php\ext\
   ```

4. **配置 php.ini**
   ```ini
   [xdebug]
   zend_extension=xdebug
   xdebug.mode=coverage,debug
   xdebug.start_with_request=yes
   ```

5. **重启 Web 服务器**
   ```bash
   # 重启 Apache 或 Nginx
   ```

#### Linux/macOS

```bash
# 使用 PECL 安装
pecl install xdebug

# 或使用包管理器
# Ubuntu/Debian
sudo apt-get install php-xdebug

# CentOS/RHEL
sudo yum install php-xdebug

# macOS (Homebrew)
brew install php@8.1-xdebug
```

**配置 php.ini:**
```ini
[xdebug]
zend_extension=xdebug
xdebug.mode=coverage
```

### 方案 2: 安装 PCOV（轻量级）

PCOV 是一个专门用于代码覆盖率的轻量级扩展，性能比 Xdebug 更好。

```bash
# 使用 PECL 安装
pecl install pcov

# 配置 php.ini
echo "extension=pcov" >> php.ini
echo "pcov.enabled=1" >> php.ini
```

## ✅ 验证安装

安装完成后，验证扩展是否正确加载：

```bash
# 检查 Xdebug
php -m | grep -i xdebug

# 检查 PCOV
php -m | grep -i pcov

# 运行我们的测试脚本
php run-tests.php
```

## 🚀 使用覆盖率功能

安装覆盖率驱动后，您可以使用以下命令：

### 基本覆盖率报告

```bash
# 生成 HTML 覆盖率报告
composer test:coverage

# 显示文本覆盖率摘要
composer test:text-coverage

# 使用 PHPUnit 生成覆盖率
composer phpunit:coverage
```

### 高级覆盖率选项

```bash
# 设置最小覆盖率阈值
pest --coverage --min=80

# 生成不同格式的报告
pest --coverage --coverage-html=coverage-html
pest --coverage --coverage-clover=coverage.xml
pest --coverage --coverage-text
```

## 📈 覆盖率报告解读

### HTML 报告

HTML 报告提供最详细的覆盖率信息：

- **绿色**: 已覆盖的代码行
- **红色**: 未覆盖的代码行
- **黄色**: 部分覆盖的代码行

### 文本报告

```
Classes:        100.00% (2/2)
Methods:        95.24% (20/21)
Lines:          89.47% (85/95)
```

### 覆盖率指标

- **行覆盖率**: 执行的代码行百分比
- **函数覆盖率**: 调用的函数百分比
- **分支覆盖率**: 执行的条件分支百分比
- **类覆盖率**: 实例化的类百分比

## 🎯 覆盖率目标

### 推荐的覆盖率目标

- **单元测试**: 90%+ 行覆盖率
- **集成测试**: 80%+ 行覆盖率
- **整体项目**: 85%+ 行覆盖率

### ThinkScramble 当前状态

基于我们的测试套件，预期覆盖率：

- **配置管理**: ~95% 覆盖率
- **缓存系统**: ~90% 覆盖率
- **核心功能**: ~85% 覆盖率

## 🔍 故障排除

### 常见问题

1. **"No code coverage driver available"**
   - 确保已安装 Xdebug 或 PCOV
   - 检查 php.ini 配置
   - 重启 Web 服务器

2. **覆盖率报告为空**
   - 检查 Xdebug 模式设置
   - 确保 `xdebug.mode=coverage`

3. **性能问题**
   - 考虑使用 PCOV 替代 Xdebug
   - 仅在需要时启用覆盖率

### 调试命令

```bash
# 检查 PHP 配置
php --ini

# 查看已加载的扩展
php -m

# 检查 Xdebug 配置
php -i | grep -i xdebug

# 测试覆盖率功能
php -r "var_dump(extension_loaded('xdebug'));"
```

## 📝 CI/CD 集成

### GitHub Actions 示例

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v2
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.1'
        extensions: xdebug
        coverage: xdebug
    
    - name: Install dependencies
      run: composer install
    
    - name: Run tests with coverage
      run: composer test:coverage
    
    - name: Upload coverage to Codecov
      uses: codecov/codecov-action@v1
```

## 🛠️ 开发工作流

### 日常开发

```bash
# 开发时运行快速测试（无覆盖率）
composer test:no-coverage

# 提交前运行完整测试
composer test:coverage

# 检查特定文件的覆盖率
pest --coverage --filter=ConfigTest
```

### 持续改进

1. **定期检查覆盖率报告**
2. **为未覆盖的代码添加测试**
3. **重构复杂的未测试代码**
4. **设置覆盖率阈值**

---

**注意**: 代码覆盖率是质量指标之一，但不是唯一指标。高覆盖率不等于高质量测试，重要的是编写有意义的测试用例。
