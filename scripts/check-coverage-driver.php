<?php

/**
 * 检查覆盖率驱动脚本
 */

declare(strict_types=1);

echo "🔍 Coverage Driver Detection\n";
echo "============================\n\n";

// 检查 PHP 版本
$phpVersion = PHP_VERSION;
echo "📋 PHP Version: {$phpVersion}\n\n";

// 检查已加载的扩展
$loadedExtensions = get_loaded_extensions();

// 检查 Xdebug
$hasXdebug = extension_loaded('xdebug');
if ($hasXdebug) {
    echo "✅ Xdebug: INSTALLED\n";
    
    // 检查 Xdebug 版本
    $xdebugVersion = phpversion('xdebug');
    echo "   Version: {$xdebugVersion}\n";
    
    // 检查 Xdebug 模式
    if (function_exists('xdebug_info')) {
        $xdebugInfo = xdebug_info();
        echo "   Mode: " . (ini_get('xdebug.mode') ?: 'default') . "\n";
    }
    
    // 检查覆盖率支持
    if (function_exists('xdebug_start_code_coverage')) {
        echo "   Coverage Support: ✅ YES\n";
    } else {
        echo "   Coverage Support: ❌ NO\n";
    }
    
    echo "\n";
} else {
    echo "❌ Xdebug: NOT INSTALLED\n\n";
}

// 检查 PCOV
$hasPcov = extension_loaded('pcov');
if ($hasPcov) {
    echo "✅ PCOV: INSTALLED\n";
    
    // 检查 PCOV 版本
    $pcovVersion = phpversion('pcov');
    echo "   Version: {$pcovVersion}\n";
    
    // 检查 PCOV 配置
    echo "   Enabled: " . (ini_get('pcov.enabled') ? 'YES' : 'NO') . "\n";
    echo "   Directory: " . (ini_get('pcov.directory') ?: 'Not set') . "\n";
    
    echo "\n";
} else {
    echo "❌ PCOV: NOT INSTALLED\n\n";
}

// 总结
echo "📊 Coverage Driver Summary:\n";
echo "===========================\n";

if ($hasXdebug || $hasPcov) {
    echo "✅ Coverage drivers available!\n\n";
    
    if ($hasXdebug && $hasPcov) {
        echo "💡 Both Xdebug and PCOV are installed.\n";
        echo "   Recommendation: Use PCOV for faster coverage (set XDEBUG_MODE=off)\n";
        echo "   Command: XDEBUG_MODE=off pest --coverage-html=coverage/html\n\n";
    } elseif ($hasXdebug) {
        echo "💡 Using Xdebug for coverage.\n";
        echo "   Command: XDEBUG_MODE=coverage pest --coverage-html=coverage/html\n\n";
    } else {
        echo "💡 Using PCOV for coverage.\n";
        echo "   Command: pest --coverage-html=coverage/html\n\n";
    }
    
    // 测试覆盖率功能
    echo "🧪 Testing coverage functionality...\n";
    
    try {
        if ($hasXdebug) {
            // 测试 Xdebug 覆盖率
            if (function_exists('xdebug_start_code_coverage')) {
                xdebug_start_code_coverage();
                
                // 执行一些代码
                $testVar = 'coverage test';
                $testArray = ['test' => true];
                
                $coverage = xdebug_get_code_coverage();
                xdebug_stop_code_coverage();
                
                if (!empty($coverage)) {
                    echo "   ✅ Xdebug coverage test: PASSED\n";
                } else {
                    echo "   ⚠️ Xdebug coverage test: No data collected\n";
                }
            }
        }
        
        if ($hasPcov) {
            // 测试 PCOV 覆盖率
            if (function_exists('pcov\\start')) {
                \pcov\start();
                
                // 执行一些代码
                $testVar = 'pcov test';
                $testArray = ['pcov' => true];
                
                $coverage = \pcov\collect();
                \pcov\stop();
                
                if (!empty($coverage)) {
                    echo "   ✅ PCOV coverage test: PASSED\n";
                } else {
                    echo "   ⚠️ PCOV coverage test: No data collected\n";
                }
            }
        }
        
    } catch (Exception $e) {
        echo "   ❌ Coverage test failed: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
    
} else {
    echo "❌ No coverage drivers found!\n\n";
    
    echo "📦 Installation Instructions:\n";
    echo "=============================\n\n";
    
    echo "🔧 Install Xdebug (Recommended for development):\n";
    echo "   macOS (Homebrew): brew install php-xdebug\n";
    echo "   Ubuntu/Debian:    sudo apt-get install php-xdebug\n";
    echo "   CentOS/RHEL:      sudo yum install php-xdebug\n";
    echo "   Windows:          Download from https://xdebug.org/download\n\n";
    
    echo "⚡ Install PCOV (Recommended for CI/CD):\n";
    echo "   PECL:             pecl install pcov\n";
    echo "   Ubuntu/Debian:    sudo apt-get install php-pcov\n";
    echo "   Compile:          https://github.com/krakjoe/pcov\n\n";
    
    echo "📝 Configuration:\n";
    echo "   Add to php.ini:\n";
    echo "   [xdebug]\n";
    echo "   zend_extension=xdebug.so\n";
    echo "   xdebug.mode=coverage\n\n";
    echo "   [pcov]\n";
    echo "   extension=pcov.so\n";
    echo "   pcov.enabled=1\n";
    echo "   pcov.directory=/path/to/src\n\n";
}

// 环境变量检查
echo "🌍 Environment Variables:\n";
echo "=========================\n";

$xdebugMode = getenv('XDEBUG_MODE');
if ($xdebugMode !== false) {
    echo "   XDEBUG_MODE: {$xdebugMode}\n";
} else {
    echo "   XDEBUG_MODE: Not set\n";
}

$pcovDirectory = getenv('PCOV_DIRECTORY');
if ($pcovDirectory !== false) {
    echo "   PCOV_DIRECTORY: {$pcovDirectory}\n";
} else {
    echo "   PCOV_DIRECTORY: Not set\n";
}

echo "\n";

// 推荐的测试命令
echo "🚀 Recommended Test Commands:\n";
echo "=============================\n";

if ($hasXdebug || $hasPcov) {
    echo "# Using our test script:\n";
    echo "./scripts/test.sh --coverage\n";
    echo "./scripts/test.sh --coverage --report\n\n";
    
    echo "# Using Composer:\n";
    echo "composer test:coverage\n";
    echo "composer coverage:report\n\n";
    
    echo "# Direct Pest commands:\n";
    if ($hasPcov) {
        echo "pest --coverage-html=coverage/html\n";
    }
    if ($hasXdebug) {
        echo "XDEBUG_MODE=coverage pest --coverage-html=coverage/html\n";
    }
    echo "pest --coverage-clover=coverage/clover.xml\n";
    echo "pest --coverage --min=80\n\n";
} else {
    echo "❌ Install a coverage driver first!\n\n";
}

// 性能建议
if ($hasXdebug && $hasPcov) {
    echo "⚡ Performance Tips:\n";
    echo "===================\n";
    echo "• Use PCOV for faster coverage in CI/CD\n";
    echo "• Use Xdebug for debugging and detailed analysis\n";
    echo "• Disable Xdebug when not needed: XDEBUG_MODE=off\n";
    echo "• Use parallel testing: pest --parallel --coverage\n\n";
}

// 文件检查
echo "📁 Configuration Files:\n";
echo "=======================\n";

$configFiles = [
    'phpunit.xml' => 'PHPUnit configuration',
    'pest.xml' => 'Pest configuration', 
    'composer.json' => 'Composer scripts',
];

foreach ($configFiles as $file => $description) {
    if (file_exists($file)) {
        echo "   ✅ {$file}: {$description}\n";
    } else {
        echo "   ❌ {$file}: Missing\n";
    }
}

echo "\n";

// 最终状态
if ($hasXdebug || $hasPcov) {
    echo "🎉 Ready for coverage testing!\n";
    echo "Run: ./scripts/test.sh --coverage\n";
    exit(0);
} else {
    echo "⚠️ Coverage drivers needed for coverage testing.\n";
    echo "Install Xdebug or PCOV to enable coverage reports.\n";
    exit(1);
}
