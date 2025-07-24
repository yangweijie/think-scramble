<?php

declare(strict_types=1);

namespace app\dto;

/**
 * API 响应数据传输对象
 */
class ApiResponse
{
    /**
     * 响应状态码
     */
    public int $code;

    /**
     * 响应消息
     */
    public string $message;

    /**
     * 响应数据
     */
    public mixed $data;

    public function __construct(int $code, string $message, mixed $data = null)
    {
        $this->code = $code;
        $this->message = $message;
        $this->data = $data;
    }
}
