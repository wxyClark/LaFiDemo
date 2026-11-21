<?php

declare(strict_types=1);

namespace App\Modules\CrossSell\Enums;

enum PaymentType: string
{
    case PAY = 'pay';
    case RECHARGE = 'recharge';
    case REFUND = 'refund';

    public function label(): string
    {
        return match ($this) {
            self::PAY => '支付',
            self::RECHARGE => '充值',
            self::REFUND => '退款',
        };
    }
}
