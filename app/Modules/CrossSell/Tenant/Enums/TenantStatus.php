<?php

declare(strict_types=1);

namespace App\Modules\CrossSell\Tenant\Enums;

/**
 * 租户状态枚举
 */
enum TenantStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Cancelled = 'cancelled';

    /**
     * 获取状态名称
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => '正常',
            self::Suspended => '已暂停',
            self::Cancelled => '已取消',
        };
    }

    /**
     * 获取颜色（用于Filament Badge）
     */
    public function getColor(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Suspended => 'warning',
            self::Cancelled => 'danger',
        };
    }
}
