<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses(Yangweijie\ThinkScramble\Tests\TestCase::class)->in('Unit', 'Integration', 'Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

expect()->extend('toHaveValidOpenApiStructure', function () {
    $document = $this->value;
    
    expect($document)->toBeArray()
        ->and($document)->toHaveKeys(['openapi', 'info', 'paths'])
        ->and($document['openapi'])->toMatch('/^3\.\d+\.\d+$/')
        ->and($document['info'])->toHaveKeys(['title', 'version'])
        ->and($document['paths'])->toBeArray();
    
    return $this;
});

expect()->extend('toHaveValidPathInfo', function () {
    $pathInfo = $this->value;
    
    expect($pathInfo)->toBeArray();
    
    $httpMethods = ['get', 'post', 'put', 'patch', 'delete', 'options', 'head'];
    $hasMethod = false;
    
    foreach ($httpMethods as $method) {
        if (isset($pathInfo[$method])) {
            $hasMethod = true;
            expect($pathInfo[$method])->toBeArray();
        }
    }
    
    expect($hasMethod)->toBeTrue('Path should have at least one HTTP method');
    
    return $this;
});

expect()->extend('toHavePerformanceWithin', function (float $maxTime, string $operation = 'operation') {
    $actualTime = $this->value;
    
    expect($actualTime)->toBeLessThanOrEqual(
        $maxTime,
        "Performance test failed: {$operation} took {$actualTime}ms, expected <= {$maxTime}ms"
    );
    
    return $this;
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the amount of code duplication.
|
*/

function createTestController(string $className, array $methods = []): string
{
    $methodsCode = '';
    foreach ($methods as $method) {
        $params = $method['params'] ?? '';
        $body = $method['body'] ?? 'return [];';
        $methodsCode .= "
    public function {$method['name']}({$params})
    {
        {$body}
    }
";
    }

    $content = "<?php

namespace app\\controller;

use think\\Controller;

class {$className} extends Controller
{
{$methodsCode}
}";

    $testDataPath = __DIR__ . '/data';
    if (!is_dir($testDataPath . '/controllers')) {
        mkdir($testDataPath . '/controllers', 0755, true);
    }
    
    $filepath = $testDataPath . "/controllers/{$className}.php";
    file_put_contents($filepath, $content);
    return $filepath;
}

function createTestModel(string $className, array $properties = []): string
{
    $propertiesCode = '';
    foreach ($properties as $property) {
        $propertiesCode .= "
    protected \${$property['name']};
";
    }

    $content = "<?php

namespace app\\model;

use think\\Model;

class {$className} extends Model
{
{$propertiesCode}
}";

    $testDataPath = __DIR__ . '/data';
    if (!is_dir($testDataPath . '/models')) {
        mkdir($testDataPath . '/models', 0755, true);
    }
    
    $filepath = $testDataPath . "/models/{$className}.php";
    file_put_contents($filepath, $content);
    return $filepath;
}

function createTestFile(string $filename, string $content): string
{
    $testDataPath = __DIR__ . '/data';
    if (!is_dir($testDataPath)) {
        mkdir($testDataPath, 0755, true);
    }
    
    $filepath = $testDataPath . '/' . $filename;
    file_put_contents($filepath, $content);
    return $filepath;
}

function cleanupTestFiles(): void
{
    $testDataPath = __DIR__ . '/data';
    $testFiles = glob($testDataPath . '/test_*');
    foreach ($testFiles as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
}

/*
|--------------------------------------------------------------------------
| Coverage Configuration
|--------------------------------------------------------------------------
|
| Configure coverage settings and thresholds for the test suite.
|
*/

/**
 * 获取覆盖率阈值配置
 */
function getCoverageThresholds(): array
{
    return [
        'line' => 80,
        'function' => 80,
        'class' => 80,
        'method' => 80,
        'branch' => 70,
    ];
}

/**
 * 获取覆盖率排除文件列表
 */
function getCoverageExcludes(): array
{
    return [
        'src/Exception/*',
        'src/Service/ScrambleServiceProvider.php',
        'src/Console/Commands/*',
        'tests/*',
        'vendor/*',
    ];
}

/**
 * 验证覆盖率是否达到阈值
 */
function validateCoverage(array $coverage): bool
{
    $thresholds = getCoverageThresholds();

    foreach ($thresholds as $metric => $threshold) {
        if (isset($coverage[$metric]) && $coverage[$metric] < $threshold) {
            return false;
        }
    }

    return true;
}

/**
 * 生成覆盖率报告摘要
 */
function generateCoverageSummary(array $coverage): string
{
    $summary = "Coverage Summary:\n";
    $summary .= "================\n";

    foreach ($coverage as $metric => $percentage) {
        $status = $percentage >= (getCoverageThresholds()[$metric] ?? 0) ? '✅' : '❌';
        $summary .= sprintf("%s %s: %.2f%%\n", $status, ucfirst($metric), $percentage);
    }

    return $summary;
}
