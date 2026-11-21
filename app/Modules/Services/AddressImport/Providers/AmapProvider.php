<?php

declare(strict_types=1);

namespace App\Modules\Services\AddressImport\Providers;

use App\Modules\Services\AddressImport\HmtRegions;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * 高德行政区划接口（数据源为民政部区划，需免费申请的 Web 服务 Key）
 *
 * 文档: https://lbs.amap.com/api/webservice/guide/api/district
 * 个人开发者免费额度 5000 次/日，QPS 限制 3。
 * 省/市/区 adcode 为 6 位（民政部标准码），乡镇为 9 位。
 *
 * 注意: 高德编码与 pcas 编码体系不同（110000 vs 11），
 * 切换数据源后请先执行 addresses:init --flush。
 */
class AmapProvider implements AddressProvider
{
    public function __construct(
        protected ?string $key = null,
        protected ?string $baseUrl = null,
    ) {
        $this->key ??= (string) env('AMAP_KEY', '');
        $this->baseUrl ??= rtrim((string) env('ADDRESS_API_BASE_URL', 'https://restapi.amap.com'), '/');

        if ($this->key === '') {
            throw new RuntimeException('AmapProvider 需要 AMAP_KEY（.env 或构造函数传入）');
        }
    }

    public function name(): string
    {
        return 'amap';
    }

    /**
     * 高德无版本概念：按天为修订号，跨天自动重新拉取（幂等 upsert）
     */
    public function revision(): string
    {
        return 'amap@'.now()->format('Ymd');
    }

    public function units(): array
    {
        $units = $this->district('中国', 0)
            ->map(fn (array $d) => ['key' => $d['adcode'], 'name' => $d['name']])
            // 港澳台用内置权威编码数据，避免与高德数据重复
            ->filter(fn (array $u) => ! preg_match('/^(71|81|82)/', $u['key']))
            ->values()
            ->all();

        foreach (HmtRegions::unitKeys() as $key) {
            $units[] = ['key' => $key, 'name' => HmtRegions::rows($key)[0]['name'] ?? $key];
        }

        return $units;
    }

    public function rowsForUnit(string $key): array
    {
        if (in_array($key, HmtRegions::unitKeys(), true)) {
            return HmtRegions::rows($key);
        }

        $province = $this->district($key, 3);

        if ($province === []) {
            throw new RuntimeException("AmapProvider: 未查询到省级单位 {$key}");
        }

        $rows = [];
        $this->walk($province, null, 2, $rows);

        return $rows;
    }

    /**
     * 请求高德 district 接口
     *
     * @return array<int, array{name: string, adcode: string, level: ?string, district?: array}>
     */
    protected function district(string $keyword, int $subdistrict): array
    {
        $response = Http::timeout(60)
            ->get($this->baseUrl.'/v3/config/district', [
                'keywords' => $keyword,
                'subdistrict' => $subdistrict,
                'extensions' => 'base',
                'key' => $this->key,
            ]);

        if ($response->failed()) {
            throw new RuntimeException("Amap district 请求失败: HTTP {$response->status()}");
        }

        $json = $response->json();

        if (($json['status'] ?? '0') !== '1') {
            throw new RuntimeException('Amap 返回错误: '.($json['info'] ?? 'unknown')." (infocode {$json['infocode']})");
        }

        return $json['districts'] ?? [];
    }

    /**
     * @param  array{rows: array}  $holder
     */
    protected function walk(array $node, ?string $parentAdcode, int $levelNum, array &$rows, array $path = []): void
    {
        $adcode = (string) ($node['adcode'] ?? '');

        // 乡镇级无 adcode 时: 父级 6 位 + 3 位序号
        if ($adcode === '' && $parentAdcode !== null) {
            static $seq = [];
            $seq[$parentAdcode] = ($seq[$parentAdcode] ?? 0) + 1;
            $adcode = $parentAdcode.sprintf('%03d', $seq[$parentAdcode]);
        }

        $level = match ($levelNum) {
            2 => 'province',
            3 => 'city',
            4 => 'district',
            default => 'township',
        };

        $currentPath = array_merge($path, [$node['name'] ?? '']);

        if ($adcode !== '') {
            $rows[] = [
                'code' => $adcode,
                'name' => $node['name'],
                'level' => $level,
                'level_num' => $levelNum,
                'parent_code' => $parentAdcode,
                'merge_path' => $currentPath,
            ];
        }

        // 高德 level 字段: city/district/street；直辖市下辖区县直接挂在省（无 city 级）
        $childLevel = $levelNum + 1;
        foreach ($node['district'] ?? [] as $child) {
            $childLevelNum = $childLevel;

            if (($child['level'] ?? null) === 'street') {
                $childLevelNum = 5;
            } elseif (($child['level'] ?? null) === 'district' && $levelNum === 3) {
                $childLevelNum = 4;
            }

            $this->walk($child, $adcode, $childLevelNum, $rows, $currentPath);
        }
    }
}
