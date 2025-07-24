<?php

declare(strict_types=1);

namespace app\dto;

/**
 * 用户响应数据传输对象
 */
class UserResponse
{
    /**
     * 用户ID
     */
    public int $id;

    /**
     * 用户名
     */
    public string $name;

    /**
     * 用户邮箱
     */
    public string $email;

    public function __construct(int $id, string $name, string $email)
    {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
    }
}
