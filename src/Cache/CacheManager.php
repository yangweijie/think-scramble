<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Cache;

use think\App;
use think\Cache;
use Yangweijie\ThinkScramble\Contracts\ConfigInterface;
use Yangweijie\ThinkScramble\Exception\CacheException;

/**
 * 缓存管理器
 * 
 * 管理 Scramble 的各种缓存策略和操作
 */
class CacheManager
{
    /**
     * ThinkPHP 应用实例
     */
    protected App $app;

    /**
     * 缓存实例
     */
    protected Cache $cache;

    /**
     * 配置接口
     */
    protected ConfigInterface $config;

    /**
     * 缓存键前缀
     */
    protected string $prefix = 'scramble_';

    /**
     * 缓存统计
     */
    protected array $stats = [
        'hits' => 0,
        'misses' => 0,
        'writes' => 0,
        'deletes' => 0,
    ];

    /**
     * 构造函数
     *
     * @param App $app ThinkPHP 应用实例
     * @param ConfigInterface $config 配置接口
     */
    public function __construct(App $app, ConfigInterface $config)
    {
        $this->app = $app;
        $this->cache = $app->cache;
        $this->config = $config;
        $this->prefix = $config->get('cache.prefix', 'scramble_');
    }

    /**
     * 获取缓存
     *
     * @param string $key 缓存键
     * @param mixed $default 默认值
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        try {
            $fullKey = $this->buildKey($key);
            $value = $this->cache->get($fullKey);
            
            if ($value !== null) {
                $this->stats['hits']++;
                return $this->unserializeValue($value);
            }
            
            $this->stats['misses']++;
            return $default;
        } catch (\Exception $e) {
            $this->stats['misses']++;
            return $default;
        }
    }

    /**
     * 设置缓存
     *
     * @param string $key 缓存键
     * @param mixed $value 缓存值
     * @param int|null $ttl 过期时间（秒）
     * @return bool
     */
    public function set(string $key, $value, ?int $ttl = null): bool
    {
        try {
            $fullKey = $this->buildKey($key);
            $ttl = $ttl ?? $this->config->get('cache.ttl', 3600);
            
            $serializedValue = $this->serializeValue($value);
            $result = $this->cache->set($fullKey, $serializedValue, $ttl);
            
            if ($result) {
                $this->stats['writes']++;
            }
            
            return $result;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 删除缓存
     *
     * @param string $key 缓存键
     * @return bool
     */
    public function delete(string $key): bool
    {
        try {
            $fullKey = $this->buildKey($key);
            $result = $this->cache->delete($fullKey);
            
            if ($result) {
                $this->stats['deletes']++;
            }
            
            return $result;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 检查缓存是否存在
     *
     * @param string $key 缓存键
     * @return bool
     */
    public function has(string $key): bool
    {
        try {
            $fullKey = $this->buildKey($key);
            return $this->cache->has($fullKey);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 获取或设置缓存
     *
     * @param string $key 缓存键
     * @param callable $callback 回调函数
     * @param int|null $ttl 过期时间（秒）
     * @return mixed
     */
    public function remember(string $key, callable $callback, ?int $ttl = null)
    {
        $value = $this->get($key);
        
        if ($value !== null) {
            return $value;
        }
        
        $value = $callback();
        $this->set($key, $value, $ttl);
        
        return $value;
    }

    /**
     * 清除所有 Scramble 相关缓存
     *
     * @return bool
     */
    public function flush(): bool
    {
        try {
            $pattern = $this->prefix . '*';
            return $this->cache->clear($pattern);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 根据标签清除缓存
     *
     * @param array $tags 标签列表
     * @return bool
     */
    public function flushByTags(array $tags): bool
    {
        try {
            foreach ($tags as $tag) {
                $keys = $this->getKeysByTag($tag);
                foreach ($keys as $key) {
                    $this->delete($key);
                }
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 获取缓存统计信息
     *
     * @return array
     */
    public function getStats(): array
    {
        $total = $this->stats['hits'] + $this->stats['misses'];
        $hitRate = $total > 0 ? round(($this->stats['hits'] / $total) * 100, 2) : 0;
        
        return array_merge($this->stats, [
            'hit_rate' => $hitRate,
            'total_requests' => $total,
        ]);
    }

    /**
     * 重置统计信息
     *
     * @return void
     */
    public function resetStats(): void
    {
        $this->stats = [
            'hits' => 0,
            'misses' => 0,
            'writes' => 0,
            'deletes' => 0,
        ];
    }

    /**
     * 构建完整的缓存键
     *
     * @param string $key 原始键
     * @return string
     */
    protected function buildKey(string $key): string
    {
        return $this->prefix . md5($key);
    }

    /**
     * 序列化值
     *
     * @param mixed $value 值
     * @return string
     */
    protected function serializeValue($value): string
    {
        return serialize([
            'data' => $value,
            'timestamp' => time(),
            'version' => $this->config->get('info.version', '1.0.0'),
        ]);
    }

    /**
     * 反序列化值
     *
     * @param string $value 序列化的值
     * @return mixed
     */
    protected function unserializeValue(string $value)
    {
        try {
            $data = unserialize($value);
            
            if (!is_array($data) || !isset($data['data'])) {
                return null;
            }
            
            // 检查版本兼容性
            $cachedVersion = $data['version'] ?? '';
            $currentVersion = $this->config->get('info.version', '1.0.0');
            
            if ($cachedVersion !== $currentVersion) {
                return null; // 版本不匹配，视为缓存失效
            }
            
            return $data['data'];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 根据标签获取缓存键
     *
     * @param string $tag 标签
     * @return array
     */
    protected function getKeysByTag(string $tag): array
    {
        // 这里可以扩展为实际的标签管理
        // 目前返回空数组，具体实现取决于缓存驱动的支持
        return [];
    }

    /**
     * 获取缓存大小信息
     *
     * @return array
     */
    public function getSizeInfo(): array
    {
        try {
            // 这里可以扩展为获取实际的缓存大小信息
            // 具体实现取决于缓存驱动的支持
            return [
                'total_keys' => 0,
                'total_size' => 0,
                'average_size' => 0,
            ];
        } catch (\Exception $e) {
            return [
                'total_keys' => 0,
                'total_size' => 0,
                'average_size' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * 预热缓存
     *
     * @param array $keys 要预热的键列表
     * @param callable $dataProvider 数据提供者
     * @return array 预热结果
     */
    public function warmup(array $keys, callable $dataProvider): array
    {
        $results = [];
        
        foreach ($keys as $key) {
            try {
                if (!$this->has($key)) {
                    $data = $dataProvider($key);
                    if ($data !== null) {
                        $this->set($key, $data);
                        $results[$key] = 'warmed';
                    } else {
                        $results[$key] = 'no_data';
                    }
                } else {
                    $results[$key] = 'already_cached';
                }
            } catch (\Exception $e) {
                $results[$key] = 'error: ' . $e->getMessage();
            }
        }
        
        return $results;
    }
}
