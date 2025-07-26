<?php

declare(strict_types=1);

namespace app\validate;

use think\Validate;

/**
 * 用户验证器示例
 * 
 * 展示如何定义验证规则，用于注解分析
 */
class UserValidate extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'name' => 'require|length:2,50|chsAlphaNum',
        'email' => 'require|email|unique:user',
        'password' => 'require|length:6,20|alphaNum',
        'age' => 'number|between:1,120',
        'phone' => 'mobile',
        'status' => 'in:0,1',
        'avatar' => 'file|fileExt:jpg,png,gif|fileSize:2097152',
        'page' => 'number|min:1',
        'limit' => 'number|between:1,100',
        'keyword' => 'length:1,50',
        'action' => 'require|in:delete,disable,enable',
        'ids' => 'require|array',
        'period' => 'in:day,week,month,year',
        'format' => 'require|in:csv,excel,json',
        'fields' => 'length:1,200',
    ];

    /**
     * 验证消息
     */
    protected $message = [
        'name.require' => '用户名不能为空',
        'name.length' => '用户名长度必须在2-50个字符之间',
        'name.chsAlphaNum' => '用户名只能包含中文、字母和数字',
        'email.require' => '邮箱不能为空',
        'email.email' => '邮箱格式不正确',
        'email.unique' => '邮箱已存在',
        'password.require' => '密码不能为空',
        'password.length' => '密码长度必须在6-20个字符之间',
        'password.alphaNum' => '密码只能包含字母和数字',
        'age.number' => '年龄必须是数字',
        'age.between' => '年龄必须在1-120之间',
        'phone.mobile' => '手机号格式不正确',
        'status.in' => '状态值不正确',
        'avatar.file' => '头像必须是文件',
        'avatar.fileExt' => '头像只支持jpg、png、gif格式',
        'avatar.fileSize' => '头像大小不能超过2MB',
        'page.number' => '页码必须是数字',
        'page.min' => '页码不能小于1',
        'limit.number' => '每页数量必须是数字',
        'limit.between' => '每页数量必须在1-100之间',
        'keyword.length' => '搜索关键词长度不能超过50个字符',
        'action.require' => '操作类型不能为空',
        'action.in' => '不支持的操作类型',
        'ids.require' => '用户ID不能为空',
        'ids.array' => '用户ID必须是数组',
        'period.in' => '统计周期不正确',
        'format.require' => '导出格式不能为空',
        'format.in' => '不支持的导出格式',
        'fields.length' => '导出字段长度不能超过200个字符',
    ];

    /**
     * 字段描述
     */
    protected $field = [
        'name' => '用户名',
        'email' => '邮箱地址',
        'password' => '密码',
        'age' => '年龄',
        'phone' => '手机号',
        'status' => '状态',
        'avatar' => '头像',
        'page' => '页码',
        'limit' => '每页数量',
        'keyword' => '搜索关键词',
        'action' => '操作类型',
        'ids' => '用户ID列表',
        'period' => '统计周期',
        'format' => '导出格式',
        'fields' => '导出字段',
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'list' => ['page', 'limit', 'keyword'],
        'create' => ['name', 'email', 'password', 'age', 'phone'],
        'update' => ['name', 'email', 'age', 'phone'],
        'batch' => ['action', 'ids'],
        'avatar' => ['avatar'],
        'export' => ['format', 'fields'],
        'stats' => ['period'],
    ];

    /**
     * 自定义验证规则：检查用户名是否包含敏感词
     */
    protected function checkSensitiveWords($value, $rule, $data = [])
    {
        $sensitiveWords = ['admin', 'root', 'test', 'demo'];
        
        foreach ($sensitiveWords as $word) {
            if (stripos($value, $word) !== false) {
                return '用户名不能包含敏感词汇';
            }
        }
        
        return true;
    }

    /**
     * 自定义验证规则：检查密码强度
     */
    protected function checkPasswordStrength($value, $rule, $data = [])
    {
        // 至少包含一个字母和一个数字
        if (!preg_match('/^(?=.*[a-zA-Z])(?=.*\d).+$/', $value)) {
            return '密码必须包含至少一个字母和一个数字';
        }
        
        return true;
    }

    /**
     * 自定义验证规则：检查邮箱域名
     */
    protected function checkEmailDomain($value, $rule, $data = [])
    {
        $allowedDomains = ['gmail.com', 'qq.com', '163.com', 'sina.com'];
        $domain = substr(strrchr($value, '@'), 1);
        
        if (!in_array($domain, $allowedDomains)) {
            return '邮箱域名不在允许列表中';
        }
        
        return true;
    }

    /**
     * 批量验证场景的自定义验证
     */
    protected function sceneBatch()
    {
        return $this->only(['action', 'ids'])
                   ->append('action', 'checkBatchAction')
                   ->append('ids', 'checkBatchIds');
    }

    /**
     * 检查批量操作类型
     */
    protected function checkBatchAction($value, $rule, $data = [])
    {
        $allowedActions = ['delete', 'disable', 'enable'];
        
        if (!in_array($value, $allowedActions)) {
            return '不支持的批量操作类型';
        }
        
        return true;
    }

    /**
     * 检查批量操作的ID列表
     */
    protected function checkBatchIds($value, $rule, $data = [])
    {
        if (!is_array($value) || empty($value)) {
            return '用户ID列表不能为空';
        }
        
        if (count($value) > 100) {
            return '单次批量操作不能超过100个用户';
        }
        
        foreach ($value as $id) {
            if (!is_numeric($id) || $id <= 0) {
                return '用户ID必须是正整数';
            }
        }
        
        return true;
    }

    /**
     * 导出场景的自定义验证
     */
    protected function sceneExport()
    {
        return $this->only(['format', 'fields'])
                   ->append('format', 'checkExportFormat')
                   ->append('fields', 'checkExportFields');
    }

    /**
     * 检查导出格式
     */
    protected function checkExportFormat($value, $rule, $data = [])
    {
        $allowedFormats = ['csv', 'excel', 'json'];
        
        if (!in_array($value, $allowedFormats)) {
            return '不支持的导出格式';
        }
        
        return true;
    }

    /**
     * 检查导出字段
     */
    protected function checkExportFields($value, $rule, $data = [])
    {
        if (empty($value)) {
            return true; // 可选字段
        }
        
        $allowedFields = ['id', 'name', 'email', 'age', 'phone', 'status', 'created_at', 'updated_at'];
        $fields = explode(',', $value);
        
        foreach ($fields as $field) {
            $field = trim($field);
            if (!in_array($field, $allowedFields)) {
                return "不支持导出字段: {$field}";
            }
        }
        
        return true;
    }
}
