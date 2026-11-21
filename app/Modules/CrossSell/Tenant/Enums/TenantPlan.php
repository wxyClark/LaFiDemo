<?php

declare(strict_types=1);

namespace App\Modules\CrossSell\Tenant\Enums;

/**
 * 租户套餐枚举
 */
enum TenantPlan: string
{
    case Free = 'free';
    case Basic = 'basic';
    case Pro = 'pro';
    case Enterprise = 'enterprise';

    /**
     * 获取套餐名称
     */
    public function label(): string
    {
        return match ($this) {
            self::Free => '免费版',
            self::Basic => '基础版',
            self::Pro => '专业版',
            self::Enterprise => '企业版',
        };
    }

    /**
     * 获取套餐价格（月）
     */
    public function getPrice(): float
    {
        return match ($this) {
            self::Free => 0,
            self::Basic => 99,
            self::Pro => 299,
            self::Enterprise => 999,
        };
    }

    /**
     * 获取订单限制
     */
    public function getOrderLimit(): int
    {
        return match ($this) {
            self::Free => 200,
            self::Basic => 1000,
            self::Pro => 5000,
            self::Enterprise => PHP_INT_MAX,
        };
    }

    /**
     * 获取平台数量限制
     */
    public function getPlatformLimit(): int
    {
        return match ($this) {
            self::Free => 1,
            self::Basic => 2,
            self::Pro => 5,
            self::Enterprise => PHP_INT_MAX,
        };
    }

    /**
     * 获取颜色（用于Filament Badge）
     */
    public function getColor(): string
    {
        return match ($this) {
            self::Free => 'gray',
            self::Basic => 'info',
            self::Pro => 'success',
            self::Enterprise => 'warning',
        };
    }
}
