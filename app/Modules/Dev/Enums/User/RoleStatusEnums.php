<?php

namespace App\Modules\Dev\Enums\User;

enum RoleStatusEnums
{
    const ROLE_STATUS_ENABLE = 1;
    const ROLE_STATUS_DISABLE = 2;

    const ROLE_STATUS_MAP = [
        self::ROLE_STATUS_ENABLE => '启用',
        self::ROLE_STATUS_DISABLE => '禁用',
    ];

    /**
     * 获取状态描述
     * @param $value
     * @return string
     * @author wxyClark
     * @create 2025/11/29 15:34
     *
     * @version 1.0
     */
    public static function getDescription($value): string
    {
        return self::ROLE_STATUS_MAP[$value] ?? '-';
    }
}
