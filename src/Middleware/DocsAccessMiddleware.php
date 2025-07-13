<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Middleware;

use think\Request;
use think\Response;
use think\App;
use Closure;
use Yangweijie\ThinkScramble\Contracts\ConfigInterface;
use Yangweijie\ThinkScramble\Generator\OpenApiGenerator;
use Yangweijie\ThinkScramble\Exception\GenerationException;

/**
 * API 文档访问控制中间件
 * 
 * 控制 API 文档的访问权限和内容展示
 */
class DocsAccessMiddleware
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
     * 构造函数
     *
     * @param App $app ThinkPHP 应用实例
     * @param ConfigInterface $config 配置接口
     */
    public function __construct(App $app, ConfigInterface $config)
    {
        $this->app = $app;
        $this->config = $config;
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
        // 检查是否启用文档访问
        if (!$this->config->get('docs.enabled', true)) {
            return $this->createErrorResponse('API documentation is disabled', 404);
        }

        // 检查环境限制
        if (!$this->isEnvironmentAllowed()) {
            return $this->createErrorResponse('API documentation is not available in this environment', 403);
        }

        // 检查 IP 白名单
        if (!$this->isIpAllowed($request)) {
            return $this->createErrorResponse('Access denied from your IP address', 403);
        }

        // 检查认证
        if (!$this->isAuthenticated($request)) {
            return $this->createAuthenticationResponse();
        }

        // 处理文档请求
        return $this->handleDocsRequest($request, $next);
    }

    /**
     * 检查环境是否允许
     *
     * @return bool
     */
    protected function isEnvironmentAllowed(): bool
    {
        $allowedEnvs = $this->config->get('docs.allowed_environments', ['local', 'development', 'testing']);
        $currentEnv = $this->app->config->get('app.app_debug', false) ? 'development' : 'production';

        return in_array($currentEnv, $allowedEnvs);
    }

    /**
     * 检查 IP 是否允许
     *
     * @param Request $request 请求对象
     * @return bool
     */
    protected function isIpAllowed(Request $request): bool
    {
        $allowedIps = $this->config->get('docs.allowed_ips', []);
        
        // 如果没有配置 IP 白名单，则允许所有 IP
        if (empty($allowedIps)) {
            return true;
        }

        $clientIp = $request->ip();
        
        foreach ($allowedIps as $allowedIp) {
            if ($this->matchIp($clientIp, $allowedIp)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 匹配 IP 地址
     *
     * @param string $clientIp 客户端 IP
     * @param string $allowedIp 允许的 IP（支持通配符和 CIDR）
     * @return bool
     */
    protected function matchIp(string $clientIp, string $allowedIp): bool
    {
        // 精确匹配
        if ($clientIp === $allowedIp) {
            return true;
        }

        // 通配符匹配
        if (str_contains($allowedIp, '*')) {
            $pattern = str_replace('*', '.*', preg_quote($allowedIp, '/'));
            return preg_match("/^{$pattern}$/", $clientIp) === 1;
        }

        // CIDR 匹配
        if (str_contains($allowedIp, '/')) {
            return $this->matchCidr($clientIp, $allowedIp);
        }

        return false;
    }

    /**
     * CIDR 匹配
     *
     * @param string $ip IP 地址
     * @param string $cidr CIDR 表示法
     * @return bool
     */
    protected function matchCidr(string $ip, string $cidr): bool
    {
        [$subnet, $mask] = explode('/', $cidr);
        
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $this->matchCidrV4($ip, $subnet, (int) $mask);
        }
        
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $this->matchCidrV6($ip, $subnet, (int) $mask);
        }

        return false;
    }

    /**
     * IPv4 CIDR 匹配
     *
     * @param string $ip IP 地址
     * @param string $subnet 子网
     * @param int $mask 掩码
     * @return bool
     */
    protected function matchCidrV4(string $ip, string $subnet, int $mask): bool
    {
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong = -1 << (32 - $mask);
        
        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }

    /**
     * IPv6 CIDR 匹配
     *
     * @param string $ip IP 地址
     * @param string $subnet 子网
     * @param int $mask 掩码
     * @return bool
     */
    protected function matchCidrV6(string $ip, string $subnet, int $mask): bool
    {
        $ipBin = inet_pton($ip);
        $subnetBin = inet_pton($subnet);
        
        if ($ipBin === false || $subnetBin === false) {
            return false;
        }

        $byteMask = $mask >> 3;
        $bitMask = $mask & 7;

        // 比较完整字节
        if ($byteMask > 0 && substr($ipBin, 0, $byteMask) !== substr($subnetBin, 0, $byteMask)) {
            return false;
        }

        // 比较剩余位
        if ($bitMask > 0) {
            $ipByte = ord($ipBin[$byteMask] ?? "\0");
            $subnetByte = ord($subnetBin[$byteMask] ?? "\0");
            $mask = 0xFF << (8 - $bitMask);
            
            return ($ipByte & $mask) === ($subnetByte & $mask);
        }

        return true;
    }

    /**
     * 检查是否已认证
     *
     * @param Request $request 请求对象
     * @return bool
     */
    protected function isAuthenticated(Request $request): bool
    {
        $authConfig = $this->config->get('docs.auth', []);
        
        // 如果没有配置认证，则不需要认证
        if (empty($authConfig) || !($authConfig['enabled'] ?? false)) {
            return true;
        }

        $authType = $authConfig['type'] ?? 'basic';
        
        switch ($authType) {
            case 'basic':
                return $this->checkBasicAuth($request, $authConfig);
            
            case 'bearer':
                return $this->checkBearerAuth($request, $authConfig);
            
            case 'custom':
                return $this->checkCustomAuth($request, $authConfig);
            
            default:
                return false;
        }
    }

    /**
     * 检查基本认证
     *
     * @param Request $request 请求对象
     * @param array $authConfig 认证配置
     * @return bool
     */
    protected function checkBasicAuth(Request $request, array $authConfig): bool
    {
        $authorization = $request->header('Authorization');
        
        if (!$authorization || !str_starts_with($authorization, 'Basic ')) {
            return false;
        }

        $credentials = base64_decode(substr($authorization, 6));
        [$username, $password] = explode(':', $credentials, 2);

        $validUsername = $authConfig['username'] ?? '';
        $validPassword = $authConfig['password'] ?? '';

        return $username === $validUsername && $password === $validPassword;
    }

    /**
     * 检查 Bearer 认证
     *
     * @param Request $request 请求对象
     * @param array $authConfig 认证配置
     * @return bool
     */
    protected function checkBearerAuth(Request $request, array $authConfig): bool
    {
        $authorization = $request->header('Authorization');
        
        if (!$authorization || !str_starts_with($authorization, 'Bearer ')) {
            return false;
        }

        $token = substr($authorization, 7);
        $validToken = $authConfig['token'] ?? '';

        return $token === $validToken;
    }

    /**
     * 检查自定义认证
     *
     * @param Request $request 请求对象
     * @param array $authConfig 认证配置
     * @return bool
     */
    protected function checkCustomAuth(Request $request, array $authConfig): bool
    {
        $callback = $authConfig['callback'] ?? null;
        
        if (!$callback || !is_callable($callback)) {
            return false;
        }

        return call_user_func($callback, $request, $authConfig);
    }

    /**
     * 处理文档请求
     *
     * @param Request $request 请求对象
     * @param Closure $next 下一个中间件
     * @return Response
     */
    protected function handleDocsRequest(Request $request, Closure $next): Response
    {
        $path = $request->pathinfo();
        $docsPath = $this->config->get('docs.path', '/docs');

        // 如果不是文档路径，继续下一个中间件
        if (!str_starts_with($path, $docsPath)) {
            return $next($request);
        }

        // 处理文档内容请求
        if (str_ends_with($path, '/openapi.json')) {
            return $this->serveOpenApiJson();
        }

        if (str_ends_with($path, '/openapi.yaml')) {
            return $this->serveOpenApiYaml();
        }

        // 处理文档页面请求
        return $this->serveDocsPage();
    }

    /**
     * 提供 OpenAPI JSON
     *
     * @return Response
     */
    protected function serveOpenApiJson(): Response
    {
        try {
            $generator = new OpenApiGenerator($this->app, $this->config);
            $json = $generator->generateJson(true);
            
            return Response::create($json, 200, [
                'Content-Type' => 'application/json',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
            ]);
        } catch (GenerationException $e) {
            return $this->createErrorResponse('Failed to generate documentation: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 提供 OpenAPI YAML
     *
     * @return Response
     */
    protected function serveOpenApiYaml(): Response
    {
        try {
            $generator = new OpenApiGenerator($this->app, $this->config);
            $yaml = $generator->generateYaml();
            
            return Response::create($yaml, 200, [
                'Content-Type' => 'application/x-yaml',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
            ]);
        } catch (GenerationException $e) {
            return $this->createErrorResponse('Failed to generate documentation: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 提供文档页面
     *
     * @return Response
     */
    protected function serveDocsPage(): Response
    {
        $title = $this->config->get('info.title', 'API Documentation');
        $docsPath = $this->config->get('docs.path', '/docs');
        
        $html = $this->getDocsPageHtml($title, $docsPath);
        
        return Response::create($html, 200, [
            'Content-Type' => 'text/html',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * 获取文档页面 HTML
     *
     * @param string $title 标题
     * @param string $docsPath 文档路径
     * @return string
     */
    protected function getDocsPageHtml(string $title, string $docsPath): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>{$title}</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@4.15.5/swagger-ui.css" />
    <style>
        html { box-sizing: border-box; overflow: -moz-scrollbars-vertical; overflow-y: scroll; }
        *, *:before, *:after { box-sizing: inherit; }
        body { margin:0; background: #fafafa; }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@4.15.5/swagger-ui-bundle.js"></script>
    <script>
        window.onload = function() {
            const ui = SwaggerUIBundle({
                url: '{$docsPath}/openapi.json',
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIBundle.presets.standalone
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout"
            });
        };
    </script>
</body>
</html>
HTML;
    }

    /**
     * 创建错误响应
     *
     * @param string $message 错误消息
     * @param int $statusCode 状态码
     * @return Response
     */
    protected function createErrorResponse(string $message, int $statusCode): Response
    {
        $content = json_encode([
            'error' => $message,
            'code' => $statusCode,
        ]);

        return Response::create($content, 'json', $statusCode, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * 创建认证响应
     *
     * @return Response
     */
    protected function createAuthenticationResponse(): Response
    {
        $authConfig = $this->config->get('docs.auth', []);
        $authType = $authConfig['type'] ?? 'basic';

        $headers = [
            'Content-Type' => 'application/json',
        ];

        if ($authType === 'basic') {
            $headers['WWW-Authenticate'] = 'Basic realm="API Documentation"';
        }

        $content = json_encode([
            'error' => 'Authentication required',
            'code' => 401,
        ]);

        return Response::create($content, 'json', 401, $headers);
    }
}
