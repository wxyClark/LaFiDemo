<?php

declare(strict_types=1);

namespace App\Modules\Domains\Auth\Enums;

enum AdminRole: string
{
    case SUPER_ADMIN = 'super-admin';
    case ADMIN = 'admin';
    case EDITOR = 'editor';

    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => '超级管理员',
            self::ADMIN => '管理员',
            self::EDITOR => '编辑',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'danger',
            self::ADMIN => 'primary',
            self::EDITOR => 'warning',
        };
    }
}
