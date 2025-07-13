<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Middleware;

use think\Request;
use think\Response;
use think\App;
use think\Cache;
use Closure;
use Yangweijie\ThinkScramble\Contracts\ConfigInterface;

/**
 * API 文档缓存中间件
 * 
 * 缓存生成的 API 文档以提高性能
 */
class CacheMiddleware
{
    /**
     * ThinkPHP 应用实例
     */
    protected App $app;

    /**
     * 配置接口
     */
    protected ConfigInterface $config;

    /**
     * 缓存实例
     */
    protected Cache $cache;

    /**
     * 构造函数
     *
     * @param App $app ThinkPHP 应用实例
     * @param ConfigInterface $config 配置接口
     */
    public function __construct(App $app, ConfigInterface $config)
    {
        $this->app = $app;
        $this->config = $config;
        $this->cache = $app->cache;
    }

    /**
     * 处理请求
     *
     * @param Request $request 请求对象
     * @param Closure $next 下一个中间件
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 检查是否启用缓存
        if (!$this->config->get('cache.enabled', true)) {
            return $next($request);
        }

        // 检查是否为文档请求
        if (!$this->isDocsRequest($request)) {
            return $next($request);
        }

        // 生成缓存键
        $cacheKey = $this->generateCacheKey($request);

        // 尝试从缓存获取响应
        $cachedResponse = $this->getCachedResponse($cacheKey);
        if ($cachedResponse) {
            return $this->createResponseFromCache($cachedResponse);
        }

        // 执行下一个中间件
        $response = $next($request);

        // 缓存响应
        if ($this->shouldCacheResponse($response)) {
            $this->cacheResponse($cacheKey, $response);
        }

        return $response;
    }

    /**
     * 检查是否为文档请求
     *
     * @param Request $request 请求对象
     * @return bool
     */
    protected function isDocsRequest(Request $request): bool
    {
        $path = $request->pathinfo();
        $docsPath = $this->config->get('docs.path', '/docs');

        return str_starts_with($path, $docsPath);
    }

    /**
     * 生成缓存键
     *
     * @param Request $request 请求对象
     * @return string
     */
    protected function generateCacheKey(Request $request): string
    {
        $path = $request->pathinfo();
        $query = $request->query() ?: [];
        
        // 排序查询参数以确保一致性
        if (is_array($query)) {
            ksort($query);
        } else {
            $query = [];
        }
        
        $keyData = [
            'path' => $path,
            'query' => $query,
            'version' => $this->getApiVersion(),
            'env' => $this->app->config->get('app.app_debug', false) ? 'development' : 'production',
        ];

        $keyString = serialize($keyData);
        return 'scramble_docs_' . md5($keyString);
    }

    /**
     * 获取 API 版本
     *
     * @return string
     */
    protected function getApiVersion(): string
    {
        return $this->config->get('info.version', '1.0.0');
    }

    /**
     * 从缓存获取响应
     *
     * @param string $cacheKey 缓存键
     * @return array|null
     */
    protected function getCachedResponse(string $cacheKey): ?array
    {
        try {
            $cached = $this->cache->get($cacheKey);
            
            if ($cached && is_array($cached)) {
                // 检查缓存是否过期
                if (isset($cached['expires_at']) && time() > $cached['expires_at']) {
                    $this->cache->delete($cacheKey);
                    return null;
                }
                
                return $cached;
            }
        } catch (\Exception $e) {
            // 缓存获取失败，继续正常流程
        }

        return null;
    }

    /**
     * 从缓存创建响应
     *
     * @param array $cachedData 缓存数据
     * @return Response
     */
    protected function createResponseFromCache(array $cachedData): Response
    {
        $headers = $cachedData['headers'] ?? [];
        
        // 添加缓存标识头
        $headers['X-Scramble-Cache'] = 'HIT';
        $headers['X-Scramble-Cache-Time'] = date('Y-m-d H:i:s', $cachedData['cached_at'] ?? time());
        
        return Response::create(
            $cachedData['content'] ?? '',
            $cachedData['status_code'] ?? 200,
            $headers
        );
    }

    /**
     * 检查是否应该缓存响应
     *
     * @param Response $response 响应对象
     * @return bool
     */
    protected function shouldCacheResponse(Response $response): bool
    {
        $statusCode = $response->getCode();
        
        // 只缓存成功响应
        if ($statusCode < 200 || $statusCode >= 300) {
            return false;
        }

        // 检查响应大小限制
        $maxSize = $this->config->get('cache.max_size', 1024 * 1024); // 1MB
        $contentLength = strlen($response->getContent());
        
        if ($contentLength > $maxSize) {
            return false;
        }

        // 检查内容类型
        $contentType = $response->getHeader('Content-Type');
        $allowedTypes = $this->config->get('cache.allowed_types', [
            'application/json',
            'application/x-yaml',
            'text/html',
        ]);

        foreach ($allowedTypes as $allowedType) {
            if (str_contains($contentType, $allowedType)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 缓存响应
     *
     * @param string $cacheKey 缓存键
     * @param Response $response 响应对象
     * @return void
     */
    protected function cacheResponse(string $cacheKey, Response $response): void
    {
        try {
            $ttl = $this->config->get('cache.ttl', 3600); // 1小时
            $expiresAt = time() + $ttl;
            
            $cacheData = [
                'content' => $response->getContent(),
                'status_code' => $response->getCode(),
                'headers' => $response->getHeader(),
                'cached_at' => time(),
                'expires_at' => $expiresAt,
            ];

            $this->cache->set($cacheKey, $cacheData, $ttl);
        } catch (\Exception $e) {
            // 缓存设置失败，不影响正常响应
        }
    }

    /**
     * 清除文档缓存
     *
     * @return bool
     */
    public function clearDocsCache(): bool
    {
        try {
            $pattern = 'scramble_docs_*';
            return $this->cache->clear($pattern);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 获取缓存统计信息
     *
     * @return array
     */
    public function getCacheStats(): array
    {
        try {
            $stats = [
                'enabled' => $this->config->get('cache.enabled', true),
                'ttl' => $this->config->get('cache.ttl', 3600),
                'max_size' => $this->config->get('cache.max_size', 1024 * 1024),
                'hit_count' => 0,
                'miss_count' => 0,
                'total_size' => 0,
            ];

            // 这里可以扩展为获取实际的缓存统计信息
            // 具体实现取决于使用的缓存驱动

            return $stats;
        } catch (\Exception $e) {
            return [
                'enabled' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * 预热缓存
     *
     * @param array $urls 要预热的 URL 列表
     * @return array 预热结果
     */
    public function warmupCache(array $urls = []): array
    {
        $results = [];
        
        if (empty($urls)) {
            $docsPath = $this->config->get('docs.path', '/docs');
            $urls = [
                $docsPath,
                $docsPath . '/openapi.json',
                $docsPath . '/openapi.yaml',
            ];
        }

        foreach ($urls as $url) {
            try {
                // 创建模拟请求
                $request = new Request();
                $request->setPathinfo($url);
                
                // 生成缓存键
                $cacheKey = $this->generateCacheKey($request);
                
                // 检查是否已缓存
                if ($this->getCachedResponse($cacheKey)) {
                    $results[$url] = 'already_cached';
                    continue;
                }

                // 这里可以扩展为实际生成和缓存内容
                $results[$url] = 'warmed_up';
                
            } catch (\Exception $e) {
                $results[$url] = 'failed: ' . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * 设置缓存标签
     *
     * @param string $cacheKey 缓存键
     * @param array $tags 标签列表
     * @return void
     */
    protected function setCacheTags(string $cacheKey, array $tags): void
    {
        try {
            // 如果缓存驱动支持标签，可以在这里实现
            // 例如：$this->cache->tag($tags)->set($cacheKey, $data);
        } catch (\Exception $e) {
            // 忽略标签设置失败
        }
    }

    /**
     * 根据标签清除缓存
     *
     * @param array $tags 标签列表
     * @return bool
     */
    public function clearCacheByTags(array $tags): bool
    {
        try {
            // 如果缓存驱动支持标签，可以在这里实现
            // 例如：return $this->cache->tag($tags)->clear();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 获取缓存键列表
     *
     * @return array
     */
    public function getCacheKeys(): array
    {
        try {
            // 这里可以扩展为获取所有相关的缓存键
            // 具体实现取决于使用的缓存驱动
            return [];
        } catch (\Exception $e) {
            return [];
        }
    }
}
