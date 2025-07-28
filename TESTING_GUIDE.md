# 🧪 ThinkScramble 测试指南

本指南详细介绍了 ThinkScramble 的测试框架、覆盖率分析和最佳实践。

## 📋 测试概览

ThinkScramble 使用现代化的测试栈：

- **测试框架**: Pest (基于 PHPUnit)
- **覆盖率工具**: Xdebug/PCOV + PHPUnit Coverage
- **静态分析**: PHPStan
- **代码风格**: PHP-CS-Fixer
- **CI/CD**: GitHub Actions

## 🚀 快速开始

### 基本测试命令

```bash
# 运行所有测试
composer test

# 运行特定测试套件
composer test:unit          # 单元测试
composer test:feature       # 功能测试
composer test:integration   # 集成测试

# 运行带覆盖率的测试
composer test:coverage

# 生成 HTML 覆盖率报告
composer test:coverage-html
```

### 使用测试脚本

```bash
# Linux/macOS
./scripts/test.sh --help

# Windows
scripts\test.bat --help

# 常用选项
./scripts/test.sh --coverage                    # 生成覆盖率
./scripts/test.sh --testsuite Unit              # 运行单元测试
./scripts/test.sh --filter ControllerTest       # 过滤测试
./scripts/test.sh --parallel                    # 并行执行
./scripts/test.sh --verbose                     # 详细输出
```

## 📊 覆盖率分析

### 覆盖率阈值

项目设置了以下覆盖率阈值：

- **行覆盖率**: 80%
- **函数覆盖率**: 80%
- **类覆盖率**: 80%
- **方法覆盖率**: 80%
- **分支覆盖率**: 70%

### 生成覆盖率报告

```bash
# 运行测试并生成覆盖率
composer coverage:report

# 只分析现有覆盖率数据
composer coverage:analyse

# 查看 HTML 报告
open coverage/html/index.html
```

### 覆盖率报告文件

```
coverage/
├── html/                   # HTML 格式报告
│   ├── index.html         # 主页面
│   └── ...                # 详细页面
├── xml/                   # XML 格式报告
├── clover.xml             # Clover 格式 (CI 使用)
├── coverage.txt           # 文本格式摘要
├── analysis-report.txt    # 详细分析报告
└── junit.xml              # JUnit 格式 (CI 使用)
```

## 🏗️ 测试结构

### 目录结构

```
tests/
├── Feature/               # 功能测试
│   ├── CoverageTest.php  # 覆盖率专用测试
│   └── ...
├── Integration/           # 集成测试
│   ├── DocumentBuilderTest.php
│   └── ...
├── Unit/                  # 单元测试
│   ├── Parser/
│   ├── Cache/
│   └── ...
├── Support/               # 测试支持类
│   ├── TestCase.php      # 基础测试类
│   └── TestDataGenerator.php # 测试数据生成器
├── data/                  # 测试数据文件
└── Pest.php              # Pest 配置
```

### 测试套件说明

#### 1. 单元测试 (Unit)
- 测试单个类或方法
- 不依赖外部资源
- 执行速度快
- 覆盖核心逻辑

#### 2. 功能测试 (Feature)
- 测试完整功能流程
- 可能涉及多个类协作
- 测试用户场景
- 包含边缘情况

#### 3. 集成测试 (Integration)
- 测试组件间集成
- 测试与外部系统交互
- 端到端测试
- 性能测试

## ✍️ 编写测试

### 基本测试示例

```php
<?php

use Yangweijie\ThinkScramble\DocumentBuilder;

it('can build basic document', function () {
    $builder = new DocumentBuilder();
    $document = $builder->build([]);
    
    expect($document)
        ->toBeArray()
        ->toHaveKey('openapi')
        ->toHaveKey('info')
        ->toHaveKey('paths');
});

test('document builder with controllers', function () {
    $controllerFile = createTestController('User', [
        'index' => ['summary' => 'Get users'],
    ]);
    
    $builder = new DocumentBuilder();
    $document = $builder->build([$controllerFile]);
    
    expect($document['paths'])->not->toBeEmpty();
    
    cleanupTestFiles();
});
```

### 使用测试数据生成器

```php
use Yangweijie\ThinkScramble\Tests\Support\TestDataGenerator;

test('controller parsing', function () {
    // 生成测试控制器
    $controllerCode = TestDataGenerator::generateController('User');
    $controllerFile = TestDataGenerator::createTempFile($controllerCode);
    
    // 执行测试
    $parser = new ControllerParser();
    $result = $parser->parse($controllerFile);
    
    expect($result)->not->toBeEmpty();
    
    // 清理
    TestDataGenerator::cleanupTempFiles();
});
```

### 自定义断言

```php
// 验证 OpenAPI 文档
expect($document)->toBeValidOpenApi();

// 验证路径项
expect($pathItem)->toBeValidPath();

// 验证 Schema
expect($schema)->toBeValidSchema();
```

## 🎯 覆盖率优化

### 提高覆盖率的策略

#### 1. 识别未覆盖代码

```bash
# 运行覆盖率分析
php scripts/coverage-analysis.php

# 查看详细报告
cat coverage/analysis-report.txt
```

#### 2. 针对性编写测试

```php
/**
 * 专门用于提高覆盖率的测试
 * 
 * @covers \Yangweijie\ThinkScramble\SomeClass
 */
class CoverageTest extends TestCase
{
    public function test_edge_cases(): void
    {
        // 测试异常情况
        expect(fn() => $class->methodWithException())
            ->toThrow(SomeException::class);
            
        // 测试边界值
        expect($class->methodWithBoundary(0))->toBe('min');
        expect($class->methodWithBoundary(100))->toBe('max');
        
        // 测试不同分支
        expect($class->conditionalMethod(true))->toBe('branch1');
        expect($class->conditionalMethod(false))->toBe('branch2');
    }
}
```

#### 3. 测试私有方法

```php
// 通过反射测试私有方法
test('private method coverage', function () {
    $class = new SomeClass();
    $reflection = new ReflectionClass($class);
    $method = $reflection->getMethod('privateMethod');
    $method->setAccessible(true);
    
    $result = $method->invoke($class, 'test');
    expect($result)->toBe('expected');
});

// 或通过公共接口测试
test('private method via public interface', function () {
    $class = new SomeClass();
    // 调用公共方法，间接测试私有方法
    $result = $class->publicMethodThatCallsPrivate();
    expect($result)->toBe('expected');
});
```

### 排除不需要覆盖的代码

在 `phpunit.xml` 中配置：

```xml
<source>
    <include>
        <directory>src</directory>
    </include>
    <exclude>
        <directory>src/Exception</directory>
        <file>src/Service/ScrambleServiceProvider.php</file>
    </exclude>
</source>
```

或使用注解：

```php
// @codeCoverageIgnore
public function debugMethod(): void
{
    // 调试代码，不需要测试覆盖
}

// @codeCoverageIgnoreStart
if (defined('DEBUG') && DEBUG) {
    // 调试代码块
}
// @codeCoverageIgnoreEnd
```

## 🔧 配置和工具

### PHPUnit 配置

`phpunit.xml` 主要配置：

```xml
<phpunit>
    <!-- 测试套件 -->
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
    </testsuites>
    
    <!-- 覆盖率配置 -->
    <coverage includeUncoveredFiles="true">
        <report>
            <html outputDirectory="coverage/html"/>
            <clover outputFile="coverage/clover.xml"/>
        </report>
    </coverage>
</phpunit>
```

### Pest 配置

`tests/Pest.php` 主要配置：

```php
// 使用基础测试类
uses(TestCase::class)->in('Feature', 'Unit', 'Integration');

// 自定义期望
expect()->extend('toBeValidOpenApi', function () {
    return $this->toBeArray()
        ->toHaveKey('openapi')
        ->toHaveKey('info')
        ->toHaveKey('paths');
});

// 覆盖率阈值
function getCoverageThresholds(): array
{
    return [
        'line' => 80,
        'function' => 80,
        'class' => 80,
        'method' => 80,
    ];
}
```

## 🚀 CI/CD 集成

### GitHub Actions

测试工作流 (`.github/workflows/tests.yml`) 包含：

- **多版本测试**: PHP 8.0-8.3
- **多平台测试**: Linux, Windows, macOS
- **覆盖率报告**: 自动生成和上传
- **并行执行**: 提高测试速度
- **性能测试**: 大型项目基准测试

### 覆盖率服务集成

```yaml
# Codecov
- name: Upload to Codecov
  uses: codecov/codecov-action@v3
  with:
    file: coverage/clover.xml

# Code Climate
- name: Upload to Code Climate
  uses: paambaati/codeclimate-action@v5.0.0
  env:
    CC_TEST_REPORTER_ID: ${{ secrets.CC_TEST_REPORTER_ID }}
  with:
    coverageLocations: coverage/clover.xml:clover
```

## 📈 性能测试

### 基准测试

```bash
# 运行性能测试
./scripts/test.sh --testsuite Integration --filter Performance

# 生成大型项目进行测试
php tests/Support/generate-large-project.php
time php bin/scramble --output=temp/large-project.json
```

### 内存使用监控

```php
test('memory usage within limits', function () {
    $startMemory = memory_get_usage();
    
    $builder = new DocumentBuilder();
    $document = $builder->build($largeControllerList);
    
    $endMemory = memory_get_usage();
    $memoryUsed = $endMemory - $startMemory;
    
    // 确保内存使用在合理范围内 (例如 < 50MB)
    expect($memoryUsed)->toBeLessThan(50 * 1024 * 1024);
});
```

## 🐛 调试测试

### 调试技巧

```bash
# 运行单个测试
./scripts/test.sh --filter "specific test name"

# 详细输出
./scripts/test.sh --verbose

# 停止在第一个失败
./scripts/test.sh --stop-on-failure

# 调试模式
XDEBUG_MODE=debug ./scripts/test.sh --filter "test name"
```

### 测试数据检查

```php
test('debug test data', function () {
    $data = generateTestData();
    
    // 输出调试信息
    dump($data);
    
    // 或写入文件
    file_put_contents('debug.json', json_encode($data, JSON_PRETTY_PRINT));
    
    expect($data)->toBeArray();
});
```

## 📚 最佳实践

### 1. 测试命名

```php
// ✅ 好的命名
test('can parse controller with multiple methods')
test('throws exception when file not found')
test('generates correct openapi schema for user model')

// ❌ 不好的命名
test('test1')
test('controller test')
test('it works')
```

### 2. 测试组织

```php
// 使用 describe 组织相关测试
describe('DocumentBuilder', function () {
    test('can build empty document')
    test('can build document with controllers')
    test('handles invalid controllers gracefully')
});
```

### 3. 数据提供者

```php
// 使用数据集测试多种情况
test('validates different input types', function ($input, $expected) {
    expect(validateInput($input))->toBe($expected);
})->with([
    ['valid string', true],
    ['', false],
    [null, false],
    [123, false],
]);
```

### 4. 设置和清理

```php
beforeEach(function () {
    $this->builder = new DocumentBuilder();
    $this->tempFiles = [];
});

afterEach(function () {
    // 清理临时文件
    foreach ($this->tempFiles as $file) {
        if (file_exists($file)) {
            unlink($file);
        }
    }
});
```

## 🎯 覆盖率目标

### 当前状态

- **总体覆盖率**: 目标 80%+
- **核心组件**: 目标 90%+
- **工具类**: 目标 85%+
- **异常处理**: 目标 75%+

### 持续改进

1. **每周覆盖率检查**
2. **新功能必须包含测试**
3. **重构时保持覆盖率**
4. **定期审查测试质量**

---

🎉 **通过完善的测试和覆盖率分析，确保 ThinkScramble 的代码质量和可靠性！**
