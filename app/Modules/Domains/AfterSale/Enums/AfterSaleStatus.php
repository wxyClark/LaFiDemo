<?php

declare(strict_types=1);

namespace App\Modules\Domains\AfterSale\Enums;

enum AfterSaleStatus: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => '处理中',
            self::COMPLETED => '已完成',
            self::REJECTED => '已拒绝',
        };
    }
}
