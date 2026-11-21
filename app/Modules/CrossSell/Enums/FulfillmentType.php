<?php

declare(strict_types=1);

namespace App\Modules\CrossSell\Enums;

enum FulfillmentType: string
{
    case ENTITY = 'entity';
    case VIRTUAL = 'virtual';

    public function label(): string
    {
        return match ($this) {
            self::ENTITY => '实体物流',
            self::VIRTUAL => '虚拟交付',
        };
    }
}
