<?php

declare(strict_types=1);

namespace App\Modules\Domains\Catalog\Enums;

enum ProductStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => '上架',
            self::INACTIVE => '下架',
        };
    }
}
