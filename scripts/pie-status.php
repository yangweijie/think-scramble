<?php

/**
 * PIE 状态检查脚本
 */

declare(strict_types=1);

echo "📊 ThinkScramble Installation Status\n";
echo "====================================\n\n";

// 检查操作系统
$os = PHP_OS_FAMILY;
echo "🖥️ Operating System: {$os}\n";
echo "🐘 PHP Version: " . PHP_VERSION . "\n\n";

// 检查安装路径
$installPaths = [
    'Linux' => '/usr/local/bin',
    'Darwin' => '/usr/local/bin',
    'Windows' => getenv('USERPROFILE') . '\\bin',
];

$installPath = $installPaths[$os] ?? '/usr/local/bin';
$binaryName = $os === 'Windows' ? 'scramble.bat' : 'scramble';
$targetFile = $installPath . DIRECTORY_SEPARATOR . $binaryName;

echo "📁 Expected install path: {$installPath}\n";
echo "📄 Binary name: {$binaryName}\n\n";

// 检查主要安装
$isInstalled = false;
$installedPath = null;

if (file_exists($targetFile)) {
    $isInstalled = true;
    $installedPath = $targetFile;
    echo "✅ ThinkScramble is installed: {$targetFile}\n";
} else {
    echo "❌ ThinkScramble not found in expected location\n";
    
    // 搜索其他可能的位置
    $searchPaths = [
        '/usr/bin/scramble',
        '/usr/local/bin/scramble',
        getenv('HOME') . '/.local/bin/scramble',
        '/opt/homebrew/bin/scramble',
    ];
    
    echo "🔍 Searching in other locations...\n";
    foreach ($searchPaths as $path) {
        if (file_exists($path)) {
            $isInstalled = true;
            $installedPath = $path;
            echo "✅ Found installation: {$path}\n";
            break;
        }
    }
    
    if (!$isInstalled) {
        echo "❌ ThinkScramble not found in any common locations\n";
    }
}

echo "\n";

// 如果找到安装，获取详细信息
if ($isInstalled && $installedPath) {
    echo "📋 Installation Details:\n";
    echo "   Path: {$installedPath}\n";
    echo "   Size: " . formatBytes(filesize($installedPath)) . "\n";
    echo "   Modified: " . date('Y-m-d H:i:s', filemtime($installedPath)) . "\n";
    echo "   Permissions: " . substr(sprintf('%o', fileperms($installedPath)), -4) . "\n";
    
    // 检查是否可执行
    if (is_executable($installedPath)) {
        echo "   Executable: ✅ Yes\n";
    } else {
        echo "   Executable: ❌ No\n";
    }
    
    echo "\n";
    
    // 获取版本信息
    echo "🏷️ Version Information:\n";
    $output = [];
    $returnCode = 0;
    exec("{$installedPath} --version 2>/dev/null", $output, $returnCode);
    
    if ($returnCode === 0) {
        foreach ($output as $line) {
            echo "   {$line}\n";
        }
    } else {
        echo "   ❌ Failed to get version information\n";
    }
    
    echo "\n";
    
    // 测试基本功能
    echo "🧪 Functionality Test:\n";
    $output = [];
    $returnCode = 0;
    exec("{$installedPath} --help 2>/dev/null", $output, $returnCode);
    
    if ($returnCode === 0) {
        echo "   ✅ Help command works\n";
    } else {
        echo "   ❌ Help command failed\n";
    }
    
    // 检查依赖文件 (Windows)
    if ($os === 'Windows') {
        $pharFile = dirname($installedPath) . DIRECTORY_SEPARATOR . 'scramble.phar';
        if (file_exists($pharFile)) {
            echo "   ✅ PHAR file found: {$pharFile}\n";
            echo "   PHAR size: " . formatBytes(filesize($pharFile)) . "\n";
        } else {
            echo "   ⚠️ PHAR file not found: {$pharFile}\n";
        }
    }
}

echo "\n";

// 检查缓存目录
echo "💾 Cache Information:\n";
$cacheDir = sys_get_temp_dir() . '/think-scramble-cache';

if (is_dir($cacheDir)) {
    echo "   Cache directory: {$cacheDir}\n";
    
    $files = glob($cacheDir . '/*');
    $totalSize = 0;
    
    foreach ($files as $file) {
        if (is_file($file)) {
            $totalSize += filesize($file);
        }
    }
    
    echo "   Cache files: " . count($files) . "\n";
    echo "   Cache size: " . formatBytes($totalSize) . "\n";
    echo "   Last modified: " . (count($files) > 0 ? date('Y-m-d H:i:s', max(array_map('filemtime', $files))) : 'N/A') . "\n";
} else {
    echo "   ❌ Cache directory not found\n";
}

echo "\n";

// 检查 PATH 环境变量
echo "🛤️ PATH Information:\n";
$pathEnv = getenv('PATH');
$pathDirs = explode(PATH_SEPARATOR, $pathEnv);

$inPath = false;
foreach ($pathDirs as $dir) {
    if (realpath($dir) === realpath($installPath)) {
        $inPath = true;
        break;
    }
}

if ($inPath) {
    echo "   ✅ Install directory is in PATH\n";
} else {
    echo "   ⚠️ Install directory not in PATH\n";
    echo "   💡 Add {$installPath} to your PATH for global access\n";
}

echo "\n";

// 系统要求检查
echo "⚙️ System Requirements:\n";

// PHP 版本
if (version_compare(PHP_VERSION, '8.0.0', '>=')) {
    echo "   ✅ PHP version: " . PHP_VERSION . " (>= 8.0.0)\n";
} else {
    echo "   ❌ PHP version: " . PHP_VERSION . " (< 8.0.0 required)\n";
}

// 必需扩展
$requiredExtensions = ['json', 'mbstring'];
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        echo "   ✅ Extension {$ext}: loaded\n";
    } else {
        echo "   ❌ Extension {$ext}: not loaded\n";
    }
}

// 可选扩展
$optionalExtensions = ['yaml', 'zip'];
foreach ($optionalExtensions as $ext) {
    if (extension_loaded($ext)) {
        echo "   ✅ Extension {$ext}: loaded (optional)\n";
    } else {
        echo "   ⚠️ Extension {$ext}: not loaded (optional)\n";
    }
}

echo "\n";

// 总结
echo "📝 Summary:\n";
if ($isInstalled) {
    echo "   Status: ✅ Installed and functional\n";
    echo "   Location: {$installedPath}\n";
    echo "   Ready to use: " . ($inPath ? "✅ Yes" : "⚠️ Add to PATH") . "\n";
} else {
    echo "   Status: ❌ Not installed\n";
    echo "   Action: Install with 'pie install yangweijie/think-scramble'\n";
}

echo "\n";
echo "📚 Documentation: https://github.com/yangweijie/think-scramble\n";
echo "🐛 Issues: https://github.com/yangweijie/think-scramble/issues\n";

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
