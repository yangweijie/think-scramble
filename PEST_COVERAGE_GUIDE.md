# 🧪 Pest 覆盖率完整指南

Pest **完全支持**代码覆盖率！本指南详细介绍如何在 ThinkScramble 中使用 Pest 的覆盖率功能。

## 📋 Pest 覆盖率支持

Pest 基于 PHPUnit，继承了所有 PHPUnit 的覆盖率功能：

- ✅ **HTML 报告** - 可视化覆盖率报告
- ✅ **Clover XML** - CI/CD 集成格式
- ✅ **文本报告** - 命令行摘要
- ✅ **XML 报告** - 详细的 XML 格式
- ✅ **覆盖率阈值** - 设置最小覆盖率要求
- ✅ **并行执行** - 支持并行测试覆盖率

## 🚀 快速开始

### 1. 安装覆盖率驱动

Pest 需要覆盖率驱动才能生成覆盖率报告。推荐使用 **Xdebug** 或 **PCOV**：

#### 安装 Xdebug (推荐)

```bash
# macOS (Homebrew)
brew install php-xdebug

# Ubuntu/Debian
sudo apt-get install php-xdebug

# CentOS/RHEL
sudo yum install php-xdebug

# Windows (通过 XAMPP 或手动安装)
# 下载对应版本的 Xdebug 扩展
```

#### 安装 PCOV (更快的覆盖率)

```bash
# 通过 PECL 安装
pecl install pcov

# 或通过包管理器
# Ubuntu/Debian
sudo apt-get install php-pcov
```

#### 验证安装

```bash
# 检查 Xdebug
php -m | grep xdebug

# 检查 PCOV
php -m | grep pcov

# 查看详细信息
php --ri xdebug
php --ri pcov
```

### 2. 配置覆盖率环境

#### Xdebug 配置

在 `php.ini` 中添加：

```ini
[xdebug]
zend_extension=xdebug.so
xdebug.mode=coverage
xdebug.start_with_request=yes
```

#### PCOV 配置

在 `php.ini` 中添加：

```ini
[pcov]
extension=pcov.so
pcov.enabled=1
pcov.directory=/path/to/your/project/src
```

### 3. 基本覆盖率命令

```bash
# 生成 HTML 覆盖率报告
pest --coverage-html=coverage/html

# 生成 Clover XML 报告
pest --coverage-clover=coverage/clover.xml

# 生成文本摘要
pest --coverage-text

# 组合多种格式
pest --coverage-html=coverage/html --coverage-clover=coverage/clover.xml

# 设置最小覆盖率阈值
pest --coverage --min=80

# 只显示覆盖率摘要
pest --coverage-text --coverage-filter=src/
```

## 🔧 ThinkScramble 中的使用

### 使用测试脚本

```bash
# 生成完整覆盖率报告
./scripts/test.sh --coverage

# 生成覆盖率并创建详细报告
./scripts/test.sh --coverage --report

# 特定测试套件的覆盖率
./scripts/test.sh --coverage --testsuite Unit

# 并行执行覆盖率测试
./scripts/test.sh --coverage --parallel
```

### 使用 Composer 脚本

```bash
# 基本覆盖率测试
composer test:coverage

# 生成 HTML 报告
composer test:coverage-html

# 完整覆盖率分析
composer coverage:report

# CI 流程（测试 + 覆盖率分析）
composer ci
```

### 直接使用 Pest

```bash
# 基本覆盖率
vendor/bin/pest --coverage-html=coverage/html

# 带阈值的覆盖率
vendor/bin/pest --coverage --min=80

# 特定测试套件
vendor/bin/pest --testsuite=Unit --coverage-html=coverage/html

# 详细覆盖率信息
vendor/bin/pest --coverage-text --coverage-html=coverage/html --coverage-clover=coverage/clover.xml
```

## 📊 覆盖率报告格式

### 1. HTML 报告

```bash
pest --coverage-html=coverage/html
```

生成的文件：
```
coverage/html/
├── index.html              # 主页面
├── dashboard.html          # 仪表板
├── src_Parser_ControllerParser_php.html  # 文件详情
└── ...                     # 其他文件
```

特点：
- 🎨 **可视化界面** - 直观的图表和颜色编码
- 📁 **文件浏览** - 按目录结构浏览
- 🔍 **行级详情** - 显示每行的覆盖状态
- 📈 **统计图表** - 覆盖率趋势和分布

### 2. Clover XML 报告

```bash
pest --coverage-clover=coverage/clover.xml
```

用途：
- 🔄 **CI/CD 集成** - Jenkins, GitHub Actions, GitLab CI
- 📊 **第三方服务** - Codecov, Coveralls, Code Climate
- 🤖 **自动化分析** - 脚本解析和处理

### 3. 文本报告

```bash
pest --coverage-text
```

输出示例：
```
Code Coverage Report:
  2023-12-07 10:30:45

 Summary:
  Classes: 85.71% (6/7)
  Methods: 88.89% (16/18)
  Lines:   91.30% (42/46)
```

### 4. XML 报告

```bash
pest --coverage-xml=coverage/xml
```

生成详细的 XML 格式报告，包含：
- 📄 **文件级别** - 每个文件的覆盖率
- 🔧 **方法级别** - 每个方法的覆盖率
- 📝 **行级别** - 每行代码的覆盖状态

## ⚙️ 高级配置

### 1. 配置文件设置

在 `phpunit.xml` 或 `pest.xml` 中：

```xml
<coverage includeUncoveredFiles="true"
          pathCoverage="false"
          ignoreDeprecatedCodeUnits="true">
    <include>
        <directory suffix=".php">src</directory>
    </include>
    <exclude>
        <directory>src/Exception</directory>
        <file>src/Service/ScrambleServiceProvider.php</file>
    </exclude>
    <report>
        <html outputDirectory="coverage/html" lowUpperBound="50" highLowerBound="80"/>
        <clover outputFile="coverage/clover.xml"/>
        <xml outputDirectory="coverage/xml"/>
        <text outputFile="coverage/coverage.txt"/>
    </report>
</coverage>
```

### 2. 环境变量配置

```bash
# 设置 Xdebug 模式
export XDEBUG_MODE=coverage

# 设置 PCOV 目录
export PCOV_DIRECTORY=/path/to/src

# 禁用覆盖率（提高测试速度）
export XDEBUG_MODE=off
```

### 3. 覆盖率过滤

```bash
# 只测试特定目录
pest --coverage-html=coverage/html --coverage-filter=src/Parser

# 排除特定文件
pest --coverage-html=coverage/html --coverage-exclude=src/Exception
```

## 🎯 覆盖率优化

### 1. 提高覆盖率的策略

#### 识别未覆盖代码

```bash
# 生成详细的文本报告
pest --coverage-text --coverage-html=coverage/html

# 查看具体未覆盖的行
open coverage/html/index.html
```

#### 编写针对性测试

```php
// 测试异常情况
test('handles file not found exception', function () {
    expect(fn() => parseNonExistentFile())
        ->toThrow(FileNotFoundException::class);
});

// 测试边界条件
test('handles empty input', function () {
    expect(parseEmptyString(''))->toBe([]);
});

// 测试不同分支
test('handles different conditions', function () {
    expect(processCondition(true))->toBe('branch1');
    expect(processCondition(false))->toBe('branch2');
});
```

### 2. 覆盖率阈值设置

```bash
# 设置全局阈值
pest --coverage --min=80

# 在配置文件中设置
```

```xml
<coverage>
    <report>
        <html outputDirectory="coverage/html" lowUpperBound="50" highLowerBound="80"/>
    </report>
</coverage>
```

### 3. 排除不需要测试的代码

```php
// 使用注解排除
// @codeCoverageIgnore
public function debugMethod(): void
{
    // 调试代码
}

// @codeCoverageIgnoreStart
if (defined('DEBUG') && DEBUG) {
    // 调试代码块
}
// @codeCoverageIgnoreEnd
```

## 🚀 性能优化

### 1. 选择合适的覆盖率驱动

```bash
# PCOV (更快，推荐用于 CI)
pest --coverage-html=coverage/html  # 使用 PCOV

# Xdebug (功能更全，推荐用于开发)
XDEBUG_MODE=coverage pest --coverage-html=coverage/html
```

### 2. 并行执行

```bash
# Pest 原生并行支持
pest --parallel --coverage-html=coverage/html

# 使用我们的脚本
./scripts/test.sh --parallel --coverage
```

### 3. 缓存优化

```bash
# 使用缓存目录
pest --cache-directory=.pest-cache --coverage-html=coverage/html
```

## 🔍 故障排除

### 常见问题

#### 1. "No code coverage driver available"

**原因**: 没有安装 Xdebug 或 PCOV

**解决方案**:
```bash
# 检查扩展
php -m | grep -E "(xdebug|pcov)"

# 安装 Xdebug
brew install php-xdebug  # macOS
sudo apt-get install php-xdebug  # Ubuntu

# 或安装 PCOV
pecl install pcov
```

#### 2. 覆盖率报告为空

**原因**: 配置问题或路径错误

**解决方案**:
```bash
# 检查配置
cat phpunit.xml | grep -A 10 coverage

# 验证源码路径
ls -la src/

# 使用绝对路径
pest --coverage-html=/full/path/to/coverage/html
```

#### 3. 覆盖率生成很慢

**原因**: 使用 Xdebug 或配置不当

**解决方案**:
```bash
# 使用 PCOV (更快)
pecl install pcov

# 或优化 Xdebug 配置
echo "xdebug.mode=coverage" >> php.ini
```

#### 4. 内存不足错误

**解决方案**:
```bash
# 增加内存限制
php -d memory_limit=512M vendor/bin/pest --coverage-html=coverage/html

# 或在配置中设置
echo "memory_limit=512M" >> php.ini
```

## 📈 CI/CD 集成

### GitHub Actions

```yaml
- name: Run tests with coverage
  run: |
    export XDEBUG_MODE=coverage
    ./scripts/test.sh --coverage

- name: Upload coverage to Codecov
  uses: codecov/codecov-action@v3
  with:
    file: coverage/clover.xml
```

### GitLab CI

```yaml
test:coverage:
  script:
    - export XDEBUG_MODE=coverage
    - ./scripts/test.sh --coverage
  artifacts:
    reports:
      coverage_report:
        coverage_format: cobertura
        path: coverage/clover.xml
```

## 📚 最佳实践

### 1. 开发流程

```bash
# 开发时快速测试（无覆盖率）
pest

# 提交前检查覆盖率
pest --coverage --min=80

# 生成详细报告
./scripts/test.sh --coverage --report
```

### 2. 覆盖率目标

- **新功能**: 90%+ 覆盖率
- **核心组件**: 85%+ 覆盖率
- **工具类**: 80%+ 覆盖率
- **总体目标**: 80%+ 覆盖率

### 3. 报告管理

```bash
# 定期清理旧报告
rm -rf coverage/

# 生成新报告
./scripts/test.sh --coverage --report

# 查看报告
open coverage/html/index.html
```

---

🎉 **Pest 的覆盖率功能非常强大！通过正确的配置和使用，可以获得详细、准确的代码覆盖率分析。**
