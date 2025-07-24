<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Adapter;

use think\App;
use think\Controller;
use ReflectionClass;
use ReflectionMethod;
use Yangweijie\ThinkScramble\Analyzer\CodeAnalyzer;
use Yangweijie\ThinkScramble\Analyzer\ReflectionAnalyzer;
use Yangweijie\ThinkScramble\Exception\AnalysisException;

/**
 * ThinkPHP 控制器解析器
 * 
 * 解析 ThinkPHP 控制器结构和方法，提取 API 相关信息
 */
class ControllerParser
{
    /**
     * ThinkPHP 应用实例
     */
    protected App $app;

    /**
     * 代码分析器
     */
    protected CodeAnalyzer $codeAnalyzer;

    /**
     * 反射分析器
     */
    protected ReflectionAnalyzer $reflectionAnalyzer;

    /**
     * 控制器缓存
     */
    protected array $controllerCache = [];

    /**
     * 构造函数
     *
     * @param App|null $app ThinkPHP 应用实例
     */
    public function __construct(?App $app = null)
    {
        $this->app = $app ?: new App();
        $this->codeAnalyzer = new CodeAnalyzer();
        $this->reflectionAnalyzer = new ReflectionAnalyzer();
    }

    /**
     * 解析控制器
     *
     * @param string $controller 控制器名称
     * @param string|null $module 模块名称（多应用模式）
     * @return array
     * @throws AnalysisException
     */
    public function parseController(string $controller, ?string $module = null): array
    {
        $cacheKey = $this->getCacheKey($controller, $module);
        
        if (isset($this->controllerCache[$cacheKey])) {
            return $this->controllerCache[$cacheKey];
        }

        try {
            $className = $this->resolveControllerClass($controller, $module);
            
            if (!class_exists($className)) {
                throw new AnalysisException("Controller class not found: {$className}");
            }

            $controllerInfo = $this->analyzeControllerClass($className);
            $this->controllerCache[$cacheKey] = $controllerInfo;
            
            return $controllerInfo;
        } catch (\Exception $e) {
            throw new AnalysisException("Failed to parse controller {$controller}: " . $e->getMessage());
        }
    }

    /**
     * 解析控制器方法
     *
     * @param string $controller 控制器名称
     * @param string $action 方法名称
     * @param string|null $module 模块名称
     * @return array
     * @throws AnalysisException
     */
    public function parseControllerAction(string $controller, string $action, ?string $module = null): array
    {
        $controllerInfo = $this->parseController($controller, $module);
        
        if (!isset($controllerInfo['methods'][$action])) {
            throw new AnalysisException("Action {$action} not found in controller {$controller}");
        }

        return $controllerInfo['methods'][$action];
    }

    /**
     * 解析控制器类名
     *
     * @param string $controller 控制器名称
     * @param string|null $module 模块名称
     * @return string
     */
    protected function resolveControllerClass(string $controller, ?string $module = null): string
    {
        // 如果已经是完整的类名（包含命名空间），直接返回
        if (strpos($controller, '\\') !== false) {
            return $controller;
        }

        // 否则按照传统方式构建类名
        $namespace = $this->getControllerNamespace($module);
        $className = $this->formatControllerName($controller);

        return $namespace . '\\' . $className;
    }

    /**
     * 获取控制器命名空间
     *
     * @param string|null $module 模块名称
     * @return string
     */
    protected function getControllerNamespace(?string $module = null): string
    {
        $baseNamespace = $this->app->config->get('app.app_namespace', 'app');
        $controllerLayer = $this->app->config->get('route.controller_layer', 'controller');
        
        if ($module) {
            // 多应用模式
            return $baseNamespace . '\\' . $module . '\\' . $controllerLayer;
        } else {
            // 单应用模式
            return $baseNamespace . '\\' . $controllerLayer;
        }
    }

    /**
     * 格式化控制器名称
     *
     * @param string $controller 控制器名称
     * @return string
     */
    protected function formatControllerName(string $controller): string
    {
        // 转换为驼峰命名
        $controller = str_replace(['_', '-'], ' ', $controller);
        $controller = ucwords($controller);
        $controller = str_replace(' ', '', $controller);
        
        // 添加控制器后缀
        $suffix = $this->app->config->get('route.controller_suffix', false);
        if ($suffix && !str_ends_with($controller, 'Controller')) {
            $controller .= 'Controller';
        }
        
        return $controller;
    }

    /**
     * 分析控制器类
     *
     * @param string $className 类名
     * @return array
     * @throws AnalysisException
     */
    protected function analyzeControllerClass(string $className): array
    {
        try {
            // 使用反射分析器获取基本信息
            $classInfo = $this->reflectionAnalyzer->analyzeClass($className);
            
            // 使用代码分析器获取详细信息
            $codeInfo = $this->codeAnalyzer->analyze($className);
            
            // 合并分析结果
            $controllerInfo = array_merge($classInfo, $codeInfo);
            
            // 过滤出公共方法（API 方法）
            $controllerInfo['api_methods'] = $this->filterApiMethods($controllerInfo['methods']);
            
            // 分析控制器特性
            $controllerInfo['features'] = $this->analyzeControllerFeatures($className);
            
            return $controllerInfo;
        } catch (\Exception $e) {
            throw new AnalysisException("Failed to analyze controller class {$className}: " . $e->getMessage());
        }
    }

    /**
     * 过滤 API 方法
     *
     * @param array $methods 所有方法
     * @return array
     */
    protected function filterApiMethods(array $methods): array
    {
        $apiMethods = [];
        
        foreach ($methods as $methodName => $methodInfo) {
            // 只包含公共方法
            if ($methodInfo['visibility'] !== 'public') {
                continue;
            }
            
            // 排除魔术方法
            if (str_starts_with($methodName, '__')) {
                continue;
            }
            
            // 排除 ThinkPHP 框架方法
            if (in_array($methodName, ['initialize', '_empty', '_initialize'])) {
                continue;
            }
            
            // 排除继承的方法（如果是从 Controller 基类继承）
            if (isset($methodInfo['class']) && isset($methodInfo['declaring_class']) &&
                $methodInfo['class'] !== $methodInfo['declaring_class']) {
                $reflection = new ReflectionClass($methodInfo['class']);
                if ($reflection->isSubclassOf(Controller::class)) {
                    $parentReflection = new ReflectionClass(Controller::class);
                    if ($parentReflection->hasMethod($methodName)) {
                        continue;
                    }
                }
            }
            
            $apiMethods[$methodName] = $methodInfo;
        }
        
        return $apiMethods;
    }

    /**
     * 分析控制器特性
     *
     * @param string $className 类名
     * @return array
     */
    protected function analyzeControllerFeatures(string $className): array
    {
        $features = [
            'is_rest_controller' => false,
            'is_api_controller' => false,
            'base_controller' => null,
            'middleware' => [],
            'traits' => [],
        ];

        try {
            $reflection = new ReflectionClass($className);
            
            // 检查是否为 REST 控制器
            $features['is_rest_controller'] = $this->isRestController($reflection);
            
            // 检查是否为 API 控制器
            $features['is_api_controller'] = $this->isApiController($reflection);
            
            // 获取基础控制器
            $parent = $reflection->getParentClass();
            if ($parent) {
                $features['base_controller'] = $parent->getName();
            }
            
            // 获取使用的 traits
            $features['traits'] = array_keys($reflection->getTraits());
            
            // 分析控制器中间件（如果有定义）
            $features['middleware'] = $this->analyzeControllerMiddleware($reflection);
            
        } catch (\Exception $e) {
            // 忽略分析错误
        }

        return $features;
    }

    /**
     * 检查是否为 REST 控制器
     *
     * @param ReflectionClass $reflection
     * @return bool
     */
    protected function isRestController(ReflectionClass $reflection): bool
    {
        // 检查是否有标准的 REST 方法
        $restMethods = ['index', 'create', 'save', 'read', 'edit', 'update', 'delete'];
        $foundMethods = 0;
        
        foreach ($restMethods as $method) {
            if ($reflection->hasMethod($method)) {
                $methodReflection = $reflection->getMethod($method);
                if ($methodReflection->isPublic() && !$methodReflection->isStatic()) {
                    $foundMethods++;
                }
            }
        }
        
        // 如果有 3 个或以上的 REST 方法，认为是 REST 控制器
        return $foundMethods >= 3;
    }

    /**
     * 检查是否为 API 控制器
     *
     * @param ReflectionClass $reflection
     * @return bool
     */
    protected function isApiController(ReflectionClass $reflection): bool
    {
        $className = $reflection->getName();
        
        // 检查类名是否包含 Api
        if (str_contains($className, 'Api') || str_contains($className, 'API')) {
            return true;
        }
        
        // 检查命名空间是否包含 api
        if (str_contains(strtolower($className), 'api')) {
            return true;
        }
        
        // 检查是否有 API 相关的注释
        $docComment = $reflection->getDocComment();
        if ($docComment && (str_contains($docComment, '@api') || str_contains($docComment, 'API'))) {
            return true;
        }
        
        return false;
    }

    /**
     * 分析控制器中间件
     *
     * @param ReflectionClass $reflection
     * @return array
     */
    protected function analyzeControllerMiddleware(ReflectionClass $reflection): array
    {
        $middleware = [];
        
        // 检查是否有 middleware 属性
        if ($reflection->hasProperty('middleware')) {
            $property = $reflection->getProperty('middleware');
            if ($property->isPublic() || $property->isProtected()) {
                try {
                    $property->setAccessible(true);
                    $instance = $reflection->newInstanceWithoutConstructor();
                    $value = $property->getValue($instance);
                    
                    if (is_array($value)) {
                        $middleware = $value;
                    }
                } catch (\Exception $e) {
                    // 忽略获取失败
                }
            }
        }
        
        return $middleware;
    }

    /**
     * 获取缓存键
     *
     * @param string $controller 控制器名称
     * @param string|null $module 模块名称
     * @return string
     */
    protected function getCacheKey(string $controller, ?string $module = null): string
    {
        return $module ? "{$module}.{$controller}" : $controller;
    }

    /**
     * 清除控制器缓存
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->controllerCache = [];
    }
}
