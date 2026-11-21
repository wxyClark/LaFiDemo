<?php

declare(strict_types=1);

namespace App\Modules\Services\AddressImport\Providers;

interface AddressProvider
{
    /**
     * 数据源标识（写入 addresses.source）
     */
    public function name(): string;

    /**
     * 导入单元列表（1 个省级单位 = 1 页）
     *
     * @return array<int, array{key: string, name: string}>
     */
    public function units(): array;

    /**
     * 数据修订号：上游数据变化后应返回不同的值（用于使断点状态失效）
     */
    public function revision(): string;

    /**
     * 获取指定单元的平铺行
     *
     * @return array<int, array{code: string, name: string, level: string, level_num: int, parent_code: ?string, merge_path: array<int, string>}>
     */
    public function rowsForUnit(string $key): array;
}
