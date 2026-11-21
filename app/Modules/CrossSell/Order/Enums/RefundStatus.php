<?php

declare(strict_types=1);

namespace App\Modules\CrossSell\Order\Enums;

/**
 * 退款状态枚举
 */
enum RefundStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Refunded = 'refunded';

    /**
     * 获取状态名称
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => '待处理',
            self::Approved => '已批准',
            self::Rejected => '已拒绝',
            self::Refunded => '已退款',
        };
    }

    /**
     * 获取颜色（用于Filament Badge）
     */
    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'info',
            self::Rejected => 'danger',
            self::Refunded => 'success',
        };
    }
}
