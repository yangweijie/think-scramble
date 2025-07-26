<?php

/**
 * 测试所有导出格式的示例脚本
 */

echo "=== ThinkScramble 导出格式测试 ===\n\n";

// 支持的格式列表
$formats = [
    'json' => 'OpenAPI JSON format',
    'yaml' => 'OpenAPI YAML format', 
    'html' => 'Static HTML documentation',
    'postman' => 'Postman collection',
    'insomnia' => 'Insomnia workspace',
    'eolink' => 'Eolink API management platform',
    'jmeter' => 'Apache JMeter test plan',
    'yapi' => 'YApi interface management platform',
    'apidoc' => 'ApiDoc documentation format',
    'apipost' => 'ApiPost collection format',
    'apifox' => 'ApiFox collection format',
    'har' => 'HTTP Archive format',
    'rap' => 'RAP interface management platform',
    'wsdl' => 'Web Services Description Language',
    'showdoc' => 'ShowDoc documentation format',
];

echo "支持的导出格式：\n";
foreach ($formats as $format => $description) {
    echo sprintf("  %-12s %s\n", $format, $description);
}

echo "\n=== 导出命令示例 ===\n\n";

// 基本导出命令
echo "1. 基本导出命令：\n";
foreach ($formats as $format => $description) {
    echo "   php think scramble:export -f {$format}\n";
}

echo "\n2. 指定输出文件：\n";
$examples = [
    'json' => 'docs/api.json',
    'yaml' => 'docs/api.yaml',
    'html' => 'docs/',
    'postman' => 'collections/postman.json',
    'insomnia' => 'collections/insomnia.json',
    'eolink' => 'exports/eolink-collection.json',
    'jmeter' => 'tests/api-testplan.jmx',
    'yapi' => 'exports/yapi-project.json',
    'apidoc' => 'docs/apidoc-data.json',
    'apipost' => 'collections/apipost.json',
    'apifox' => 'collections/apifox.json',
    'har' => 'tests/api-requests.har',
    'rap' => 'exports/rap-project.json',
    'wsdl' => 'services/api.wsdl',
    'showdoc' => 'docs/showdoc-data.json',
];

foreach ($examples as $format => $outputPath) {
    echo "   php think scramble:export -f {$format} -o {$outputPath}\n";
}

echo "\n3. 带选项的导出：\n";
echo "   php think scramble:export -f postman --title=\"My API\" --include-examples\n";
echo "   php think scramble:export -f html --template=custom-template.html\n";
echo "   php think scramble:export -f json --compress --quiet\n";
echo "   php think scramble:export -f yaml --api-version=2.0.0\n";

echo "\n=== 批量导出脚本 ===\n\n";

// 生成批量导出脚本
$batchScript = <<<'BASH'
#!/bin/bash

# ThinkScramble 批量导出脚本
# 将 API 文档导出为多种格式

echo "开始批量导出 API 文档..."

# 创建输出目录
mkdir -p exports/{collections,tests,docs,services}

# 标准格式
echo "导出标准格式..."
php think scramble:export -f json -o exports/api.json
php think scramble:export -f yaml -o exports/api.yaml
php think scramble:export -f html -o exports/docs/

# API 管理平台格式
echo "导出 API 管理平台格式..."
php think scramble:export -f postman -o exports/collections/postman-collection.json
php think scramble:export -f insomnia -o exports/collections/insomnia-workspace.json
php think scramble:export -f eolink -o exports/collections/eolink-collection.json
php think scramble:export -f yapi -o exports/collections/yapi-project.json
php think scramble:export -f apipost -o exports/collections/apipost-collection.json
php think scramble:export -f apifox -o exports/collections/apifox-collection.json
php think scramble:export -f rap -o exports/collections/rap-project.json

# 测试工具格式
echo "导出测试工具格式..."
php think scramble:export -f jmeter -o exports/tests/jmeter-testplan.jmx
php think scramble:export -f har -o exports/tests/api-requests.har

# 文档工具格式
echo "导出文档工具格式..."
php think scramble:export -f apidoc -o exports/docs/apidoc-data.json
php think scramble:export -f showdoc -o exports/docs/showdoc-data.json

# Web 服务格式
echo "导出 Web 服务格式..."
php think scramble:export -f wsdl -o exports/services/api-service.wsdl

echo "批量导出完成！"
echo "输出目录: exports/"
ls -la exports/
BASH;

echo "批量导出脚本内容：\n";
echo $batchScript;

echo "\n=== 使用说明 ===\n\n";

echo "1. 保存批量导出脚本：\n";
echo "   将上述脚本保存为 batch_export.sh\n";
echo "   chmod +x batch_export.sh\n";
echo "   ./batch_export.sh\n\n";

echo "2. 单个格式测试：\n";
echo "   php think scramble:export -f json\n";
echo "   php think scramble:export -f yaml\n";
echo "   php think scramble:export -f postman\n\n";

echo "3. 查看帮助信息：\n";
echo "   php think scramble:export --help\n\n";

echo "4. 验证导出结果：\n";
echo "   # JSON 格式验证\n";
echo "   php -r \"echo json_encode(json_decode(file_get_contents('exports.json')), JSON_PRETTY_PRINT);\"\n\n";
echo "   # YAML 格式验证\n";
echo "   cat exports.yaml | head -20\n\n";

echo "=== 格式特性说明 ===\n\n";

$formatFeatures = [
    'json' => ['标准格式', '通用性强', '易于解析', '版本控制友好'],
    'yaml' => ['人类可读', '配置友好', '支持注释', '层次清晰'],
    'html' => ['可视化展示', '交互式文档', '浏览器友好', '样式可定制'],
    'postman' => ['API 测试', '环境变量', '测试脚本', '团队协作'],
    'insomnia' => ['现代界面', '插件支持', 'GraphQL 支持', '代码生成'],
    'eolink' => ['团队协作', '版本管理', 'Mock 服务', '自动化测试'],
    'jmeter' => ['性能测试', '负载测试', '压力测试', '监控报告'],
    'yapi' => ['接口管理', 'Mock 数据', '自动化测试', '权限控制'],
    'apidoc' => ['注释驱动', '版本控制', '静态生成', '主题支持'],
    'apipost' => ['国产工具', '中文友好', '团队协作', '接口测试'],
    'apifox' => ['设计优先', '协作开发', '自动化测试', '数据模型'],
    'har' => ['网络分析', '请求记录', '性能分析', '调试工具'],
    'rap' => ['阿里开源', 'Mock 支持', '版本管理', '团队协作'],
    'wsdl' => ['SOAP 服务', '企业集成', 'XML 格式', '服务描述'],
    'showdoc' => ['简单易用', '快速部署', '在线编辑', '团队共享'],
];

foreach ($formatFeatures as $format => $features) {
    echo sprintf("%-12s: %s\n", strtoupper($format), implode(', ', $features));
}

echo "\n=== 推荐使用场景 ===\n\n";

$useCases = [
    '开发阶段' => ['json', 'yaml', 'html'],
    '测试阶段' => ['postman', 'insomnia', 'jmeter', 'har'],
    '文档发布' => ['html', 'apidoc', 'showdoc'],
    '团队协作' => ['eolink', 'yapi', 'rap', 'apifox', 'apipost'],
    '企业集成' => ['wsdl', 'json', 'yaml'],
    '性能测试' => ['jmeter', 'har'],
    '接口调试' => ['postman', 'insomnia', 'apipost'],
];

foreach ($useCases as $stage => $formats) {
    echo sprintf("%-12s: %s\n", $stage, implode(', ', $formats));
}

echo "\n=== 完成 ===\n";
echo "现在你可以使用 php think scramble:export -f <format> 命令导出任何支持的格式！\n";
