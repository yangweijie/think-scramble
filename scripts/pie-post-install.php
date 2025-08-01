<?php

/**
 * PIE 安装后脚本
 */

declare(strict_types=1);

echo "🎉 ThinkScramble Post-Installation Setup\n";
echo "========================================\n\n";

// 检查操作系统
$os = PHP_OS_FAMILY;

// 创建配置目录
$configDir = getenv('HOME') . '/.think-scramble';
if ($os === 'Windows') {
    $configDir = getenv('USERPROFILE') . '\\.think-scramble';
}

if (!is_dir($configDir)) {
    if (mkdir($configDir, 0755, true)) {
        echo "📁 Created config directory: {$configDir}\n";
    } else {
        echo "⚠️ Failed to create config directory: {$configDir}\n";
    }
}

// 创建默认配置文件
$configFile = $configDir . DIRECTORY_SEPARATOR . 'config.php';
if (!file_exists($configFile)) {
    $defaultConfig = <<<'PHP'
<?php

/**
 * ThinkScramble 默认配置
 */

return [
    'info' => [
        'title' => 'My API',
        'version' => '1.0.0',
        'description' => 'API documentation generated by ThinkScramble',
    ],
    'servers' => [
        [
            'url' => 'http://localhost:8000',
            'description' => 'Development server',
        ],
    ],
    'security' => [
        'enabled_schemes' => [
            'BearerAuth',
            'ApiKeyAuth',
        ],
    ],
    'cache' => [
        'driver' => 'file',
        'file' => [
            'path' => sys_get_temp_dir() . '/think-scramble-cache',
        ],
    ],
    'export' => [
        'formats' => [
            'json',
            'yaml',
            'postman',
            'insomnia',
        ],
    ],
    'performance' => [
        'monitor' => true,
        'cache_ttl' => 3600,
    ],
];
PHP;

    if (file_put_contents($configFile, $defaultConfig)) {
        echo "📄 Created default config: {$configFile}\n";
    } else {
        echo "⚠️ Failed to create config file: {$configFile}\n";
    }
}

// 创建缓存目录
$cacheDir = sys_get_temp_dir() . '/think-scramble-cache';
if (!is_dir($cacheDir)) {
    if (mkdir($cacheDir, 0755, true)) {
        echo "💾 Created cache directory: {$cacheDir}\n";
    } else {
        echo "⚠️ Failed to create cache directory: {$cacheDir}\n";
    }
}

// 创建示例项目配置
$exampleConfig = $configDir . DIRECTORY_SEPARATOR . 'example-project.php';
if (!file_exists($exampleConfig)) {
    $exampleContent = <<<'PHP'
<?php

/**
 * ThinkScramble 项目配置示例
 * 
 * 复制此文件到你的 ThinkPHP 项目根目录，重命名为 scramble.php
 */

return [
    'info' => [
        'title' => 'My ThinkPHP API',
        'version' => '1.0.0',
        'description' => 'A comprehensive API for my ThinkPHP application',
        'contact' => [
            'name' => 'API Support',
            'email' => 'support@example.com',
            'url' => 'https://example.com/support',
        ],
        'license' => [
            'name' => 'MIT',
            'url' => 'https://opensource.org/licenses/MIT',
        ],
    ],
    
    'servers' => [
        [
            'url' => 'http://localhost:8000',
            'description' => 'Development server',
        ],
        [
            'url' => 'https://api.example.com',
            'description' => 'Production server',
        ],
    ],
    
    'paths' => [
        'controllers' => 'app/controller',
        'models' => 'app/model',
        'validate' => 'app/validate',
    ],
    
    'security' => [
        'enabled_schemes' => [
            'BearerAuth',
            'ApiKeyAuth',
            'SessionAuth',
        ],
        'global_security' => [
            ['BearerAuth' => []],
        ],
    ],
    
    'cache' => [
        'driver' => 'file',
        'file' => [
            'path' => './runtime/scramble-cache',
        ],
        'ttl' => 3600,
    ],
    
    'export' => [
        'default_format' => 'json',
        'formats' => [
            'json',
            'yaml', 
            'postman',
            'insomnia',
        ],
    ],
    
    'performance' => [
        'monitor' => true,
        'cache_analysis' => true,
        'optimize_autoloader' => true,
    ],
    
    'plugins' => [
        'enabled' => [],
        'directories' => [
            './plugins',
        ],
    ],
];
PHP;

    if (file_put_contents($exampleConfig, $exampleContent)) {
        echo "📋 Created example config: {$exampleConfig}\n";
    }
}

// 检查 shell 配置文件并添加补全
$shells = [
    'bash' => [
        'file' => getenv('HOME') . '/.bashrc',
        'completion' => 'complete -W "--help --version --output --config --format --controllers --models --middleware --validate --stats --watch" scramble',
    ],
    'zsh' => [
        'file' => getenv('HOME') . '/.zshrc', 
        'completion' => 'compdef _gnu_generic scramble',
    ],
];

foreach ($shells as $shell => $config) {
    if (file_exists($config['file'])) {
        $content = file_get_contents($config['file']);
        
        if (strpos($content, 'scramble') === false) {
            echo "🐚 Adding {$shell} completion to {$config['file']}\n";
            
            $addition = "\n# ThinkScramble completion\n" . $config['completion'] . "\n";
            file_put_contents($config['file'], $addition, FILE_APPEND);
        }
    }
}

// 显示快速开始指南
echo "\n🚀 Quick Start Guide:\n";
echo "====================\n\n";

echo "1. 📁 Navigate to your ThinkPHP project:\n";
echo "   cd /path/to/your/thinkphp/project\n\n";

echo "2. 📄 Copy example config (optional):\n";
echo "   cp {$exampleConfig} ./scramble.php\n\n";

echo "3. 🎯 Generate API documentation:\n";
echo "   scramble --output=api.json\n\n";

echo "4. 🛡️ Include middleware analysis:\n";
echo "   scramble --output=api.json --middleware\n\n";

echo "5. 📊 Export to different formats:\n";
echo "   scramble --format=postman --output=api.postman.json\n";
echo "   scramble --format=insomnia --output=api.insomnia.json\n\n";

echo "6. 👀 Watch for file changes:\n";
echo "   scramble --watch --output=api.json\n\n";

echo "7. 📈 View statistics:\n";
echo "   scramble --stats\n\n";

// 显示有用的链接
echo "📚 Resources:\n";
echo "=============\n";
echo "📖 Documentation: https://github.com/yangweijie/think-scramble\n";
echo "🎯 Examples: https://github.com/yangweijie/think-scramble/tree/main/example\n";
echo "🐛 Issues: https://github.com/yangweijie/think-scramble/issues\n";
echo "💬 Discussions: https://github.com/yangweijie/think-scramble/discussions\n\n";

// 检查更新提醒
echo "💡 Tips:\n";
echo "========\n";
echo "• Run 'pie status yangweijie/think-scramble' to check installation status\n";
echo "• Run 'pie update yangweijie/think-scramble' to update to the latest version\n";
echo "• Use 'scramble --help' to see all available options\n";
echo "• Create a 'scramble.php' config file in your project for custom settings\n\n";

echo "🎉 Installation complete! Happy documenting! 🎉\n";
