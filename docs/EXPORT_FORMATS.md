# API 文档导出格式支持

ThinkScramble 现在支持多种 API 文档导出格式，可以满足不同平台和工具的需求。

## 🚀 支持的导出格式

### 标准格式
- **JSON** - OpenAPI JSON 格式
- **YAML** - OpenAPI YAML 格式  
- **HTML** - 静态 HTML 文档

### API 管理平台
- **Postman** - Postman 集合格式
- **Insomnia** - Insomnia 工作空间格式
- **Eolink** - Eolink API 管理平台格式
- **YApi** - YApi 接口管理平台格式
- **ApiPost** - ApiPost 集合格式
- **ApiFox** - ApiFox 集合格式
- **RAP** - RAP 接口管理平台格式
- **ShowDoc** - ShowDoc 文档格式

### 测试工具
- **JMeter** - Apache JMeter 测试计划格式
- **HAR** - HTTP Archive 格式

### 文档工具
- **ApiDoc** - ApiDoc 文档格式

### Web 服务
- **WSDL** - Web Services Description Language

## 📝 使用方法

### 基本导出命令

```bash
# 导出为指定格式
php think scramble:export -f <format>

# 指定输出文件
php think scramble:export -f <format> -o <output_path>

# 添加标题和版本信息
php think scramble:export -f <format> --title="My API" --api-version="2.0.0"
```

### 具体格式示例

#### 1. 标准格式

```bash
# JSON 格式
php think scramble:export -f json -o docs/api.json

# YAML 格式
php think scramble:export -f yaml -o docs/api.yaml

# HTML 格式
php think scramble:export -f html -o docs/
```

#### 2. API 管理平台

```bash
# Postman 集合
php think scramble:export -f postman -o collections/api.json

# Insomnia 工作空间
php think scramble:export -f insomnia -o collections/workspace.json

# Eolink 格式
php think scramble:export -f eolink -o eolink/api-collection.json

# YApi 格式
php think scramble:export -f yapi -o yapi/project.json

# ApiPost 格式
php think scramble:export -f apipost -o apipost/collection.json

# ApiFox 格式
php think scramble:export -f apifox -o apifox/collection.json

# RAP 格式
php think scramble:export -f rap -o rap/project.json

# ShowDoc 格式
php think scramble:export -f showdoc -o showdoc/data.json
```

#### 3. 测试工具

```bash
# JMeter 测试计划
php think scramble:export -f jmeter -o tests/api-testplan.jmx

# HAR 格式
php think scramble:export -f har -o tests/api-requests.har
```

#### 4. 文档工具

```bash
# ApiDoc 格式
php think scramble:export -f apidoc -o docs/apidoc-data.json
```

#### 5. Web 服务

```bash
# WSDL 格式
php think scramble:export -f wsdl -o services/api.wsdl
```

## 📁 默认输出路径

如果不指定输出路径，系统会使用以下默认路径：

| 格式 | 默认文件名 | 说明 |
|------|------------|------|
| json | exports.json | OpenAPI JSON 格式 |
| yaml | exports.yaml | OpenAPI YAML 格式 |
| html | index.html | HTML 文档目录 |
| postman | postman-collection.json | Postman 集合 |
| insomnia | insomnia-workspace.json | Insomnia 工作空间 |
| eolink | eolink-collection.json | Eolink 集合 |
| jmeter | jmeter-testplan.jmx | JMeter 测试计划 |
| yapi | yapi-project.json | YApi 项目 |
| apidoc | apidoc-data.json | ApiDoc 数据 |
| apipost | apipost-collection.json | ApiPost 集合 |
| apifox | apifox-collection.json | ApiFox 集合 |
| har | api-requests.har | HAR 文件 |
| rap | rap-project.json | RAP 项目 |
| wsdl | api-service.wsdl | WSDL 服务 |
| showdoc | showdoc-data.json | ShowDoc 数据 |

## 🔧 高级选项

### 包含示例数据

```bash
# 包含请求/响应示例
php think scramble:export -f postman --include-examples
```

### 自定义模板（HTML 格式）

```bash
# 使用自定义模板
php think scramble:export -f html --template=custom-template.html
```

### 压缩输出

```bash
# 压缩输出文件
php think scramble:export -f json --compress
```

### 静默模式

```bash
# 静默导出，不显示进度信息
php think scramble:export -f yaml --quiet
```

## 📊 格式特性对比

| 格式 | 文件类型 | 用途 | 特点 |
|------|----------|------|------|
| JSON | .json | 标准格式 | 通用性强，易于解析 |
| YAML | .yaml | 标准格式 | 人类可读，配置友好 |
| HTML | .html | 文档展示 | 可视化，支持交互 |
| Postman | .json | API 测试 | 支持测试脚本，环境变量 |
| Insomnia | .json | API 测试 | 现代化界面，插件支持 |
| Eolink | .json | API 管理 | 团队协作，版本管理 |
| JMeter | .jmx | 性能测试 | 负载测试，性能监控 |
| YApi | .json | 接口管理 | Mock 数据，自动化测试 |
| ApiDoc | .json | 文档生成 | 注释驱动，版本控制 |
| ApiPost | .json | API 调试 | 国产工具，中文友好 |
| ApiFox | .json | API 设计 | 设计优先，协作开发 |
| HAR | .har | 网络分析 | 请求记录，性能分析 |
| RAP | .json | 接口管理 | 阿里开源，Mock 支持 |
| WSDL | .wsdl | Web 服务 | SOAP 服务，企业集成 |
| ShowDoc | .json | 文档展示 | 简单易用，快速部署 |

## 🎯 使用场景

### 开发阶段
- **JSON/YAML**: 标准格式，用于版本控制和工具集成
- **HTML**: 开发文档，团队内部查看

### 测试阶段  
- **Postman/Insomnia**: API 功能测试
- **JMeter**: 性能和负载测试
- **HAR**: 网络请求分析

### 部署阶段
- **ApiDoc**: 生成静态文档网站
- **ShowDoc**: 快速部署文档服务

### 团队协作
- **Eolink/YApi/RAP**: 团队 API 管理
- **ApiFox/ApiPost**: 协作设计和测试

### 企业集成
- **WSDL**: 企业 SOA 架构集成

## 🔍 故障排除

### 常见问题

1. **导出失败**
   ```bash
   # 检查输出目录权限
   mkdir -p exports/
   chmod 755 exports/
   ```

2. **格式不支持**
   ```bash
   # 查看支持的格式列表
   php think scramble:export --help
   ```

3. **文件覆盖**
   ```bash
   # 强制覆盖现有文件
   php think scramble:export -f json --force
   ```

### 调试模式

```bash
# 启用详细输出
php think scramble:export -f json -v

# 查看错误详情
php think scramble:export -f json -vv
```

## 📚 扩展开发

如果需要支持新的导出格式，可以：

1. 在 `$supportedFormats` 数组中添加新格式
2. 在 `exportDocumentation` 方法中添加处理分支
3. 实现对应的 `export{Format}` 方法
4. 实现格式转换方法 `convertTo{Format}Format`

示例：
```php
// 添加新格式支持
protected array $supportedFormats = [
    // ... 现有格式
    'newformat' => 'New Format Description',
];

// 添加导出方法
protected function exportNewFormat(array $document, string $outputPath, Input $input): string
{
    $filePath = $this->ensureFileExtension($outputPath, 'ext');
    $content = $this->convertToNewFormat($document);
    $this->writeFile($filePath, $content);
    return $filePath;
}
```
