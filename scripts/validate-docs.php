<?php

/**
 * ThinkScramble 文档验证脚本
 */

declare(strict_types=1);

echo "🔍 Validating ThinkScramble Documentation\n";
echo "=========================================\n\n";

$errors = [];
$warnings = [];
$info = [];

// 检查必要文件
function checkRequiredFiles(): array
{
    $requiredFiles = [
        'docs/index.html' => 'Docsify 主页面',
        'docs/_sidebar.md' => '侧边栏导航',
        'docs/_navbar.md' => '顶部导航',
        'docs/_coverpage.md' => '封面页',
        'docs/_404.md' => '404 页面',
        'docs/.nojekyll' => 'Jekyll 禁用文件',
        'docs/README.md' => '主页内容',
        'docs/quickstart.md' => '快速开始',
        'docs/installation.md' => '安装指南',
        'docs/faq.md' => '常见问题',
        'docs/changelog.md' => '更新日志',
        'docs/pie-installation.md' => 'PIE 安装指南',
    ];

    $errors = [];
    $info = [];

    foreach ($requiredFiles as $file => $description) {
        if (file_exists($file)) {
            $info[] = "✅ {$description}: {$file}";
        } else {
            $errors[] = "❌ 缺少文件: {$file} ({$description})";
        }
    }

    return ['errors' => $errors, 'info' => $info];
}

// 检查文档内容
function checkDocumentContent(): array
{
    $errors = [];
    $warnings = [];
    $info = [];

    // 检查 index.html
    if (file_exists('docs/index.html')) {
        $content = file_get_contents('docs/index.html');
        
        if (strpos($content, 'docsify') === false) {
            $errors[] = "❌ index.html 不包含 docsify 配置";
        } else {
            $info[] = "✅ index.html 包含 docsify 配置";
        }

        if (strpos($content, 'ThinkScramble') === false) {
            $warnings[] = "⚠️ index.html 标题可能不正确";
        } else {
            $info[] = "✅ index.html 标题正确";
        }
    }

    // 检查侧边栏
    if (file_exists('docs/_sidebar.md')) {
        $content = file_get_contents('docs/_sidebar.md');
        
        $requiredSections = [
            '快速开始' => 'quickstart.md',
            '安装指南' => 'installation.md',
            'PIE 安装' => 'pie-installation.md',
            '常见问题' => 'faq.md',
        ];

        foreach ($requiredSections as $section => $file) {
            if (strpos($content, $file) === false) {
                $warnings[] = "⚠️ 侧边栏缺少 {$section} 链接";
            } else {
                $info[] = "✅ 侧边栏包含 {$section} 链接";
            }
        }
    }

    return ['errors' => $errors, 'warnings' => $warnings, 'info' => $info];
}

// 检查链接有效性
function checkLinks(): array
{
    $errors = [];
    $warnings = [];
    $info = [];

    $markdownFiles = glob('docs/*.md');
    $linkPattern = '/\[([^\]]+)\]\(([^)]+)\)/';

    foreach ($markdownFiles as $file) {
        $content = file_get_contents($file);
        $filename = basename($file);
        
        preg_match_all($linkPattern, $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $linkText = $match[1];
            $linkUrl = $match[2];
            
            // 跳过外部链接
            if (strpos($linkUrl, 'http') === 0 || strpos($linkUrl, '#') === 0) {
                continue;
            }
            
            // 检查本地文件链接
            $targetFile = 'docs/' . $linkUrl;
            if (!file_exists($targetFile)) {
                $errors[] = "❌ {$filename}: 链接目标不存在 '{$linkUrl}'";
            }
        }
    }

    if (empty($errors)) {
        $info[] = "✅ 所有本地链接都有效";
    }

    return ['errors' => $errors, 'warnings' => $warnings, 'info' => $info];
}

// 检查图片资源
function checkAssets(): array
{
    $errors = [];
    $warnings = [];
    $info = [];

    $assetFiles = [
        'docs/assets/logo.svg' => 'Logo 图片',
        'docs/assets/bg.svg' => '背景图片',
    ];

    foreach ($assetFiles as $file => $description) {
        if (file_exists($file)) {
            $info[] = "✅ {$description}: {$file}";
            
            // 检查 SVG 文件格式
            if (pathinfo($file, PATHINFO_EXTENSION) === 'svg') {
                $content = file_get_contents($file);
                if (strpos($content, '<svg') === false) {
                    $errors[] = "❌ {$file} 不是有效的 SVG 文件";
                }
            }
        } else {
            $warnings[] = "⚠️ 缺少资源文件: {$file} ({$description})";
        }
    }

    return ['errors' => $errors, 'warnings' => $warnings, 'info' => $info];
}

// 检查 GitHub Actions 配置
function checkGitHubActions(): array
{
    $errors = [];
    $warnings = [];
    $info = [];

    $workflowFile = '.github/workflows/docs.yml';
    
    if (file_exists($workflowFile)) {
        $info[] = "✅ GitHub Actions 工作流存在: {$workflowFile}";
        
        $content = file_get_contents($workflowFile);
        
        if (strpos($content, 'github-pages') === false) {
            $warnings[] = "⚠️ GitHub Actions 可能未配置 Pages 部署";
        } else {
            $info[] = "✅ GitHub Actions 配置了 Pages 部署";
        }
    } else {
        $warnings[] = "⚠️ 缺少 GitHub Actions 工作流: {$workflowFile}";
    }

    return ['errors' => $errors, 'warnings' => $warnings, 'info' => $info];
}

// 生成文档统计
function generateStats(): array
{
    $stats = [
        'total_files' => 0,
        'markdown_files' => 0,
        'total_size' => 0,
        'largest_file' => '',
        'largest_size' => 0,
    ];

    if (is_dir('docs')) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator('docs', RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if ($file->isFile()) {
                $stats['total_files']++;
                $size = $file->getSize();
                $stats['total_size'] += $size;
                
                if ($file->getExtension() === 'md') {
                    $stats['markdown_files']++;
                }
                
                if ($size > $stats['largest_size']) {
                    $stats['largest_size'] = $size;
                    $stats['largest_file'] = $file->getPathname();
                }
            }
        }
    }

    return $stats;
}

// 运行所有检查
echo "🔍 检查必要文件...\n";
$fileCheck = checkRequiredFiles();
$errors = array_merge($errors, $fileCheck['errors']);
$info = array_merge($info, $fileCheck['info']);

echo "\n📄 检查文档内容...\n";
$contentCheck = checkDocumentContent();
$errors = array_merge($errors, $contentCheck['errors']);
$warnings = array_merge($warnings, $contentCheck['warnings'] ?? []);
$info = array_merge($info, $contentCheck['info']);

echo "\n🔗 检查链接有效性...\n";
$linkCheck = checkLinks();
$errors = array_merge($errors, $linkCheck['errors']);
$warnings = array_merge($warnings, $linkCheck['warnings'] ?? []);
$info = array_merge($info, $linkCheck['info']);

echo "\n🖼️ 检查资源文件...\n";
$assetCheck = checkAssets();
$errors = array_merge($errors, $assetCheck['errors']);
$warnings = array_merge($warnings, $assetCheck['warnings'] ?? []);
$info = array_merge($info, $assetCheck['info']);

echo "\n⚙️ 检查 GitHub Actions...\n";
$actionsCheck = checkGitHubActions();
$errors = array_merge($errors, $actionsCheck['errors']);
$warnings = array_merge($warnings, $actionsCheck['warnings'] ?? []);
$info = array_merge($info, $actionsCheck['info']);

// 生成统计信息
$stats = generateStats();

// 输出结果
echo "\n📊 文档统计信息\n";
echo "================\n";
echo "总文件数: {$stats['total_files']}\n";
echo "Markdown 文件: {$stats['markdown_files']}\n";
echo "总大小: " . formatBytes($stats['total_size']) . "\n";
echo "最大文件: {$stats['largest_file']} (" . formatBytes($stats['largest_size']) . ")\n";

echo "\n📋 验证结果\n";
echo "============\n";

if (!empty($info)) {
    echo "✅ 成功项目:\n";
    foreach ($info as $item) {
        echo "   {$item}\n";
    }
    echo "\n";
}

if (!empty($warnings)) {
    echo "⚠️ 警告项目:\n";
    foreach ($warnings as $warning) {
        echo "   {$warning}\n";
    }
    echo "\n";
}

if (!empty($errors)) {
    echo "❌ 错误项目:\n";
    foreach ($errors as $error) {
        echo "   {$error}\n";
    }
    echo "\n";
}

// 总结
$totalIssues = count($errors) + count($warnings);
echo "📈 总结\n";
echo "========\n";
echo "✅ 成功: " . count($info) . "\n";
echo "⚠️ 警告: " . count($warnings) . "\n";
echo "❌ 错误: " . count($errors) . "\n";

if (count($errors) === 0) {
    echo "\n🎉 文档验证通过！\n";
    echo "📚 文档已准备好发布到 GitHub Pages\n";
    echo "🌐 预览地址: https://yangweijie.github.io/think-scramble/\n";
    exit(0);
} else {
    echo "\n🚨 文档验证失败！\n";
    echo "💡 请修复上述错误后重新验证\n";
    exit(1);
}

/**
 * 格式化字节数
 */
function formatBytes(int $bytes, int $precision = 2): string
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= (1 << (10 * $pow));
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}
