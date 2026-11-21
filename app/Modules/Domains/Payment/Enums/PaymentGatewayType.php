<?php

declare(strict_types=1);

namespace App\Modules\Domains\Payment\Enums;

enum PaymentGatewayType: string
{
    case BALANCE = 'balance';

    public function label(): string
    {
        return match ($this) {
            self::BALANCE => '余额支付',
        };
    }
}
