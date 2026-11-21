<?php

declare(strict_types=1);

namespace App\Modules\CrossSell\Product\Enums;

/**
 * 商品状态枚举
 */
enum ProductStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Inactive = 'inactive';

    /**
     * 获取状态名称
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => '草稿',
            self::Active => '上架',
            self::Inactive => '下架',
        };
    }

    /**
     * 获取颜色（用于Filament Badge）
     */
    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Active => 'success',
            self::Inactive => 'danger',
        };
    }
}
