<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Adapter;

use think\App;
use think\Route;
use think\route\Rule;
use think\route\RuleItem;
use think\route\RuleGroup;
use think\route\Resource;
use think\route\Domain;
use Yangweijie\ThinkScramble\Exception\AnalysisException;

/**
 * ThinkPHP 路由分析器
 * 
 * 分析 ThinkPHP 路由定义，支持资源路由、分组路由、域名路由等
 */
class RouteAnalyzer
{
    /**
     * ThinkPHP 应用实例
     */
    protected App $app;

    /**
     * 路由实例
     */
    protected Route $route;

    /**
     * 路由缓存
     */
    protected array $routeCache = [];

    /**
     * 构造函数
     *
     * @param App|null $app ThinkPHP 应用实例
     */
    public function __construct(?App $app = null)
    {
        $this->app = $app ?: new App();
        $this->route = $this->app->route;
    }

    /**
     * 分析所有路由
     *
     * @return array
     * @throws AnalysisException
     */
    public function analyzeRoutes(): array
    {
        try {
            $routes = [];

            // 确保路由已加载
            $this->ensureRoutesLoaded();

            // 获取所有域名的路由规则
            $allRules = $this->getAllRouteRules();

            foreach ($allRules as $rule) {
                if ($rule instanceof \think\route\RuleItem) {
                    $routeInfo = $this->analyzeRule($rule);
                    if ($routeInfo) {
                        $routes[] = $routeInfo;
                    }
                } elseif ($rule instanceof \think\route\RuleGroup) {
                    $groupRoutes = $this->analyzeRuleGroup($rule);
                    $routes = array_merge($routes, $groupRoutes);
                } elseif (is_object($rule) && method_exists($rule, 'getRule')) {
                    // 处理我们的模拟对象
                    $routeInfo = $this->analyzeMockRule($rule);
                    if ($routeInfo) {
                        $routes[] = $routeInfo;
                    }
                }
            }

            return $routes;
        } catch (\Exception $e) {
            throw new AnalysisException("Failed to analyze routes: " . $e->getMessage());
        }
    }

    /**
     * 获取所有路由规则
     */
    protected function getAllRouteRules(): array
    {
        $allRules = [];

        try {
            // 直接解析路由列表命令的输出
            $allRules = $this->getRoutesFromCommandOutput();

        } catch (\Exception $e) {
            // 如果失败，使用备用方法
            $allRules = $this->getRoutesByAlternativeMethod();
        }

        return $allRules;
    }

    /**
     * 通过解析命令输出获取路由
     */
    protected function getRoutesFromCommandOutput(): array
    {
        $routes = [];

        try {
            // 执行路由列表命令并获取输出
            $output = $this->executeRouteListCommand();

            if (!empty($output)) {
                $routes = $this->parseRouteListOutput($output);
            }

        } catch (\Exception $e) {
            // 忽略错误
        }

        return $routes;
    }

    /**
     * 执行路由列表命令
     */
    protected function executeRouteListCommand(): string
    {
        try {
            // 获取应用根目录
            $rootPath = $this->app->getRootPath();

            // 执行命令
            $command = "cd " . escapeshellarg($rootPath) . " && php think route:list";
            $output = shell_exec($command);

            return $output ?: '';
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * 解析路由列表输出
     */
    protected function parseRouteListOutput(string $output): array
    {
        $routes = [];

        try {
            // 按行分割输出
            $lines = explode("\n", $output);

            foreach ($lines as $line) {
                $line = trim($line);

                // 跳过表格边框和标题行
                if (empty($line) || strpos($line, '+') === 0 || strpos($line, 'Rule') !== false) {
                    continue;
                }

                // 解析表格行
                if (strpos($line, '|') !== false) {
                    $parts = array_map('trim', explode('|', $line));

                    if (count($parts) >= 4) {
                        $rule = $parts[1] ?? '';
                        $route = $parts[2] ?? '';
                        $method = $parts[3] ?? 'GET';
                        $name = $parts[4] ?? '';

                        if (!empty($rule) && !empty($route)) {
                            $mockRule = $this->createMockRuleItem([
                                'rule' => $rule,
                                'route' => $route,
                                'method' => strtoupper($method),
                                'name' => $name,
                                'option' => [],
                                'pattern' => []
                            ]);

                            if ($mockRule) {
                                $routes[] = $mockRule;
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // 忽略解析错误
        }

        return $routes;
    }

    /**
     * 通过命令行获取路由信息
     */
    protected function getRoutesFromCommand(): array
    {
        $routes = [];

        try {
            // 创建一个临时的路由列表命令实例
            $command = new \think\console\command\RouteList();

            // 使用反射获取路由数据
            $reflection = new \ReflectionClass($command);
            if ($reflection->hasMethod('getRouteList')) {
                $method = $reflection->getMethod('getRouteList');
                $method->setAccessible(true);
                $routeData = $method->invoke($command);

                // 转换为我们需要的格式
                foreach ($routeData as $route) {
                    if (isset($route['rule']) && isset($route['route'])) {
                        // 创建一个模拟的 RuleItem 对象
                        $ruleItem = $this->createMockRuleItem($route);
                        if ($ruleItem) {
                            $routes[] = $ruleItem;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // 忽略错误
        }

        return $routes;
    }

    /**
     * 创建模拟的 RuleItem 对象
     */
    protected function createMockRuleItem(array $routeData): ?object
    {
        try {
            // 创建一个简单的对象来模拟 RuleItem
            $mockRule = new class($routeData) {
                private $data;

                public function __construct($data) {
                    $this->data = $data;
                }

                public function getRule() {
                    return $this->data['rule'] ?? '';
                }

                public function getRoute() {
                    return $this->data['route'] ?? '';
                }

                public function getMethod() {
                    return $this->data['method'] ?? 'GET';
                }

                public function getName() {
                    return $this->data['name'] ?? '';
                }

                public function getOption() {
                    return $this->data['option'] ?? [];
                }

                public function getPattern() {
                    return $this->data['pattern'] ?? [];
                }
            };

            return $mockRule;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 备用方法获取路由
     */
    protected function getRoutesByAlternativeMethod(): array
    {
        $routes = [];

        try {
            // 尝试通过路由列表命令获取
            $routeList = $this->route->getRuleList();

            foreach ($routeList as $routeData) {
                if (is_object($routeData)) {
                    $routes[] = $routeData;
                }
            }
        } catch (\Exception $e) {
            // 忽略错误
        }

        return $routes;
    }

    /**
     * 分析模拟路由对象
     */
    protected function analyzeMockRule(object $rule): ?array
    {
        try {
            $routeInfo = [
                'domain' => '',
                'rule' => method_exists($rule, 'getRule') ? $rule->getRule() : '',
                'route' => method_exists($rule, 'getRoute') ? $rule->getRoute() : '',
                'method' => $this->normalizeHttpMethod(method_exists($rule, 'getMethod') ? $rule->getMethod() : 'get'),
                'name' => method_exists($rule, 'getName') ? $rule->getName() : '',
                'middleware' => [],
                'option' => method_exists($rule, 'getOption') ? $rule->getOption() : [],
                'pattern' => method_exists($rule, 'getPattern') ? $rule->getPattern() : [],
                'type' => 'single',
                'controller' => null,
                'action' => null,
                'parameters' => [],
                'is_resource' => false,
                'resource_actions' => [],
            ];

            // 跳过 Closure 路由
            if ($routeInfo['route'] instanceof \Closure) {
                return null;
            }

            // 解析控制器和方法
            $this->parseControllerAction($routeInfo);

            // 解析路由参数
            $this->parseRouteParameters($routeInfo);

            return $routeInfo;
        } catch (\Exception $e) {
            // 忽略解析失败的路由
            return null;
        }
    }

    /**
     * 确保路由已加载
     */
    protected function ensureRoutesLoaded(): void
    {
        try {
            // 手动加载路由文件
            $routePath = $this->app->getRootPath() . 'route' . DIRECTORY_SEPARATOR . 'app.php';
            if (file_exists($routePath)) {
                require_once $routePath;
            }

            // 注解路由已经由 ThinkPHP 自动加载，无需手动处理
            // ThinkPHP 的注解扩展会自动扫描控制器并注册路由

        } catch (\Exception $e) {
            error_log("Error loading routes: " . $e->getMessage());
        }
    }


    /**
     * 获取路由域名
     *
     * @return array
     */
    protected function getRouteDomains(): array
    {
        try {
            // 首先尝试使用公共方法
            if (method_exists($this->route, 'getDomains')) {
                return $this->route->getDomains();
            }

            // 使用反射获取 domains 属性
            $reflection = new \ReflectionClass($this->route);
            $domainsProperty = $reflection->getProperty('domains');
            $domainsProperty->setAccessible(true);
            return $domainsProperty->getValue($this->route) ?: [];

        } catch (\Exception $e) {
            error_log("Error getting route domains: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 分析路由数据（ThinkPHP 官方格式）
     *
     * @param array $routeData 路由数据
     * @return array|null
     */
    protected function analyzeRouteData(array $routeData): ?array
    {
        try {
            // 直接使用 ThinkPHP 官方格式的数据
            $routeInfo = [
                'domain' => $routeData['domain'] ?? '',
                'rule' => $routeData['rule'] ?? '',
                'route' => $routeData['route'] ?? '',
                'method' => $this->normalizeHttpMethod($routeData['method'] ?? 'get'),
                'name' => $routeData['name'] ?? '',
                'middleware' => [], // 中间件需要单独获取
                'option' => $routeData['option'] ?? [],
                'pattern' => $routeData['pattern'] ?? [],
                'type' => 'single',
                'controller' => null,
                'action' => null,
                'parameters' => [],
                'is_resource' => false,
                'resource_actions' => [],
            ];

            // 跳过 Closure 路由
            if ($routeInfo['route'] instanceof \Closure) {
                return null;
            }

            // 解析控制器和方法
            $this->parseControllerAction($routeInfo);

            // 解析路由参数
            $this->parseRouteParameters($routeInfo);

            return $routeInfo;
        } catch (\Exception $e) {
            // 忽略解析失败的路由
            return null;
        }
    }

    /**
     * 分析域名路由（已废弃，保留兼容性）
     *
     * @param Domain $domain 域名对象
     * @param string $domainName 域名名称
     * @return array
     */
    protected function analyzeDomain(Domain $domain, string $domainName): array
    {
        $routes = [];

        try {
            // 使用反射获取域名下的路由规则
            $rules = $this->getDomainRules($domain);

            foreach ($rules as $rule) {
                $routeInfos = $this->analyzeRuleOrGroup($rule, $domainName);
                if ($routeInfos) {
                    if (is_array($routeInfos) && isset($routeInfos[0]) && is_array($routeInfos[0])) {
                        // 多个路由（来自路由组）
                        $routes = array_merge($routes, $routeInfos);
                    } else {
                        // 单个路由
                        $routes[] = $routeInfos;
                    }
                }
            }
        } catch (\Exception $e) {
            // 记录错误但继续处理
            error_log("Error analyzing domain {$domainName}: " . $e->getMessage());
        }

        return $routes;
    }

    /**
     * 获取域名下的路由规则
     *
     * @param Domain $domain 域名对象
     * @return array
     */
    protected function getDomainRules(Domain $domain): array
    {
        try {
            // 首先尝试使用公共方法
            if (method_exists($domain, 'getRules')) {
                return $domain->getRules();
            }

            // 使用反射获取 rules 属性
            $reflection = new \ReflectionClass($domain);
            $rulesProperty = $reflection->getProperty('rules');
            $rulesProperty->setAccessible(true);
            return $rulesProperty->getValue($domain) ?: [];

        } catch (\Exception $e) {
            error_log("Error getting domain rules: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 分析路由规则或路由组
     *
     * @param mixed $rule 路由规则或路由组
     * @param string $domain 域名
     * @return array|null
     */
    protected function analyzeRuleOrGroup($rule, string $domain = ''): ?array
    {
        // 检查是否为路由组
        if ($rule instanceof \think\route\RuleGroup) {
            return $this->analyzeRuleGroup($rule, $domain);
        }

        // 检查是否为单个路由规则
        if ($rule instanceof \think\route\RuleItem) {
            return $this->analyzeRule($rule, $domain);
        }

        return null;
    }

    /**
     * 分析路由组
     *
     * @param \think\route\RuleGroup $group 路由组
     * @param string $domain 域名
     * @return array
     */
    protected function analyzeRuleGroup(\think\route\RuleGroup $group, string $domain = ''): array
    {
        $routes = [];

        try {
            // 获取路由组中的规则
            $rules = $this->getGroupRules($group);

            foreach ($rules as $rule) {
                $routeInfo = $this->analyzeRuleOrGroup($rule, $domain);
                if ($routeInfo) {
                    if (is_array($routeInfo) && isset($routeInfo[0]) && is_array($routeInfo[0])) {
                        // 嵌套路由组
                        $routes = array_merge($routes, $routeInfo);
                    } else {
                        // 单个路由
                        $routes[] = $routeInfo;
                    }
                }
            }
        } catch (\Exception $e) {
            // 记录错误但继续处理
            error_log("Error analyzing route group: " . $e->getMessage());
        }

        return $routes;
    }

    /**
     * 获取路由组中的规则
     *
     * @param \think\route\RuleGroup $group 路由组
     * @return array
     */
    protected function getGroupRules(\think\route\RuleGroup $group): array
    {
        try {
            // 首先尝试使用公共方法
            if (method_exists($group, 'getRules')) {
                return $group->getRules();
            }

            // 使用反射获取 rules 属性
            $reflection = new \ReflectionClass($group);
            $rulesProperty = $reflection->getProperty('rules');
            $rulesProperty->setAccessible(true);
            return $rulesProperty->getValue($group) ?: [];

        } catch (\Exception $e) {
            error_log("Error getting group rules: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 分析路由规则
     *
     * @param \think\route\RuleItem $rule 路由规则
     * @param string $domain 域名
     * @return array|null
     */
    protected function analyzeRule(\think\route\RuleItem $rule, string $domain = ''): ?array
    {
        try {
            $routeInfo = [
                'domain' => $domain,
                'rule' => $this->safeGetProperty($rule, 'getRule'),
                'route' => $this->safeGetProperty($rule, 'getRoute'),
                'method' => $this->normalizeHttpMethod($this->safeGetProperty($rule, 'getMethod')),
                'name' => $this->safeGetProperty($rule, 'getName'),
                'middleware' => $this->safeGetMiddleware($rule),
                'option' => $this->safeGetProperty($rule, 'getOption'),
                'pattern' => $this->safeGetProperty($rule, 'getPattern'),
                'type' => $this->getRouteType($rule),
                'controller' => null,
                'action' => null,
                'parameters' => [],
                'is_resource' => $this->isResourceRoute($rule),
                'resource_actions' => [],
            ];

            // 解析控制器和方法
            $this->parseControllerAction($routeInfo);

            // 解析路由参数
            $this->parseRouteParameters($routeInfo);

            // 解析资源路由
            if ($routeInfo['is_resource']) {
                $this->parseResourceRoute($routeInfo, $rule);
            }

            return $routeInfo;
        } catch (\Exception $e) {
            // 忽略解析失败的路由
            return null;
        }
    }

    /**
     * 获取路由类型
     *
     * @param RuleItem $rule
     * @return string
     */
    protected function getRouteType(RuleItem $rule): string
    {
        if ($rule instanceof Resource) {
            return 'resource';
        }

        if ($rule instanceof RuleGroup) {
            return 'group';
        }

        return 'single';
    }

    /**
     * 标准化 HTTP 方法
     *
     * @param mixed $method HTTP 方法
     * @return string
     */
    protected function normalizeHttpMethod($method): string
    {
        if (is_array($method)) {
            // 如果是数组，取第一个方法
            return strtolower($method[0] ?? 'get');
        }

        if (is_string($method)) {
            return strtolower($method);
        }

        return 'get'; // 默认为 GET
    }

    /**
     * 检查是否为资源路由
     *
     * @param \think\route\RuleItem $rule 路由规则
     * @return bool
     */
    protected function isResourceRoute(\think\route\RuleItem $rule): bool
    {
        return $rule instanceof \think\route\Resource;
    }

    /**
     * 解析资源路由
     *
     * @param array &$routeInfo 路由信息
     * @param \think\route\RuleItem $rule 路由规则
     * @return void
     */
    protected function parseResourceRoute(array &$routeInfo, \think\route\RuleItem $rule): void
    {
        if (!$this->isResourceRoute($rule)) {
            return;
        }

        try {
            // 获取资源路由的标准动作
            $resourceActions = [
                'index' => ['method' => 'get', 'path' => ''],
                'create' => ['method' => 'get', 'path' => '/create'],
                'store' => ['method' => 'post', 'path' => ''],
                'show' => ['method' => 'get', 'path' => '/{id}'],
                'edit' => ['method' => 'get', 'path' => '/{id}/edit'],
                'update' => ['method' => 'put', 'path' => '/{id}'],
                'delete' => ['method' => 'delete', 'path' => '/{id}'],
            ];

            $routeInfo['resource_actions'] = $resourceActions;
            $routeInfo['type'] = 'resource';
        } catch (\Exception $e) {
            // 忽略资源路由解析错误
        }
    }

    /**
     * 解析控制器和方法
     *
     * @param array &$routeInfo
     * @return void
     */
    protected function parseControllerAction(array &$routeInfo): void
    {
        $route = $routeInfo['route'];

        if (is_string($route)) {
            // 检查是否是 @ 分隔符格式：app\controller\Api@users
            if (strpos($route, '@') !== false) {
                $parts = explode('@', $route);
                if (count($parts) === 2) {
                    $routeInfo['controller'] = $parts[0];
                    $routeInfo['action'] = $parts[1];
                    return;
                }
            }

            // 格式：controller/action 或 module/controller/action
            $parts = explode('/', trim($route, '/'));

            if (count($parts) >= 2) {
                if (count($parts) === 3) {
                    // 多应用模式：module/controller/action
                    $routeInfo['module'] = $parts[0];
                    $routeInfo['controller'] = $parts[1];
                    $routeInfo['action'] = $parts[2];
                } else {
                    // 单应用模式：controller/action
                    $controllerName = $parts[0];
                    $actionName = $parts[1];

                    // 如果控制器名称不包含命名空间，添加默认命名空间
                    if (strpos($controllerName, '\\') === false) {
                        $baseNamespace = $this->app->config->get('app.app_namespace', 'app');
                        $controllerLayer = $this->app->config->get('route.controller_layer', 'controller');

                        // 确保基础命名空间不为空
                        if (empty($baseNamespace)) {
                            $baseNamespace = 'app';
                        }

                        $controllerName = $baseNamespace . '\\' . $controllerLayer . '\\' . $controllerName;
                    }

                    $routeInfo['controller'] = $controllerName;
                    $routeInfo['action'] = $actionName;
                }
            }
        } elseif (is_array($route) && count($route) >= 2) {
            // 数组格式：[controller, action]
            $routeInfo['controller'] = $route[0];
            $routeInfo['action'] = $route[1];
        }
    }

    /**
     * 解析路由参数
     *
     * @param array &$routeInfo
     * @return void
     */
    protected function parseRouteParameters(array &$routeInfo): void
    {
        $rule = $routeInfo['rule'];
        $pattern = $routeInfo['pattern'];

        // 提取路由中的参数
        if (preg_match_all('/<(\w+)>/', $rule, $matches)) {
            foreach ($matches[1] as $param) {
                $paramInfo = [
                    'name' => $param,
                    'required' => true,
                    'pattern' => $pattern[$param] ?? '[\w\.]+',
                    'type' => $this->inferParameterType($pattern[$param] ?? ''),
                ];

                $routeInfo['parameters'][] = $paramInfo;
            }
        }

        // 处理可选参数
        if (preg_match_all('/\[([^\]]+)\]/', $rule, $matches)) {
            foreach ($matches[1] as $optional) {
                if (preg_match('/<(\w+)>/', $optional, $paramMatch)) {
                    $param = $paramMatch[1];
                    $paramInfo = [
                        'name' => $param,
                        'required' => false,
                        'pattern' => $pattern[$param] ?? '[\w\.]+',
                        'type' => $this->inferParameterType($pattern[$param] ?? ''),
                    ];

                    $routeInfo['parameters'][] = $paramInfo;
                }
            }
        }
    }

    /**
     * 推断参数类型
     *
     * @param string $pattern 参数模式
     * @return string
     */
    protected function inferParameterType(string $pattern): string
    {
        if (empty($pattern)) {
            return 'string';
        }

        // 根据正则模式推断类型
        if (preg_match('/^\[\\\\d\+\]$/', $pattern) || $pattern === '\d+') {
            return 'integer';
        }

        if (preg_match('/^\[\\\\d\+\\\\.\?\]$/', $pattern)) {
            return 'number';
        }

        return 'string';
    }

    /**
     * 分析资源路由
     *
     * @param string $resource 资源名称
     * @return array
     */
    public function analyzeResourceRoute(string $resource): array
    {
        $routes = [];
        $restActions = $this->route->getRest();

        foreach ($restActions as $action => $config) {
            [$method, $uri, $actionName] = $config;
            
            $routes[] = [
                'resource' => $resource,
                'action' => $action,
                'method' => strtoupper($method),
                'uri' => $resource . $uri,
                'controller_action' => $actionName,
                'type' => 'resource',
            ];
        }

        return $routes;
    }

    /**
     * 获取路由中间件
     *
     * @param string $route 路由规则
     * @return array
     */
    public function getRouteMiddleware(string $route): array
    {
        $rules = $this->route->getRule($route);
        $middleware = [];

        foreach ($rules as $rule) {
            $ruleMiddleware = $rule->getMiddleware();
            if (!empty($ruleMiddleware)) {
                $middleware = array_merge($middleware, $ruleMiddleware);
            }
        }

        return array_unique($middleware);
    }

    /**
     * 检查路由是否为 API 路由
     *
     * @param array $routeInfo 路由信息
     * @return bool
     */
    public function isApiRoute(array $routeInfo): bool
    {
        $rule = $routeInfo['rule'];
        $middleware = $routeInfo['middleware'] ?? [];

        // 检查路由规则是否以 api 开头
        if (str_starts_with($rule, 'api/')) {
            return true;
        }

        // 检查是否有 API 相关中间件
        $apiMiddleware = ['api', 'auth:api', 'throttle'];
        foreach ($middleware as $mw) {
            if (in_array($mw, $apiMiddleware)) {
                return true;
            }
        }

        // 检查控制器是否在 api 目录下
        $controller = $routeInfo['controller'] ?? '';
        if (str_contains($controller, 'api\\') || str_contains($controller, 'Api\\')) {
            return true;
        }

        return false;
    }

    /**
     * 获取多应用模式下的应用列表
     *
     * @return array
     */
    public function getApplications(): array
    {
        $applications = [];
        
        // 检查是否为多应用模式
        if ($this->app->config->get('app.multi_app', false)) {
            $appPath = $this->app->getAppPath();
            
            if (is_dir($appPath)) {
                $dirs = scandir($appPath);
                foreach ($dirs as $dir) {
                    if ($dir !== '.' && $dir !== '..' && is_dir($appPath . $dir)) {
                        $applications[] = $dir;
                    }
                }
            }
        }

        return $applications;
    }

    /**
     * 安全获取属性
     *
     * @param object $object 对象
     * @param string $method 方法名
     * @return mixed
     */
    protected function safeGetProperty($object, string $method)
    {
        try {
            if (method_exists($object, $method)) {
                return $object->$method();
            }
        } catch (\Exception $e) {
            // 忽略错误
        }

        return null;
    }

    /**
     * 安全获取中间件
     *
     * @param object $rule 路由规则
     * @return array
     */
    protected function safeGetMiddleware($rule): array
    {
        try {
            if (method_exists($rule, 'getMiddleware')) {
                $middleware = $rule->getMiddleware();
                return is_array($middleware) ? $middleware : [];
            }
        } catch (\Exception $e) {
            // 忽略错误
        }

        return [];
    }

    /**
     * 清除路由缓存
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->routeCache = [];
    }
}
