<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Adapter;

use think\App;
use think\Validate;
use ReflectionClass;
use Yangweijie\ThinkScramble\Exception\AnalysisException;

/**
 * ThinkPHP 验证器集成
 * 
 * 分析 ThinkPHP 验证器规则并转换为 OpenAPI 参数约束
 */
class ValidatorIntegration
{
    /**
     * ThinkPHP 应用实例
     */
    protected App $app;

    /**
     * 验证器缓存
     */
    protected array $validatorCache = [];

    /**
     * 规则类型映射
     */
    protected array $ruleTypeMapping = [
        'require' => 'required',
        'number' => 'integer',
        'integer' => 'integer',
        'float' => 'number',
        'boolean' => 'boolean',
        'email' => 'string',
        'url' => 'string',
        'ip' => 'string',
        'date' => 'string',
        'dateFormat' => 'string',
        'alpha' => 'string',
        'alphaNum' => 'string',
        'alphaDash' => 'string',
        'chs' => 'string',
        'chsAlpha' => 'string',
        'chsAlphaNum' => 'string',
        'chsDash' => 'string',
        'mobile' => 'string',
        'idCard' => 'string',
        'zip' => 'string',
    ];

    /**
     * 构造函数
     *
     * @param App|null $app ThinkPHP 应用实例
     */
    public function __construct(?App $app = null)
    {
        $this->app = $app ?: new App();
    }

    /**
     * 分析验证器类
     *
     * @param string $validatorClass 验证器类名
     * @return array
     * @throws AnalysisException
     */
    public function analyzeValidator(string $validatorClass): array
    {
        if (isset($this->validatorCache[$validatorClass])) {
            return $this->validatorCache[$validatorClass];
        }

        try {
            if (!class_exists($validatorClass)) {
                throw new AnalysisException("Validator class not found: {$validatorClass}");
            }

            $reflection = new ReflectionClass($validatorClass);
            $validator = $reflection->newInstance();

            if (!$validator instanceof Validate) {
                throw new AnalysisException("Class {$validatorClass} is not a ThinkPHP validator");
            }

            $result = [
                'class' => $validatorClass,
                'rules' => $this->extractRules($validator),
                'messages' => $this->extractMessages($validator),
                'scenes' => $this->extractScenes($validator),
                'openapi_parameters' => [],
            ];

            // 转换为 OpenAPI 参数
            $result['openapi_parameters'] = $this->convertToOpenApiParameters($result['rules']);

            $this->validatorCache[$validatorClass] = $result;
            return $result;

        } catch (\Exception $e) {
            throw new AnalysisException("Failed to analyze validator {$validatorClass}: " . $e->getMessage());
        }
    }

    /**
     * 分析验证规则数组
     *
     * @param array $rules 验证规则
     * @return array
     */
    public function analyzeRules(array $rules): array
    {
        $result = [
            'rules' => $rules,
            'openapi_parameters' => $this->convertToOpenApiParameters($rules),
        ];

        return $result;
    }

    /**
     * 提取验证器规则
     *
     * @param Validate $validator 验证器实例
     * @return array
     */
    protected function extractRules(Validate $validator): array
    {
        $rules = [];

        // 尝试获取 rule 属性
        $reflection = new ReflectionClass($validator);
        
        if ($reflection->hasProperty('rule')) {
            $property = $reflection->getProperty('rule');
            $property->setAccessible(true);
            $rules = $property->getValue($validator) ?: [];
        }

        return $rules;
    }

    /**
     * 提取验证器消息
     *
     * @param Validate $validator 验证器实例
     * @return array
     */
    protected function extractMessages(Validate $validator): array
    {
        $messages = [];

        $reflection = new ReflectionClass($validator);
        
        if ($reflection->hasProperty('message')) {
            $property = $reflection->getProperty('message');
            $property->setAccessible(true);
            $messages = $property->getValue($validator) ?: [];
        }

        return $messages;
    }

    /**
     * 提取验证器场景
     *
     * @param Validate $validator 验证器实例
     * @return array
     */
    protected function extractScenes(Validate $validator): array
    {
        $scenes = [];

        $reflection = new ReflectionClass($validator);
        
        if ($reflection->hasProperty('scene')) {
            $property = $reflection->getProperty('scene');
            $property->setAccessible(true);
            $scenes = $property->getValue($validator) ?: [];
        }

        return $scenes;
    }

    /**
     * 转换为 OpenAPI 参数
     *
     * @param array $rules 验证规则
     * @return array
     */
    protected function convertToOpenApiParameters(array $rules): array
    {
        $parameters = [];

        foreach ($rules as $field => $rule) {
            $parameter = $this->convertFieldRule($field, $rule);
            if ($parameter) {
                $parameters[] = $parameter;
            }
        }

        return $parameters;
    }

    /**
     * 转换字段规则
     *
     * @param string $field 字段名
     * @param string|array $rule 规则
     * @return array|null
     */
    protected function convertFieldRule(string $field, $rule): ?array
    {
        $parameter = [
            'name' => $field,
            'in' => 'query', // 默认为查询参数，可根据实际情况调整
            'required' => false,
            'schema' => [
                'type' => 'string', // 默认类型
            ],
            'description' => '',
        ];

        // 解析规则
        $ruleList = $this->parseRule($rule);
        
        foreach ($ruleList as $ruleName => $ruleValue) {
            $this->applyRule($parameter, $ruleName, $ruleValue);
        }

        return $parameter;
    }

    /**
     * 解析规则
     *
     * @param string|array $rule 规则
     * @return array
     */
    protected function parseRule($rule): array
    {
        if (is_string($rule)) {
            return $this->parseStringRule($rule);
        }

        if (is_array($rule)) {
            return $rule;
        }

        return [];
    }

    /**
     * 解析字符串规则
     *
     * @param string $rule 规则字符串
     * @return array
     */
    protected function parseStringRule(string $rule): array
    {
        $rules = [];
        $parts = explode('|', $rule);

        foreach ($parts as $part) {
            if (str_contains($part, ':')) {
                [$name, $value] = explode(':', $part, 2);
                $rules[$name] = $value;
            } else {
                $rules[$part] = true;
            }
        }

        return $rules;
    }

    /**
     * 应用规则到参数
     *
     * @param array &$parameter 参数数组
     * @param string $ruleName 规则名称
     * @param mixed $ruleValue 规则值
     * @return void
     */
    protected function applyRule(array &$parameter, string $ruleName, $ruleValue): void
    {
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

            case 'dateFormat':
                $parameter['schema']['type'] = 'string';
                $parameter['schema']['format'] = 'date-time';
                break;

            case 'min':
                if ($parameter['schema']['type'] === 'string') {
                    $parameter['schema']['minLength'] = (int) $ruleValue;
                } else {
                    $parameter['schema']['minimum'] = (int) $ruleValue;
                }
                break;

            case 'max':
                if ($parameter['schema']['type'] === 'string') {
                    $parameter['schema']['maxLength'] = (int) $ruleValue;
                } else {
                    $parameter['schema']['maximum'] = (int) $ruleValue;
                }
                break;

            case 'length':
                if (str_contains($ruleValue, ',')) {
                    [$min, $max] = explode(',', $ruleValue);
                    $parameter['schema']['minLength'] = (int) $min;
                    $parameter['schema']['maxLength'] = (int) $max;
                } else {
                    $parameter['schema']['minLength'] = (int) $ruleValue;
                    $parameter['schema']['maxLength'] = (int) $ruleValue;
                }
                break;

            case 'in':
                $parameter['schema']['enum'] = explode(',', $ruleValue);
                break;

            case 'between':
                [$min, $max] = explode(',', $ruleValue);
                if ($parameter['schema']['type'] === 'string') {
                    $parameter['schema']['minLength'] = (int) $min;
                    $parameter['schema']['maxLength'] = (int) $max;
                } else {
                    $parameter['schema']['minimum'] = (int) $min;
                    $parameter['schema']['maximum'] = (int) $max;
                }
                break;

            case 'regex':
                $parameter['schema']['pattern'] = $ruleValue;
                break;
        }
    }

    /**
     * 根据场景过滤参数
     *
     * @param array $parameters 参数列表
     * @param string $scene 场景名称
     * @param array $scenes 场景配置
     * @return array
     */
    public function filterParametersByScene(array $parameters, string $scene, array $scenes): array
    {
        if (!isset($scenes[$scene])) {
            return $parameters;
        }

        $sceneFields = $scenes[$scene];
        $filteredParameters = [];

        foreach ($parameters as $parameter) {
            if (in_array($parameter['name'], $sceneFields)) {
                $filteredParameters[] = $parameter;
            }
        }

        return $filteredParameters;
    }

    /**
     * 清除验证器缓存
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->validatorCache = [];
    }
}
