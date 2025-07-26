<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Analyzer;

use ReflectionClass;
use ReflectionMethod;
use Yangweijie\ThinkScramble\Exception\AnalysisException;

/**
 * 验证器注解分析器
 * 
 * 分析控制器方法中的验证注解，提取验证规则用于 OpenAPI 文档生成
 */
class ValidateAnnotationAnalyzer
{
    /**
     * 注解解析器
     */
    protected AnnotationParser $annotationParser;

    /**
     * 内置验证规则映射
     */
    protected array $ruleMapping = [
        'require' => ['required' => true],
        'number' => ['type' => 'number'],
        'integer' => ['type' => 'integer'],
        'float' => ['type' => 'number'],
        'boolean' => ['type' => 'boolean'],
        'email' => ['type' => 'string', 'format' => 'email'],
        'url' => ['type' => 'string', 'format' => 'uri'],
        'date' => ['type' => 'string', 'format' => 'date'],
        'dateFormat' => ['type' => 'string', 'format' => 'date-time'],
        'alpha' => ['type' => 'string', 'pattern' => '^[a-zA-Z]+$'],
        'alphaNum' => ['type' => 'string', 'pattern' => '^[a-zA-Z0-9]+$'],
        'alphaDash' => ['type' => 'string', 'pattern' => '^[a-zA-Z0-9_-]+$'],
        'chs' => ['type' => 'string', 'description' => '中文字符'],
        'chsAlpha' => ['type' => 'string', 'description' => '中文字符和字母'],
        'chsAlphaNum' => ['type' => 'string', 'description' => '中文字符、字母和数字'],
        'chsDash' => ['type' => 'string', 'description' => '中文字符、字母、数字和下划线_及破折号-'],
        'mobile' => ['type' => 'string', 'pattern' => '^1[3-9]\d{9}$', 'description' => '手机号码'],
        'idCard' => ['type' => 'string', 'description' => '身份证号码'],
        'zip' => ['type' => 'string', 'pattern' => '^\d{6}$', 'description' => '邮政编码'],
    ];

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->annotationParser = new AnnotationParser();
    }

    /**
     * 分析方法的验证注解
     *
     * @param ReflectionMethod $method
     * @return array
     */
    public function analyzeMethod(ReflectionMethod $method): array
    {
        $methodAnnotations = $this->annotationParser->parseMethodAnnotations($method);
        $validateInfo = [];

        foreach ($methodAnnotations['validate'] as $validateAnnotation) {
            $parsed = $validateAnnotation['parsed'] ?? [];
            
            if (!empty($parsed['class'])) {
                $validateInfo[] = $this->analyzeValidateClass($parsed);
            }
        }

        return [
            'method' => $method->getName(),
            'class' => $method->getDeclaringClass()->getName(),
            'validate_annotations' => $validateInfo,
            'openapi_parameters' => $this->generateOpenApiParameters($validateInfo),
        ];
    }

    /**
     * 分析验证器类
     *
     * @param array $validateConfig
     * @return array
     */
    protected function analyzeValidateClass(array $validateConfig): array
    {
        $className = $validateConfig['class'];
        $scene = $validateConfig['scene'] ?? '';
        
        $validateInfo = [
            'class' => $className,
            'scene' => $scene,
            'batch' => $validateConfig['batch'] ?? false,
            'rules' => [],
            'messages' => [],
            'fields' => [],
        ];

        try {
            // 尝试加载验证器类
            if (class_exists($className)) {
                $validateInfo = array_merge($validateInfo, $this->extractValidateRules($className, $scene));
            } else {
                // 尝试从应用目录加载
                $appClassName = 'app\\validate\\' . $className;
                if (class_exists($appClassName)) {
                    $validateInfo = array_merge($validateInfo, $this->extractValidateRules($appClassName, $scene));
                }
            }
        } catch (\Exception $e) {
            // 如果无法加载验证器类，记录错误但继续
            $validateInfo['error'] = $e->getMessage();
        }

        return $validateInfo;
    }

    /**
     * 提取验证器规则
     *
     * @param string $className
     * @param string $scene
     * @return array
     */
    protected function extractValidateRules(string $className, string $scene = ''): array
    {
        try {
            $reflection = new ReflectionClass($className);
            $rules = [];
            $messages = [];
            $fields = [];

            // 获取规则属性
            if ($reflection->hasProperty('rule')) {
                $ruleProperty = $reflection->getProperty('rule');
                $ruleProperty->setAccessible(true);
                $rules = $ruleProperty->getStaticValue() ?? [];
            }

            // 获取消息属性
            if ($reflection->hasProperty('message')) {
                $messageProperty = $reflection->getProperty('message');
                $messageProperty->setAccessible(true);
                $messages = $messageProperty->getStaticValue() ?? [];
            }

            // 获取字段属性
            if ($reflection->hasProperty('field')) {
                $fieldProperty = $reflection->getProperty('field');
                $fieldProperty->setAccessible(true);
                $fields = $fieldProperty->getStaticValue() ?? [];
            }

            // 如果指定了场景，过滤规则
            if (!empty($scene) && $reflection->hasProperty('scene')) {
                $sceneProperty = $reflection->getProperty('scene');
                $sceneProperty->setAccessible(true);
                $scenes = $sceneProperty->getStaticValue() ?? [];
                
                if (isset($scenes[$scene])) {
                    $sceneFields = $scenes[$scene];
                    $rules = array_intersect_key($rules, array_flip($sceneFields));
                }
            }

            return [
                'rules' => $rules,
                'messages' => $messages,
                'fields' => $fields,
            ];

        } catch (\Exception $e) {
            return [
                'rules' => [],
                'messages' => [],
                'fields' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * 生成 OpenAPI 参数
     *
     * @param array $validateInfo
     * @return array
     */
    protected function generateOpenApiParameters(array $validateInfo): array
    {
        $parameters = [];

        foreach ($validateInfo as $validate) {
            $rules = $validate['rules'] ?? [];
            $messages = $validate['messages'] ?? [];
            $fields = $validate['fields'] ?? [];

            foreach ($rules as $field => $rule) {
                $parameter = $this->convertRuleToOpenApiParameter($field, $rule, $messages, $fields);
                if ($parameter) {
                    $parameters[] = $parameter;
                }
            }
        }

        return $parameters;
    }

    /**
     * 将验证规则转换为 OpenAPI 参数
     *
     * @param string $field
     * @param mixed $rule
     * @param array $messages
     * @param array $fields
     * @return array|null
     */
    protected function convertRuleToOpenApiParameter(string $field, $rule, array $messages, array $fields): ?array
    {
        if (is_string($rule)) {
            $rules = explode('|', $rule);
        } elseif (is_array($rule)) {
            $rules = $rule;
        } else {
            return null;
        }

        $parameter = [
            'name' => $field,
            'in' => 'query',
            'required' => false,
            'description' => $fields[$field] ?? $field,
            'schema' => ['type' => 'string'],
        ];

        foreach ($rules as $singleRule) {
            $this->applyRuleToParameter($parameter, $singleRule);
        }

        return $parameter;
    }

    /**
     * 应用单个规则到参数
     *
     * @param array &$parameter
     * @param string $rule
     */
    protected function applyRuleToParameter(array &$parameter, string $rule): void
    {
        // 解析规则和参数
        if (strpos($rule, ':') !== false) {
            [$ruleName, $ruleValue] = explode(':', $rule, 2);
        } else {
            $ruleName = $rule;
            $ruleValue = '';
        }

        // 应用规则
        switch ($ruleName) {
            case 'require':
                $parameter['required'] = true;
                break;

            case 'number':
            case 'integer':
                $parameter['schema']['type'] = 'integer';
                break;

            case 'float':
                $parameter['schema']['type'] = 'number';
                break;

            case 'boolean':
                $parameter['schema']['type'] = 'boolean';
                break;

            case 'email':
                $parameter['schema']['type'] = 'string';
                $parameter['schema']['format'] = 'email';
                break;

            case 'url':
                $parameter['schema']['type'] = 'string';
                $parameter['schema']['format'] = 'uri';
                break;

            case 'date':
                $parameter['schema']['type'] = 'string';
                $parameter['schema']['format'] = 'date';
                break;

            case 'length':
                if (strpos($ruleValue, ',') !== false) {
                    [$min, $max] = explode(',', $ruleValue);
                    $parameter['schema']['minLength'] = (int)$min;
                    $parameter['schema']['maxLength'] = (int)$max;
                } else {
                    $parameter['schema']['maxLength'] = (int)$ruleValue;
                }
                break;

            case 'min':
                if ($parameter['schema']['type'] === 'string') {
                    $parameter['schema']['minLength'] = (int)$ruleValue;
                } else {
                    $parameter['schema']['minimum'] = (int)$ruleValue;
                }
                break;

            case 'max':
                if ($parameter['schema']['type'] === 'string') {
                    $parameter['schema']['maxLength'] = (int)$ruleValue;
                } else {
                    $parameter['schema']['maximum'] = (int)$ruleValue;
                }
                break;

            case 'between':
                if (strpos($ruleValue, ',') !== false) {
                    [$min, $max] = explode(',', $ruleValue);
                    if ($parameter['schema']['type'] === 'string') {
                        $parameter['schema']['minLength'] = (int)$min;
                        $parameter['schema']['maxLength'] = (int)$max;
                    } else {
                        $parameter['schema']['minimum'] = (int)$min;
                        $parameter['schema']['maximum'] = (int)$max;
                    }
                }
                break;

            case 'in':
                $parameter['schema']['enum'] = explode(',', $ruleValue);
                break;

            case 'regex':
                $parameter['schema']['pattern'] = $ruleValue;
                break;

            case 'mobile':
                $parameter['schema']['pattern'] = '^1[3-9]\d{9}$';
                $parameter['description'] .= ' (手机号码格式)';
                break;

            default:
                // 检查是否为自定义规则映射
                if (isset($this->ruleMapping[$ruleName])) {
                    $mapping = $this->ruleMapping[$ruleName];
                    $parameter['schema'] = array_merge($parameter['schema'], $mapping);
                }
                break;
        }
    }

    /**
     * 分析控制器的所有验证注解
     *
     * @param string $className
     * @return array
     * @throws AnalysisException
     */
    public function analyzeController(string $className): array
    {
        try {
            if (!class_exists($className)) {
                throw new AnalysisException("Class {$className} not found");
            }

            $reflection = new ReflectionClass($className);
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
            $validateInfo = [];

            foreach ($methods as $method) {
                if ($this->shouldSkipMethod($method)) {
                    continue;
                }

                $methodValidate = $this->analyzeMethod($method);
                if (!empty($methodValidate['validate_annotations'])) {
                    $validateInfo[$method->getName()] = $methodValidate;
                }
            }

            return [
                'class' => $className,
                'methods' => $validateInfo,
            ];

        } catch (\Exception $e) {
            throw new AnalysisException("Failed to analyze controller {$className}: " . $e->getMessage());
        }
    }

    /**
     * 检查是否应该跳过方法
     *
     * @param ReflectionMethod $method
     * @return bool
     */
    protected function shouldSkipMethod(ReflectionMethod $method): bool
    {
        $methodName = $method->getName();

        // 跳过魔术方法
        if (str_starts_with($methodName, '__')) {
            return true;
        }

        // 跳过 ThinkPHP 框架方法
        if (in_array($methodName, ['initialize', '_empty', '_initialize'])) {
            return true;
        }

        return false;
    }
}
