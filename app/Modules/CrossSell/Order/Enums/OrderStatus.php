<?php

declare(strict_types=1);

namespace App\Modules\CrossSell\Order\Enums;

/**
 * 订单状态枚举
 */
enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    /**
     * 获取状态名称
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => '待处理',
            self::Confirmed => '已确认',
            self::Processing => '处理中',
            self::Shipped => '已发货',
            self::Delivered => '已签收',
            self::Completed => '已完成',
            self::Cancelled => '已取消',
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
            self::Confirmed => 'info',
            self::Processing => 'info',
            self::Shipped => 'info',
            self::Delivered => 'success',
            self::Completed => 'success',
            self::Cancelled => 'danger',
            self::Refunded => 'danger',
        };
    }
}
