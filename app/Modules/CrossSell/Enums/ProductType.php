<?php

declare(strict_types=1);

namespace App\Modules\CrossSell\Enums;

enum ProductType: string
{
    case ENTITY = 'entity';
    case VIRTUAL = 'virtual';
    case BUNDLE = 'bundle';

    public function label(): string
    {
        return match ($this) {
            self::ENTITY => '实体商品',
            self::VIRTUAL => '虚拟商品',
            self::BUNDLE => '套餐',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ENTITY => 'success',
            self::VIRTUAL => 'info',
            self::BUNDLE => 'warning',
        };
    }

    public function isVirtual(): bool
    {
        return $this === self::VIRTUAL;
    }

    public function isBundle(): bool
    {
        return $this === self::BUNDLE;
    }
}
