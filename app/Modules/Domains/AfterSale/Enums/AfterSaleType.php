<?php

declare(strict_types=1);

namespace App\Modules\Domains\AfterSale\Enums;

enum AfterSaleType: string
{
    case REFUND = 'refund';
    case RETURN = 'return';

    public function label(): string
    {
        return match ($this) {
            self::REFUND => '仅退款',
            self::RETURN => '退货退款',
        };
    }
}
