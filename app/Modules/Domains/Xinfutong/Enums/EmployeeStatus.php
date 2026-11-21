<?php

declare(strict_types=1);

namespace App\Modules\Domains\Xinfutong\Enums;

enum EmployeeStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case RESIGNED = 'resigned';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => '在职',
            self::INACTIVE => '停用',
            self::RESIGNED => '离职',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::INACTIVE => 'warning',
            self::RESIGNED => 'danger',
        };
    }
}
