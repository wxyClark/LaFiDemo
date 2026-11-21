<?php

declare(strict_types=1);

namespace App\Modules\Domains\User\Enums;

enum CustomerStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case BANNED = 'banned';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => '正常',
            self::INACTIVE => '未激活',
            self::BANNED => '已封禁',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::INACTIVE => 'warning',
            self::BANNED => 'danger',
        };
    }
}
