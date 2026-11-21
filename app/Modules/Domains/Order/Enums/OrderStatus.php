<?php

declare(strict_types=1);

namespace App\Modules\Domains\Order\Enums;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case COMPLETED = 'completed';
    case CANCELED = 'canceled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => '待支付',
            self::PAID => '已支付',
            self::COMPLETED => '已完成',
            self::CANCELED => '已取消',
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::PENDING => in_array($next, [self::PAID, self::CANCELED], true),
            self::PAID => in_array($next, [self::COMPLETED, self::CANCELED], true),
            self::COMPLETED, self::CANCELED => false,
        };
    }
}
