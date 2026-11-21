<?php

declare(strict_types=1);

namespace App\Modules\Domains\Address\Enums;

enum AddressLevel: string
{
    case COUNTRY = 'country';
    case PROVINCE = 'province';
    case CITY = 'city';
    case DISTRICT = 'district';
    case TOWNSHIP = 'township';

    public function label(): string
    {
        return match ($this) {
            self::COUNTRY => '国家级',
            self::PROVINCE => '省级',
            self::CITY => '地级',
            self::DISTRICT => '县级',
            self::TOWNSHIP => '街道级',
        };
    }

    /**
     * 将数据库 level 值转为中文标签，未知值原样返回
     */
    public static function toLabel(string $level): string
    {
        return self::tryFrom($level)?->label() ?? $level;
    }
}
