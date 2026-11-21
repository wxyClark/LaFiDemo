<?php

declare(strict_types=1);

namespace App\Modules\Domains\Catalog\Enums;

enum ProductType: string
{
    case ENTITY = 'entity';
    case VIRTUAL = 'virtual';

    public function label(): string
    {
        return match ($this) {
            self::ENTITY => '实体商品',
            self::VIRTUAL => '虚拟商品',
        };
    }

    public function isVirtual(): bool
    {
        return $this === self::VIRTUAL;
    }
}
