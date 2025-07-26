<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Generator;

use Yangweijie\ThinkScramble\Analyzer\MiddlewareAnalyzer;
use Yangweijie\ThinkScramble\Contracts\ConfigInterface;
use Yangweijie\ThinkScramble\Exception\GenerationException;

/**
 * 安全方案生成器
 * 
 * 根据中间件分析结果生成 OpenAPI 安全方案定义
 */
class SecuritySchemeGenerator
{
    /**
     * 配置接口
     */
    protected ConfigInterface $config;

    /**
     * 中间件分析器
     */
    protected MiddlewareAnalyzer $middlewareAnalyzer;

    /**
     * 预定义安全方案
     */
    protected array $predefinedSchemes = [
        'BearerAuth' => [
            'type' => 'http',
            'scheme' => 'bearer',
            'bearerFormat' => 'JWT',
            'description' => 'JWT Bearer Token 认证',
        ],
        'ApiKeyAuth' => [
            'type' => 'apiKey',
            'in' => 'header',
            'name' => 'X-API-Key',
            'description' => 'API Key 认证',
        ],
        'BasicAuth' => [
            'type' => 'http',
            'scheme' => 'basic',
            'description' => 'HTTP Basic 认证',
        ],
        'OAuth2' => [
            'type' => 'oauth2',
            'flows' => [
                'authorizationCode' => [
                    'authorizationUrl' => '/oauth/authorize',
                    'tokenUrl' => '/oauth/token',
                    'scopes' => [
                        'read' => '读取权限',
                        'write' => '写入权限',
                        'admin' => '管理员权限',
                    ],
                ],
            ],
            'description' => 'OAuth2 认证',
        ],
        'SessionAuth' => [
            'type' => 'apiKey',
            'in' => 'cookie',
            'name' => 'PHPSESSID',
            'description' => '会话认证',
        ],
    ];

    /**
     * 构造函数
     *
     * @param ConfigInterface $config
     */
    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
        $this->middlewareAnalyzer = new MiddlewareAnalyzer();
    }

    /**
     * 生成安全方案
     *
     * @param array $controllerClasses 控制器类列表
     * @return array
     * @throws GenerationException
     */
    public function generateSecuritySchemes(array $controllerClasses): array
    {
        try {
            $allSchemes = [];
            $allSecurity = [];

            foreach ($controllerClasses as $controllerClass) {
                $middlewareInfo = $this->middlewareAnalyzer->analyzeController($controllerClass);
                $openApiSecurity = $this->middlewareAnalyzer->generateOpenApiSecurity($middlewareInfo);

                // 合并安全方案
                $allSchemes = array_merge($allSchemes, $openApiSecurity['securitySchemes']);
                
                // 合并安全要求
                foreach ($openApiSecurity['security'] as $security) {
                    if (!in_array($security, $allSecurity)) {
                        $allSecurity[] = $security;
                    }
                }
            }

            // 添加预定义方案
            $allSchemes = array_merge($this->getEnabledPredefinedSchemes(), $allSchemes);

            return [
                'securitySchemes' => $allSchemes,
                'security' => $allSecurity,
            ];

        } catch (\Exception $e) {
            throw new GenerationException("Failed to generate security schemes: " . $e->getMessage());
        }
    }

    /**
     * 为特定控制器方法生成安全要求
     *
     * @param string $controllerClass
     * @param string $methodName
     * @return array
     */
    public function generateMethodSecurity(string $controllerClass, string $methodName): array
    {
        try {
            $middlewareInfo = $this->middlewareAnalyzer->analyzeController($controllerClass);
            $security = [];

            // 类级别中间件
            foreach ($middlewareInfo['class_middleware'] as $middleware) {
                if (!empty($middleware['security'])) {
                    $schemeName = $this->generateSecuritySchemeName($middleware);
                    $security[] = [$schemeName => []];
                }
            }

            // 方法级别中间件
            if (isset($middlewareInfo['method_middleware'][$methodName])) {
                foreach ($middlewareInfo['method_middleware'][$methodName] as $middleware) {
                    if (!empty($middleware['security'])) {
                        $schemeName = $this->generateSecuritySchemeName($middleware);
                        if (!in_array([$schemeName => []], $security)) {
                            $security[] = [$schemeName => []];
                        }
                    }
                }
            }

            return $security;

        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * 生成中间件摘要
     *
     * @param array $controllerClasses
     * @return array
     */
    public function generateMiddlewareSummary(array $controllerClasses): array
    {
        $summary = [
            'total_controllers' => count($controllerClasses),
            'middleware_usage' => [],
            'security_schemes' => [],
            'middleware_types' => [],
            'coverage' => [
                'authentication' => 0,
                'authorization' => 0,
                'rate_limiting' => 0,
                'cors' => 0,
                'csrf' => 0,
            ],
        ];

        foreach ($controllerClasses as $controllerClass) {
            try {
                $middlewareInfo = $this->middlewareAnalyzer->analyzeController($controllerClass);
                $stats = $this->middlewareAnalyzer->getMiddlewareStats($middlewareInfo);

                // 统计中间件使用情况
                $allMiddleware = array_merge(
                    $middlewareInfo['class_middleware'],
                    ...array_values($middlewareInfo['method_middleware'])
                );

                foreach ($allMiddleware as $middleware) {
                    $name = $middleware['name'];
                    $type = $middleware['type'];
                    
                    $summary['middleware_usage'][$name] = ($summary['middleware_usage'][$name] ?? 0) + 1;
                    $summary['middleware_types'][$type] = ($summary['middleware_types'][$type] ?? 0) + 1;

                    // 统计覆盖率
                    if (isset($summary['coverage'][$type])) {
                        $summary['coverage'][$type]++;
                    }
                }

                // 统计安全方案
                foreach ($middlewareInfo['security_schemes'] as $schemeName => $scheme) {
                    $summary['security_schemes'][$schemeName] = $scheme;
                }

            } catch (\Exception $e) {
                // 忽略分析错误，继续处理其他控制器
                continue;
            }
        }

        // 计算覆盖率百分比
        foreach ($summary['coverage'] as $type => $count) {
            $summary['coverage'][$type] = [
                'count' => $count,
                'percentage' => round(($count / $summary['total_controllers']) * 100, 2),
            ];
        }

        return $summary;
    }

    /**
     * 获取启用的预定义方案
     *
     * @return array
     */
    protected function getEnabledPredefinedSchemes(): array
    {
        $enabled = $this->config->get('security.enabled_schemes', ['BearerAuth']);
        $schemes = [];

        foreach ($enabled as $schemeName) {
            if (isset($this->predefinedSchemes[$schemeName])) {
                $schemes[$schemeName] = $this->predefinedSchemes[$schemeName];
            }
        }

        return $schemes;
    }

    /**
     * 生成安全方案名称
     *
     * @param array $middleware
     * @return string
     */
    protected function generateSecuritySchemeName(array $middleware): string
    {
        $type = $middleware['type'] ?? 'custom';
        
        $mapping = [
            'authentication' => 'BearerAuth',
            'authorization' => 'BearerAuth',
            'session' => 'SessionAuth',
            'api_key' => 'ApiKeyAuth',
            'oauth2' => 'OAuth2',
            'basic' => 'BasicAuth',
        ];

        return $mapping[$type] ?? 'CustomAuth';
    }

    /**
     * 生成安全方案文档
     *
     * @param array $securitySchemes
     * @return string
     */
    public function generateSecurityDocumentation(array $securitySchemes): string
    {
        $doc = "# API 安全方案\n\n";
        $doc .= "本 API 使用以下安全方案进行认证和授权：\n\n";

        foreach ($securitySchemes as $schemeName => $scheme) {
            $doc .= "## {$schemeName}\n\n";
            $doc .= "**类型**: {$scheme['type']}\n\n";
            
            if (isset($scheme['description'])) {
                $doc .= "**描述**: {$scheme['description']}\n\n";
            }

            switch ($scheme['type']) {
                case 'http':
                    $doc .= "**方案**: {$scheme['scheme']}\n\n";
                    if (isset($scheme['bearerFormat'])) {
                        $doc .= "**Bearer 格式**: {$scheme['bearerFormat']}\n\n";
                    }
                    break;

                case 'apiKey':
                    $doc .= "**位置**: {$scheme['in']}\n\n";
                    $doc .= "**参数名**: {$scheme['name']}\n\n";
                    break;

                case 'oauth2':
                    $doc .= "**授权流程**:\n\n";
                    foreach ($scheme['flows'] as $flowType => $flow) {
                        $doc .= "- **{$flowType}**:\n";
                        if (isset($flow['authorizationUrl'])) {
                            $doc .= "  - 授权 URL: {$flow['authorizationUrl']}\n";
                        }
                        if (isset($flow['tokenUrl'])) {
                            $doc .= "  - Token URL: {$flow['tokenUrl']}\n";
                        }
                        if (isset($flow['scopes'])) {
                            $doc .= "  - 权限范围:\n";
                            foreach ($flow['scopes'] as $scope => $description) {
                                $doc .= "    - `{$scope}`: {$description}\n";
                            }
                        }
                    }
                    $doc .= "\n";
                    break;
            }

            $doc .= "---\n\n";
        }

        return $doc;
    }

    /**
     * 验证安全配置
     *
     * @param array $securityConfig
     * @return array
     */
    public function validateSecurityConfig(array $securityConfig): array
    {
        $errors = [];
        $warnings = [];

        // 检查是否有安全方案
        if (empty($securityConfig['securitySchemes'])) {
            $warnings[] = '未检测到任何安全方案，API 可能缺乏安全保护';
        }

        // 检查常见安全问题
        foreach ($securityConfig['securitySchemes'] as $schemeName => $scheme) {
            switch ($scheme['type']) {
                case 'http':
                    if ($scheme['scheme'] === 'basic') {
                        $warnings[] = "安全方案 '{$schemeName}' 使用 Basic 认证，建议使用更安全的方案";
                    }
                    break;

                case 'apiKey':
                    if ($scheme['in'] === 'query') {
                        $warnings[] = "安全方案 '{$schemeName}' 在查询参数中传递 API Key，存在安全风险";
                    }
                    break;
            }
        }

        // 检查是否有全局安全要求
        if (empty($securityConfig['security'])) {
            $warnings[] = '未设置全局安全要求，某些端点可能不受保护';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }
}
