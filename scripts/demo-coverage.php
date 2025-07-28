<?php

/**
 * Pest 覆盖率功能演示脚本
 * 
 * 这个脚本演示了如何在有覆盖率驱动的情况下使用 Pest 的覆盖率功能
 */

declare(strict_types=1);

echo "🎭 Pest Coverage Demo\n";
echo "====================\n\n";

// 检查覆盖率驱动
$hasXdebug = extension_loaded('xdebug');
$hasPcov = extension_loaded('pcov');

if (!$hasXdebug && !$hasPcov) {
    echo "❌ No coverage driver found!\n";
    echo "This demo requires Xdebug or PCOV to be installed.\n\n";
    
    echo "📦 Quick Installation:\n";
    echo "   macOS: brew install php-xdebug\n";
    echo "   Ubuntu: sudo apt-get install php-xdebug\n";
    echo "   PECL: pecl install pcov\n\n";
    
    echo "🔧 After installation, run:\n";
    echo "   php scripts/demo-coverage.php\n";
    exit(1);
}

echo "✅ Coverage driver available!\n";
if ($hasXdebug) {
    echo "   📊 Xdebug: " . phpversion('xdebug') . "\n";
}
if ($hasPcov) {
    echo "   ⚡ PCOV: " . phpversion('pcov') . "\n";
}
echo "\n";

// 演示 Pest 覆盖率命令
echo "🚀 Pest Coverage Commands Demo:\n";
echo "================================\n\n";

$commands = [
    "Basic Coverage" => [
        "command" => "vendor/bin/pest --coverage-text",
        "description" => "Generate text coverage summary",
    ],
    "HTML Report" => [
        "command" => "vendor/bin/pest --coverage-html=coverage/html",
        "description" => "Generate HTML coverage report",
    ],
    "Clover XML" => [
        "command" => "vendor/bin/pest --coverage-clover=coverage/clover.xml",
        "description" => "Generate Clover XML for CI/CD",
    ],
    "Combined Reports" => [
        "command" => "vendor/bin/pest --coverage-html=coverage/html --coverage-clover=coverage/clover.xml",
        "description" => "Generate multiple report formats",
    ],
    "With Threshold" => [
        "command" => "vendor/bin/pest --coverage --min=80",
        "description" => "Require minimum 80% coverage",
    ],
    "Specific Suite" => [
        "command" => "vendor/bin/pest --testsuite=Unit --coverage-html=coverage/html",
        "description" => "Coverage for specific test suite",
    ],
    "Parallel Coverage" => [
        "command" => "vendor/bin/pest --parallel --coverage-html=coverage/html",
        "description" => "Parallel execution with coverage",
    ],
];

foreach ($commands as $name => $info) {
    echo "📋 {$name}:\n";
    echo "   Command: {$info['command']}\n";
    echo "   Purpose: {$info['description']}\n\n";
}

// 演示配置选项
echo "⚙️ Configuration Options:\n";
echo "=========================\n\n";

echo "📄 In phpunit.xml/pest.xml:\n";
echo "```xml\n";
echo "<coverage includeUncoveredFiles=\"true\">\n";
echo "    <include>\n";
echo "        <directory suffix=\".php\">src</directory>\n";
echo "    </include>\n";
echo "    <exclude>\n";
echo "        <directory>src/Exception</directory>\n";
echo "    </exclude>\n";
echo "    <report>\n";
echo "        <html outputDirectory=\"coverage/html\"/>\n";
echo "        <clover outputFile=\"coverage/clover.xml\"/>\n";
echo "    </report>\n";
echo "</coverage>\n";
echo "```\n\n";

echo "🌍 Environment Variables:\n";
if ($hasXdebug) {
    echo "   export XDEBUG_MODE=coverage\n";
}
if ($hasPcov) {
    echo "   export PCOV_DIRECTORY=/path/to/src\n";
}
echo "\n";

// 演示实际运行
echo "🧪 Running Demo Test with Coverage:\n";
echo "===================================\n\n";

// 创建临时测试文件
$tempTestFile = __DIR__ . '/../tests/temp_demo_test.php';
$tempTestContent = '<?php

test("demo coverage test", function () {
    $calculator = new class {
        public function add(int $a, int $b): int {
            return $a + $b;
        }
        
        public function multiply(int $a, int $b): int {
            return $a * $b;
        }
        
        public function divide(int $a, int $b): int {
            if ($b === 0) {
                throw new InvalidArgumentException("Division by zero");
            }
            return intval($a / $b);
        }
    };
    
    // 测试加法 (会被覆盖)
    expect($calculator->add(2, 3))->toBe(5);
    
    // 测试乘法 (会被覆盖)
    expect($calculator->multiply(4, 5))->toBe(20);
    
    // 除法的正常情况 (会被覆盖)
    expect($calculator->divide(10, 2))->toBe(5);
    
    // 注意：除法的异常情况没有测试，所以不会被覆盖
});
';

file_put_contents($tempTestFile, $tempTestContent);

echo "📝 Created demo test file: tests/temp_demo_test.php\n";
echo "🔍 This test covers some code paths but not all (to demonstrate coverage gaps)\n\n";

// 运行演示测试
echo "▶️ Running: vendor/bin/pest tests/temp_demo_test.php --coverage-text\n";
echo "─────────────────────────────────────────────────────────────────\n";

$output = [];
$returnCode = 0;

// 设置环境变量
if ($hasXdebug) {
    putenv('XDEBUG_MODE=coverage');
}

exec('cd ' . dirname(__DIR__) . ' && vendor/bin/pest tests/temp_demo_test.php --coverage-text 2>&1', $output, $returnCode);

foreach ($output as $line) {
    echo $line . "\n";
}

echo "─────────────────────────────────────────────────────────────────\n\n";

if ($returnCode === 0) {
    echo "✅ Demo test completed successfully!\n\n";
    
    // 检查是否生成了覆盖率报告
    if (file_exists(dirname(__DIR__) . '/coverage/html/index.html')) {
        echo "📊 HTML coverage report generated!\n";
        echo "   Open: coverage/html/index.html\n\n";
    }
    
    if (file_exists(dirname(__DIR__) . '/coverage/clover.xml')) {
        echo "📄 Clover XML report generated!\n";
        echo "   File: coverage/clover.xml\n\n";
    }
    
} else {
    echo "⚠️ Demo test had issues (this is normal without coverage drivers)\n\n";
}

// 清理临时文件
unlink($tempTestFile);
echo "🧹 Cleaned up demo test file\n\n";

// 总结
echo "📚 Summary:\n";
echo "===========\n\n";

echo "✅ Pest FULLY supports code coverage!\n";
echo "✅ All PHPUnit coverage features are available\n";
echo "✅ Multiple report formats supported\n";
echo "✅ Configurable thresholds and filters\n";
echo "✅ CI/CD integration ready\n\n";

echo "🎯 Key Points:\n";
echo "• Pest uses PHPUnit's coverage engine\n";
echo "• Requires Xdebug or PCOV extension\n";
echo "• Supports HTML, XML, Clover, and text reports\n";
echo "• Can set minimum coverage thresholds\n";
echo "• Works with parallel test execution\n";
echo "• Fully configurable via phpunit.xml\n\n";

echo "🚀 Next Steps:\n";
echo "1. Install coverage driver: brew install php-xdebug\n";
echo "2. Run: ./scripts/test.sh --coverage\n";
echo "3. View: open coverage/html/index.html\n";
echo "4. Analyze: composer coverage:analyse\n\n";

echo "🎉 Happy testing with Pest coverage!\n";
