<?php

declare(strict_types=1);

namespace App\Modules\CrossSell\Warehouse\Enums;

/**
 * 平台类型枚举
 */
enum PlatformType: string
{
    case Temu = 'temu';
    case Shopify = 'shopify';
    case Amazon = 'amazon';
    case Shoplazza = 'shoplazza';
    case AliExpress = 'aliexpress';
    case Manual = 'manual';

    /**
     * 获取平台名称
     */
    public function label(): string
    {
        return match ($this) {
            self::Temu => 'Temu',
            self::Shopify => 'Shopify',
            self::Amazon => 'Amazon',
            self::Shoplazza => 'Shoplazza',
            self::AliExpress => '速卖通',
            self::Manual => '手动创建',
        };
    }

    /**
     * 获取颜色（用于Filament Badge）
     */
    public function getColor(): string
    {
        return match ($this) {
            self::Temu => 'danger',
            self::Shopify => 'success',
            self::Amazon => 'warning',
            self::Shoplazza => 'info',
            self::AliExpress => 'info',
            self::Manual => 'gray',
        };
    }
}
