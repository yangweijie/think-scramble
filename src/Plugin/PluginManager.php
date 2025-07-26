<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Plugin;

use Yangweijie\ThinkScramble\Contracts\ConfigInterface;

/**
 * 插件管理器
 */
class PluginManager
{
    /**
     * 配置接口
     */
    protected ConfigInterface $config;

    /**
     * 钩子管理器
     */
    protected HookManager $hookManager;

    /**
     * 已加载的插件
     */
    protected array $plugins = [];

    /**
     * 插件目录
     */
    protected array $pluginDirectories = [];

    /**
     * 构造函数
     */
    public function __construct(ConfigInterface $config, HookManager $hookManager)
    {
        $this->config = $config;
        $this->hookManager = $hookManager;
        
        // 添加默认插件目录
        $this->addPluginDirectory(__DIR__ . '/../../plugins');
    }

    /**
     * 添加插件目录
     */
    public function addPluginDirectory(string $directory): void
    {
        if (is_dir($directory)) {
            $this->pluginDirectories[] = realpath($directory);
        }
    }

    /**
     * 发现插件
     */
    public function discoverPlugins(): array
    {
        $plugins = [];

        foreach ($this->pluginDirectories as $directory) {
            $plugins = array_merge($plugins, $this->scanPluginDirectory($directory));
        }

        return $plugins;
    }

    /**
     * 扫描插件目录
     */
    protected function scanPluginDirectory(string $directory): array
    {
        $plugins = [];
        
        if (!is_dir($directory)) {
            return $plugins;
        }

        $iterator = new \DirectoryIterator($directory);
        
        foreach ($iterator as $item) {
            if ($item->isDot() || !$item->isDir()) {
                continue;
            }

            $pluginPath = $item->getPathname();
            $pluginFile = $pluginPath . '/plugin.php';
            
            if (file_exists($pluginFile)) {
                $pluginInfo = include $pluginFile;
                
                if (is_array($pluginInfo) && isset($pluginInfo['class'])) {
                    $plugins[] = [
                        'name' => $item->getFilename(),
                        'path' => $pluginPath,
                        'info' => $pluginInfo,
                    ];
                }
            }
        }

        return $plugins;
    }

    /**
     * 加载插件
     */
    public function loadPlugin(string $pluginName): bool
    {
        if (isset($this->plugins[$pluginName])) {
            return true; // 已加载
        }

        $pluginInfo = $this->findPlugin($pluginName);
        
        if (!$pluginInfo) {
            return false;
        }

        try {
            // 加载插件类文件
            $classFile = $pluginInfo['path'] . '/' . $pluginInfo['info']['file'];
            
            if (file_exists($classFile)) {
                require_once $classFile;
            }

            $className = $pluginInfo['info']['class'];
            
            if (!class_exists($className)) {
                throw new \Exception("Plugin class {$className} not found");
            }

            $plugin = new $className();
            
            if (!$plugin instanceof PluginInterface) {
                throw new \Exception("Plugin must implement PluginInterface");
            }

            // 检查依赖
            $this->checkDependencies($plugin);

            // 初始化插件
            $plugin->initialize($this->config);

            // 注册钩子
            $plugin->registerHooks($this->hookManager);

            $this->plugins[$pluginName] = $plugin;

            return true;

        } catch (\Exception $e) {
            error_log("Failed to load plugin {$pluginName}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 卸载插件
     */
    public function unloadPlugin(string $pluginName): bool
    {
        if (!isset($this->plugins[$pluginName])) {
            return false;
        }

        try {
            $plugin = $this->plugins[$pluginName];
            $plugin->uninstall();
            
            unset($this->plugins[$pluginName]);
            
            return true;

        } catch (\Exception $e) {
            error_log("Failed to unload plugin {$pluginName}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 获取已加载的插件
     */
    public function getLoadedPlugins(): array
    {
        return $this->plugins;
    }

    /**
     * 获取插件信息
     */
    public function getPluginInfo(string $pluginName): ?array
    {
        if (!isset($this->plugins[$pluginName])) {
            return null;
        }

        $plugin = $this->plugins[$pluginName];
        
        return [
            'name' => $plugin->getName(),
            'version' => $plugin->getVersion(),
            'description' => $plugin->getDescription(),
            'author' => $plugin->getAuthor(),
            'enabled' => $plugin->isEnabled(),
            'config' => $plugin->getConfig(),
            'dependencies' => $plugin->getDependencies(),
        ];
    }

    /**
     * 启用插件
     */
    public function enablePlugin(string $pluginName): bool
    {
        if (!isset($this->plugins[$pluginName])) {
            return false;
        }

        try {
            $this->plugins[$pluginName]->enable();
            return true;
        } catch (\Exception $e) {
            error_log("Failed to enable plugin {$pluginName}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 禁用插件
     */
    public function disablePlugin(string $pluginName): bool
    {
        if (!isset($this->plugins[$pluginName])) {
            return false;
        }

        try {
            $this->plugins[$pluginName]->disable();
            return true;
        } catch (\Exception $e) {
            error_log("Failed to disable plugin {$pluginName}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 配置插件
     */
    public function configurePlugin(string $pluginName, array $config): bool
    {
        if (!isset($this->plugins[$pluginName])) {
            return false;
        }

        try {
            $this->plugins[$pluginName]->setConfig($config);
            return true;
        } catch (\Exception $e) {
            error_log("Failed to configure plugin {$pluginName}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 查找插件
     */
    protected function findPlugin(string $pluginName): ?array
    {
        $discoveredPlugins = $this->discoverPlugins();
        
        foreach ($discoveredPlugins as $plugin) {
            if ($plugin['name'] === $pluginName) {
                return $plugin;
            }
        }

        return null;
    }

    /**
     * 检查插件依赖
     */
    protected function checkDependencies(PluginInterface $plugin): void
    {
        $dependencies = $plugin->getDependencies();
        
        foreach ($dependencies as $dependency) {
            if (!isset($this->plugins[$dependency])) {
                throw new \Exception("Plugin dependency not found: {$dependency}");
            }
        }
    }

    /**
     * 加载所有启用的插件
     */
    public function loadEnabledPlugins(): array
    {
        $enabledPlugins = $this->config->get('plugins.enabled', []);
        $results = [];

        foreach ($enabledPlugins as $pluginName) {
            $results[$pluginName] = $this->loadPlugin($pluginName);
        }

        return $results;
    }

    /**
     * 获取插件统计信息
     */
    public function getStats(): array
    {
        $stats = [
            'total_plugins' => count($this->plugins),
            'enabled_plugins' => 0,
            'disabled_plugins' => 0,
            'plugin_details' => [],
        ];

        foreach ($this->plugins as $name => $plugin) {
            $enabled = $plugin->isEnabled();
            
            if ($enabled) {
                $stats['enabled_plugins']++;
            } else {
                $stats['disabled_plugins']++;
            }

            $stats['plugin_details'][$name] = [
                'enabled' => $enabled,
                'version' => $plugin->getVersion(),
                'author' => $plugin->getAuthor(),
            ];
        }

        return $stats;
    }

    /**
     * 验证插件
     */
    public function validatePlugin(string $pluginPath): array
    {
        $errors = [];
        $warnings = [];

        // 检查插件目录
        if (!is_dir($pluginPath)) {
            $errors[] = 'Plugin directory does not exist';
            return ['valid' => false, 'errors' => $errors, 'warnings' => $warnings];
        }

        // 检查 plugin.php 文件
        $pluginFile = $pluginPath . '/plugin.php';
        if (!file_exists($pluginFile)) {
            $errors[] = 'plugin.php file not found';
        } else {
            $pluginInfo = include $pluginFile;
            
            if (!is_array($pluginInfo)) {
                $errors[] = 'plugin.php must return an array';
            } else {
                // 检查必需字段
                $requiredFields = ['class', 'file', 'name', 'version'];
                foreach ($requiredFields as $field) {
                    if (!isset($pluginInfo[$field])) {
                        $errors[] = "Missing required field: {$field}";
                    }
                }

                // 检查类文件
                if (isset($pluginInfo['file'])) {
                    $classFile = $pluginPath . '/' . $pluginInfo['file'];
                    if (!file_exists($classFile)) {
                        $errors[] = "Plugin class file not found: {$pluginInfo['file']}";
                    }
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }
}
