<?php

/**
 * PIE 卸载前脚本
 */

declare(strict_types=1);

echo "🗑️ ThinkScramble Pre-Uninstall Cleanup\n";
echo "======================================\n\n";

// 检查操作系统
$os = PHP_OS_FAMILY;

// 询问用户是否保留配置和缓存
echo "❓ Do you want to keep your configuration and cache files? (y/N): ";
$handle = fopen("php://stdin", "r");
$keepFiles = trim(fgets($handle));
fclose($handle);

$keepFiles = strtolower($keepFiles) === 'y' || strtolower($keepFiles) === 'yes';

if (!$keepFiles) {
    echo "🧹 Cleaning up configuration and cache files...\n";
    
    // 清理配置目录
    $configDir = getenv('HOME') . '/.think-scramble';
    if ($os === 'Windows') {
        $configDir = getenv('USERPROFILE') . '\\.think-scramble';
    }
    
    if (is_dir($configDir)) {
        echo "📁 Removing config directory: {$configDir}\n";
        
        // 递归删除目录
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($configDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }
        
        if (rmdir($configDir)) {
            echo "✅ Config directory removed\n";
        } else {
            echo "⚠️ Failed to remove config directory\n";
        }
    }
    
    // 清理缓存目录
    $cacheDir = sys_get_temp_dir() . '/think-scramble-cache';
    if (is_dir($cacheDir)) {
        echo "💾 Removing cache directory: {$cacheDir}\n";
        
        $files = glob($cacheDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        
        if (rmdir($cacheDir)) {
            echo "✅ Cache directory removed\n";
        } else {
            echo "⚠️ Failed to remove cache directory\n";
        }
    }
    
    // 清理 shell 补全
    $shells = [
        'bash' => getenv('HOME') . '/.bashrc',
        'zsh' => getenv('HOME') . '/.zshrc',
    ];
    
    foreach ($shells as $shell => $file) {
        if (file_exists($file)) {
            $content = file_get_contents($file);
            
            // 移除 ThinkScramble 相关的行
            $lines = explode("\n", $content);
            $newLines = [];
            $skipNext = false;
            
            foreach ($lines as $line) {
                if (strpos($line, '# ThinkScramble completion') !== false) {
                    $skipNext = true;
                    continue;
                }
                
                if ($skipNext && (strpos($line, 'scramble') !== false || strpos($line, 'compdef') !== false)) {
                    $skipNext = false;
                    continue;
                }
                
                $newLines[] = $line;
            }
            
            if (count($newLines) !== count($lines)) {
                file_put_contents($file, implode("\n", $newLines));
                echo "🐚 Removed {$shell} completion from {$file}\n";
            }
        }
    }
} else {
    echo "💾 Keeping configuration and cache files\n";
    
    // 显示保留的文件位置
    $configDir = getenv('HOME') . '/.think-scramble';
    if ($os === 'Windows') {
        $configDir = getenv('USERPROFILE') . '\\.think-scramble';
    }
    
    if (is_dir($configDir)) {
        echo "📁 Config files preserved in: {$configDir}\n";
    }
    
    $cacheDir = sys_get_temp_dir() . '/think-scramble-cache';
    if (is_dir($cacheDir)) {
        echo "💾 Cache files preserved in: {$cacheDir}\n";
    }
}

// 显示统计信息
echo "\n📊 Usage Statistics (if available):\n";

$statsFile = sys_get_temp_dir() . '/think-scramble-stats.json';
if (file_exists($statsFile)) {
    $stats = json_decode(file_get_contents($statsFile), true);
    
    if ($stats) {
        echo "   Total runs: " . ($stats['total_runs'] ?? 0) . "\n";
        echo "   Documents generated: " . ($stats['documents_generated'] ?? 0) . "\n";
        echo "   Last used: " . ($stats['last_used'] ?? 'Unknown') . "\n";
        echo "   Most used format: " . ($stats['most_used_format'] ?? 'Unknown') . "\n";
    }
    
    if (!$keepFiles) {
        unlink($statsFile);
        echo "📊 Statistics file removed\n";
    }
} else {
    echo "   No usage statistics available\n";
}

// 感谢信息
echo "\n💝 Thank You!\n";
echo "=============\n";
echo "Thank you for using ThinkScramble! We hope it helped you create better API documentation.\n\n";

echo "📝 We'd love your feedback:\n";
echo "   🌟 Star us on GitHub: https://github.com/yangweijie/think-scramble\n";
echo "   🐛 Report issues: https://github.com/yangweijie/think-scramble/issues\n";
echo "   💬 Join discussions: https://github.com/yangweijie/think-scramble/discussions\n";
echo "   📧 Contact us: yangweijie@example.com\n\n";

echo "🔄 Reinstall anytime with: pie install yangweijie/think-scramble\n\n";

echo "✨ Happy coding! ✨\n";
