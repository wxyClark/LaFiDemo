<?php

declare(strict_types=1);

namespace App\Modules\Services\AddressImport;

final class CanonicalCode
{
    /**
     * pcas 数据中"不设区县的县级市"例外：市级 4 位码补零后会与同名区县级 6 位码撞码，
     * 故这些市保留 4 位原长
     */
    private const NO_PAD_CITIES = ['4419', '4420', '4604'];

    /**
     * 归一化为民政部标准行政区划码
     *
     * 大陆: 省/市/区 -> 6位 (GB/T 2260, 低位右补零: 11 -> 110000, 1101 -> 110100)，
     *       乡镇街道 -> 9位。
     * 港澳台: GB/T 2260 无官方 6 位市级码（pcas 市级 4 位码右补零会与省级 6 位码冲突），
     *       故港澳台各级保留原始码长。
     */
    public static function normalize(string $code, int $levelNum): string
    {
        if (in_array($code, self::NO_PAD_CITIES, true)) {
            return $code;
        }

        if (preg_match('/^(71|81|82)/', $code)) {
            return $code;
        }

        $target = $levelNum >= 5 ? 9 : 6;

        return str_pad($code, $target, '0', STR_PAD_RIGHT);
    }
}
