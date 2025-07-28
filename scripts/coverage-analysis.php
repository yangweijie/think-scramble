<?php

/**
 * ThinkScramble 覆盖率分析脚本
 */

declare(strict_types=1);

echo "📊 ThinkScramble Coverage Analysis\n";
echo "==================================\n\n";

// 配置
$coverageFile = 'coverage/clover.xml';
$htmlDir = 'coverage/html';
$thresholds = [
    'line' => 80,
    'function' => 80,
    'class' => 80,
    'method' => 80,
    'branch' => 70,
];

// 检查覆盖率文件
if (!file_exists($coverageFile)) {
    echo "❌ Coverage file not found: {$coverageFile}\n";
    echo "💡 Run tests with coverage first: ./scripts/test.sh --coverage\n";
    exit(1);
}

// 解析覆盖率数据
function parseCoverageData(string $file): array
{
    $xml = simplexml_load_file($file);
    
    if (!$xml) {
        throw new Exception("Failed to parse coverage XML file");
    }
    
    $metrics = $xml->project->metrics;
    
    return [
        'lines' => [
            'covered' => (int) $metrics['coveredstatements'],
            'total' => (int) $metrics['statements'],
            'percentage' => $metrics['statements'] > 0 
                ? round(($metrics['coveredstatements'] / $metrics['statements']) * 100, 2)
                : 0,
        ],
        'functions' => [
            'covered' => (int) $metrics['coveredmethods'],
            'total' => (int) $metrics['methods'],
            'percentage' => $metrics['methods'] > 0 
                ? round(($metrics['coveredmethods'] / $metrics['methods']) * 100, 2)
                : 0,
        ],
        'classes' => [
            'covered' => (int) $metrics['coveredclasses'],
            'total' => (int) $metrics['classes'],
            'percentage' => $metrics['classes'] > 0 
                ? round(($metrics['coveredclasses'] / $metrics['classes']) * 100, 2)
                : 0,
        ],
    ];
}

// 获取文件级别的覆盖率
function getFileCoverage(string $file): array
{
    $xml = simplexml_load_file($file);
    $files = [];
    
    foreach ($xml->project->package as $package) {
        foreach ($package->file as $fileNode) {
            $filename = (string) $fileNode['name'];
            $metrics = $fileNode->metrics;
            
            $files[$filename] = [
                'lines' => [
                    'covered' => (int) $metrics['coveredstatements'],
                    'total' => (int) $metrics['statements'],
                    'percentage' => $metrics['statements'] > 0 
                        ? round(($metrics['coveredstatements'] / $metrics['statements']) * 100, 2)
                        : 0,
                ],
                'methods' => [
                    'covered' => (int) $metrics['coveredmethods'],
                    'total' => (int) $metrics['methods'],
                    'percentage' => $metrics['methods'] > 0 
                        ? round(($metrics['coveredmethods'] / $metrics['methods']) * 100, 2)
                        : 0,
                ],
                'classes' => [
                    'covered' => (int) $metrics['coveredclasses'],
                    'total' => (int) $metrics['classes'],
                    'percentage' => $metrics['classes'] > 0 
                        ? round(($metrics['coveredclasses'] / $metrics['classes']) * 100, 2)
                        : 0,
                ],
            ];
        }
    }
    
    return $files;
}

// 生成覆盖率报告
function generateCoverageReport(array $coverage, array $thresholds): string
{
    $report = "Coverage Report\n";
    $report .= "===============\n\n";
    
    $report .= "Overall Coverage:\n";
    $report .= "-----------------\n";
    
    foreach ($coverage as $type => $data) {
        $threshold = $thresholds[substr($type, 0, -1)] ?? 0;
        $status = $data['percentage'] >= $threshold ? '✅' : '❌';
        
        $report .= sprintf(
            "%s %s: %.2f%% (%d/%d) [Threshold: %d%%]\n",
            $status,
            ucfirst($type),
            $data['percentage'],
            $data['covered'],
            $data['total'],
            $threshold
        );
    }
    
    return $report;
}

// 生成文件级别报告
function generateFileReport(array $files, array $thresholds): string
{
    $report = "\nFile Coverage Details:\n";
    $report .= "======================\n\n";
    
    // 按覆盖率排序
    uasort($files, function ($a, $b) {
        return $b['lines']['percentage'] <=> $a['lines']['percentage'];
    });
    
    $lowCoverageFiles = [];
    
    foreach ($files as $filename => $data) {
        $linePercentage = $data['lines']['percentage'];
        $threshold = $thresholds['line'];
        
        if ($linePercentage < $threshold) {
            $lowCoverageFiles[] = [
                'file' => $filename,
                'coverage' => $linePercentage,
            ];
        }
        
        $status = $linePercentage >= $threshold ? '✅' : '❌';
        $report .= sprintf(
            "%s %s: %.2f%% (%d/%d lines)\n",
            $status,
            basename($filename),
            $linePercentage,
            $data['lines']['covered'],
            $data['lines']['total']
        );
    }
    
    if (!empty($lowCoverageFiles)) {
        $report .= "\nFiles Below Threshold:\n";
        $report .= "======================\n";
        
        foreach ($lowCoverageFiles as $file) {
            $report .= sprintf(
                "❌ %s: %.2f%%\n",
                $file['file'],
                $file['coverage']
            );
        }
    }
    
    return $report;
}

// 生成改进建议
function generateImprovementSuggestions(array $coverage, array $files, array $thresholds): string
{
    $suggestions = "\nImprovement Suggestions:\n";
    $suggestions .= "========================\n\n";
    
    // 检查整体覆盖率
    foreach ($coverage as $type => $data) {
        $threshold = $thresholds[substr($type, 0, -1)] ?? 0;
        
        if ($data['percentage'] < $threshold) {
            $needed = ceil(($threshold / 100) * $data['total']) - $data['covered'];
            $suggestions .= sprintf(
                "🎯 %s Coverage: Need to cover %d more %s to reach %d%% threshold\n",
                ucfirst($type),
                $needed,
                substr($type, 0, -1),
                $threshold
            );
        }
    }
    
    // 找出最需要改进的文件
    $lowCoverageFiles = array_filter($files, function ($data) use ($thresholds) {
        return $data['lines']['percentage'] < $thresholds['line'];
    });
    
    if (!empty($lowCoverageFiles)) {
        // 按覆盖率从低到高排序
        uasort($lowCoverageFiles, function ($a, $b) {
            return $a['lines']['percentage'] <=> $b['lines']['percentage'];
        });
        
        $suggestions .= "\n📝 Priority Files for Testing:\n";
        $count = 0;
        foreach ($lowCoverageFiles as $filename => $data) {
            if ($count >= 5) break; // 只显示前5个
            
            $suggestions .= sprintf(
                "   %d. %s (%.2f%% coverage)\n",
                $count + 1,
                basename($filename),
                $data['lines']['percentage']
            );
            $count++;
        }
    }
    
    // 生成具体建议
    $suggestions .= "\n💡 Specific Recommendations:\n";
    
    if ($coverage['lines']['percentage'] < $thresholds['line']) {
        $suggestions .= "   • Add more unit tests for core functionality\n";
        $suggestions .= "   • Focus on testing edge cases and error conditions\n";
        $suggestions .= "   • Consider integration tests for complex workflows\n";
    }
    
    if ($coverage['functions']['percentage'] < $thresholds['function']) {
        $suggestions .= "   • Ensure all public methods have test coverage\n";
        $suggestions .= "   • Add tests for private methods through public interfaces\n";
    }
    
    if ($coverage['classes']['percentage'] < $thresholds['class']) {
        $suggestions .= "   • Create test classes for uncovered classes\n";
        $suggestions .= "   • Consider if some classes need refactoring for testability\n";
    }
    
    return $suggestions;
}

// 生成 HTML 摘要
function generateHtmlSummary(array $coverage, string $htmlDir): void
{
    if (!is_dir($htmlDir)) {
        return;
    }
    
    $summaryFile = $htmlDir . '/summary.html';
    
    $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>ThinkScramble Coverage Summary</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .metric { margin: 10px 0; padding: 10px; border-radius: 5px; }
        .good { background-color: #d4edda; border: 1px solid #c3e6cb; }
        .warning { background-color: #fff3cd; border: 1px solid #ffeaa7; }
        .danger { background-color: #f8d7da; border: 1px solid #f5c6cb; }
        .percentage { font-weight: bold; font-size: 1.2em; }
    </style>
</head>
<body>
    <h1>ThinkScramble Coverage Summary</h1>
    <p>Generated: {date('Y-m-d H:i:s')}</p>
HTML;
    
    foreach ($coverage as $type => $data) {
        $class = $data['percentage'] >= 80 ? 'good' : ($data['percentage'] >= 60 ? 'warning' : 'danger');
        
        $html .= <<<HTML
    <div class="metric {$class}">
        <h3>{ucfirst($type)} Coverage</h3>
        <div class="percentage">{$data['percentage']}%</div>
        <div>{$data['covered']}/{$data['total']} covered</div>
    </div>
HTML;
    }
    
    $html .= <<<HTML
    <p><a href="index.html">View Detailed Report</a></p>
</body>
</html>
HTML;
    
    file_put_contents($summaryFile, $html);
}

// 主函数
try {
    echo "📊 Parsing coverage data...\n";
    $coverage = parseCoverageData($coverageFile);
    $files = getFileCoverage($coverageFile);
    
    echo "📋 Generating reports...\n";
    
    // 生成主报告
    $report = generateCoverageReport($coverage, $thresholds);
    echo $report;
    
    // 生成文件报告
    $fileReport = generateFileReport($files, $thresholds);
    echo $fileReport;
    
    // 生成改进建议
    $suggestions = generateImprovementSuggestions($coverage, $files, $thresholds);
    echo $suggestions;
    
    // 保存完整报告
    $fullReport = $report . $fileReport . $suggestions;
    file_put_contents('coverage/analysis-report.txt', $fullReport);
    
    // 生成 HTML 摘要
    generateHtmlSummary($coverage, $htmlDir);
    
    echo "\n📄 Reports Generated:\n";
    echo "   📊 Analysis Report: coverage/analysis-report.txt\n";
    echo "   📋 HTML Summary: coverage/html/summary.html\n";
    echo "   🌐 Detailed HTML: coverage/html/index.html\n";
    
    // 检查是否达到阈值
    $allPassed = true;
    foreach ($coverage as $type => $data) {
        $threshold = $thresholds[substr($type, 0, -1)] ?? 0;
        if ($data['percentage'] < $threshold) {
            $allPassed = false;
            break;
        }
    }
    
    if ($allPassed) {
        echo "\n✅ All coverage thresholds met!\n";
        exit(0);
    } else {
        echo "\n⚠️ Some coverage thresholds not met. See suggestions above.\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
