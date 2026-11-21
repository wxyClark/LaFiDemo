<?php

declare(strict_types=1);

namespace App\Modules\CrossSell\Fulfillment\Enums;

/**
 * 发货状态枚举
 */
enum ShipmentStatus: string
{
    case Pending = 'pending';
    case LabelCreated = 'label_created';
    case Shipped = 'shipped';
    case InTransit = 'in_transit';
    case Delivered = 'delivered';
    case Exception = 'exception';

    /**
     * 获取状态名称
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => '待发货',
            self::LabelCreated => '已生成面单',
            self::Shipped => '已发货',
            self::InTransit => '运输中',
            self::Delivered => '已签收',
            self::Exception => '异常',
        };
    }

    /**
     * 获取颜色（用于Filament Badge）
     */
    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::LabelCreated => 'info',
            self::Shipped => 'info',
            self::InTransit => 'warning',
            self::Delivered => 'success',
            self::Exception => 'danger',
        };
    }
}
