<?php

declare(strict_types=1);

namespace App\Modules\CrossSell\Enums;

enum FulfillmentStatus: string
{
    case PENDING = 'pending';
    case DELIVERED = 'delivered';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => '待发货',
            self::DELIVERED => '已发货',
        };
    }
}
