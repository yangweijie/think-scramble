# 贡献指南

感谢您对 ThinkScramble 项目的关注！我们欢迎各种形式的贡献，包括但不限于：

- 🐛 报告 Bug
- 💡 提出新功能建议
- 📝 改进文档
- 🔧 提交代码修复
- ✨ 添加新功能

## 🚀 开始之前

在开始贡献之前，请确保您已经：

1. 阅读了项目的 [README](README.md)
2. 了解了项目的基本架构和设计理念
3. 熟悉 ThinkPHP 8.0 框架
4. 具备 PHP 8.1+ 开发经验

## 📋 开发环境设置

### 1. Fork 和克隆项目

```bash
# Fork 项目到您的 GitHub 账户
# 然后克隆到本地
git clone https://github.com/YOUR_USERNAME/think-scramble.git
cd think-scramble
```

### 2. 安装依赖

```bash
composer install
```

### 3. 运行测试

```bash
# 运行所有测试
composer test

# 或使用 PHPUnit
composer phpunit
```

### 4. 代码风格检查

```bash
# 检查代码风格
composer cs-check

# 自动修复代码风格
composer cs-fix
```

## 🐛 报告 Bug

在报告 Bug 之前，请：

1. 搜索现有的 [Issues](https://github.com/yangweijie/think-scramble/issues) 确认问题未被报告
2. 确保您使用的是最新版本
3. 准备详细的重现步骤

### Bug 报告模板

```markdown
## Bug 描述
简要描述遇到的问题

## 重现步骤
1. 执行 '...'
2. 点击 '....'
3. 滚动到 '....'
4. 看到错误

## 期望行为
描述您期望发生的行为

## 实际行为
描述实际发生的行为

## 环境信息
- PHP 版本: [例如 8.1.0]
- ThinkPHP 版本: [例如 8.0.0]
- ThinkScramble 版本: [例如 1.0.0]
- 操作系统: [例如 Ubuntu 20.04]

## 附加信息
添加任何其他有助于解决问题的信息
```

## 💡 功能建议

我们欢迎新功能建议！请：

1. 搜索现有的 Issues 确认建议未被提出
2. 详细描述功能的用途和价值
3. 考虑功能的实现复杂度和维护成本

### 功能建议模板

```markdown
## 功能描述
简要描述建议的功能

## 问题背景
描述这个功能要解决的问题

## 解决方案
描述您希望的解决方案

## 替代方案
描述您考虑过的其他解决方案

## 附加信息
添加任何其他相关信息或截图
```

## 🔧 代码贡献

### 1. 创建分支

```bash
# 从 main 分支创建新分支
git checkout -b feature/your-feature-name

# 或者修复 Bug
git checkout -b fix/your-bug-fix
```

### 2. 编写代码

请遵循以下原则：

- **代码风格**: 遵循 PSR-12 编码标准
- **类型声明**: 使用严格的类型声明 `declare(strict_types=1);`
- **文档注释**: 为公共方法添加 PHPDoc 注释
- **测试覆盖**: 为新功能编写测试用例
- **向后兼容**: 避免破坏性变更

### 3. 编写测试

我们使用 Pest 作为主要测试框架：

```php
<?php

describe('YourFeature', function () {
    it('can do something', function () {
        // 测试代码
        expect($result)->toBe($expected);
    });
    
    it('handles edge cases', function () {
        // 边界情况测试
        expect($result)->toBeNull();
    });
});
```

### 4. 运行测试

```bash
# 运行所有测试
composer test

# 运行特定测试
composer test tests/Unit/YourTest.php

# 生成覆盖率报告
composer test:coverage
```

### 5. 提交代码

```bash
# 添加文件
git add .

# 提交（使用有意义的提交信息）
git commit -m "feat: add new feature for API documentation"

# 推送到您的 fork
git push origin feature/your-feature-name
```

### 6. 创建 Pull Request

1. 访问您的 fork 页面
2. 点击 "New Pull Request"
3. 选择正确的分支
4. 填写 PR 描述

### Pull Request 模板

```markdown
## 变更类型
- [ ] Bug 修复
- [ ] 新功能
- [ ] 文档更新
- [ ] 性能优化
- [ ] 重构

## 变更描述
简要描述此 PR 的变更内容

## 相关 Issue
关闭 #(issue 编号)

## 测试
- [ ] 添加了新的测试用例
- [ ] 所有测试通过
- [ ] 手动测试通过

## 检查清单
- [ ] 代码遵循项目的编码规范
- [ ] 自我审查了代码变更
- [ ] 添加了必要的注释
- [ ] 更新了相关文档
- [ ] 变更不会产生新的警告
- [ ] 添加了测试证明修复有效或功能正常
- [ ] 新的和现有的单元测试都通过
```

## 📝 文档贡献

文档同样重要！您可以：

- 修复文档中的错误
- 添加使用示例
- 改进 API 文档
- 翻译文档

文档位于以下位置：
- `README.md` - 项目主要文档
- `docs/` - 详细文档
- 代码中的 PHPDoc 注释

## 🎯 代码风格

### PHP 代码风格

我们遵循 PSR-12 标准，主要规则包括：

```php
<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Example;

use Some\Namespace\ClassA;
use Some\Namespace\ClassB;

/**
 * 示例类
 */
class ExampleClass
{
    /**
     * 示例方法
     *
     * @param string $param 参数描述
     * @return bool 返回值描述
     */
    public function exampleMethod(string $param): bool
    {
        if ($param === 'test') {
            return true;
        }

        return false;
    }
}
```

### 命名约定

- **类名**: PascalCase (例如: `OpenApiGenerator`)
- **方法名**: camelCase (例如: `generateDocument`)
- **变量名**: camelCase (例如: `$documentData`)
- **常量名**: UPPER_SNAKE_CASE (例如: `MAX_CACHE_SIZE`)

## 🧪 测试指南

### 单元测试

```php
<?php

describe('ConfigManager', function () {
    beforeEach(function () {
        $this->config = new ConfigManager();
    });

    it('can set and get configuration', function () {
        $this->config->set('test.key', 'value');
        
        expect($this->config->get('test.key'))->toBe('value');
    });
});
```

### 集成测试

```php
<?php

describe('Document Generation Integration', function () {
    it('can generate complete OpenAPI document', function () {
        $generator = new OpenApiGenerator($this->app, $this->config);
        $document = $generator->generate();

        expect($document)->toHaveValidOpenApiStructure();
    });
});
```

## 📊 性能考虑

在贡献代码时，请考虑：

- **内存使用**: 避免不必要的内存占用
- **执行时间**: 优化算法复杂度
- **缓存策略**: 合理使用缓存机制
- **数据库查询**: 避免 N+1 查询问题

## 🔍 代码审查

所有 PR 都需要经过代码审查。审查重点包括：

- 代码质量和可读性
- 测试覆盖率
- 性能影响
- 安全性考虑
- 向后兼容性

## 📞 获取帮助

如果您在贡献过程中遇到问题，可以：

1. 查看现有的 [Issues](https://github.com/yangweijie/think-scramble/issues)
2. 创建新的 Issue 寻求帮助
3. 在 PR 中 @维护者

## 🙏 致谢

感谢所有为 ThinkScramble 项目做出贡献的开发者！

---

再次感谢您的贡献！🎉
