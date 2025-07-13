<?php

declare(strict_types=1);

namespace Yangweijie\ThinkScramble\Exception;

use Exception;

/**
 * Scramble 基础异常类
 * 
 * 所有 Scramble 相关异常的基类
 */
class ScrambleException extends Exception
{
    /**
     * 创建异常实例
     *
     * @param string $message 异常消息
     * @param int $code 异常代码
     * @param Exception|null $previous 前一个异常
     */
    public function __construct(string $message = '', int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * 获取格式化的异常信息
     *
     * @return array
     */
    public function getFormattedError(): array
    {
        return [
            'error' => static::class,
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
        ];
    }
}
