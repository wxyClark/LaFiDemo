<?php

declare(strict_types=1);

namespace App\Modules\Services\AddressImport\Providers;

use App\Modules\Services\AddressImport\HmtRegions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Gitee 远端民政部区划数据源（免费、无需 key）
 *
 * modood/Administrative-divisions-of-China 持续同步民政部行政区划数据：
 *   dist/pcas-code.json  省级/市/区 三级（code: 2/4/6位）
 *   dist/streets.csv     乡镇街道（code: 9位）
 *   dist/HK-MO-TW.json   港澳台（无编码，本类用内置 HmtRegions 权威编码替代）
 *
 * 运行时下载到 storage/app/address-import/，不依赖仓库内文件。
 */
class PcasRemoteProvider implements AddressProvider
{
    public const DEFAULT_BASE_URL = 'https://gitee.com/modood/Administrative-divisions-of-China/raw/master/dist';

    protected ?Collection $tree = null;

    /** @var array<string, array<int, array{code: string, name: string, areaCode: string, cityCode: string}>> 按 provinceCode 分组 */
    protected ?array $streetsByProvince = null;

    protected ?string $revision = null;

    public function __construct(protected ?string $baseUrl = null)
    {
    }

    public function name(): string
    {
        return 'pcas';
    }

    public function revision(): string
    {
        if ($this->revision === null) {
            $json = $this->download('pcas-code.json');
            $csv = $this->download('streets.csv');

            $this->revision = 'pcas@' . substr(sha1($json . $csv), 0, 16);
        }

        return $this->revision;
    }

    public function units(): array
    {
        $units = $this->tree()
            ->map(fn (array $p) => ['key' => $p['code'], 'name' => $p['name']])
            ->values()
            ->toArray();

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

        $province = $this->tree()->firstWhere('code', $key);

        if ($province === null) {
            throw new RuntimeException("PcasRemoteProvider: 省级单元 {$key} 不存在");
        }

        $rows = $this->flattenTree($province);

        foreach ($this->streets($key) as $street) {
            $cityName = $this->cityNameByCode($province, $street['cityCode']);
            $districtName = $this->districtNameByCode($province, $street['areaCode']);

            $rows[] = [
                'code' => $street['code'],
                'name' => $street['name'],
                'level' => 'township',
                'level_num' => 5,
                'parent_code' => $street['areaCode'],
                'merge_path' => array_values(array_filter([$province['name'], $cityName, $districtName, $street['name']], fn ($v) => $v !== null && $v !== '')),
            ];
        }

        return $rows;
    }

    protected function tree()
    {
        if ($this->tree === null) {
            $json = $this->download('pcas-code.json');
            $tree = json_decode($json, true);

            if (! is_array($tree)) {
                throw new RuntimeException('pcas-code.json 解析失败');
            }

            $this->tree = collect($tree);
        }

        return $this->tree;
    }

    /**
     * @return array<int, array{code: string, name: string, areaCode: string, cityCode: string}>
     */
    protected function streets(string $provinceCode): array
    {
        if ($this->streetsByProvince === null) {
            $this->streetsByProvince = [];

            $path = $this->cachePath('streets.csv');
            $handle = fopen($path, 'rb');

            $header = fgetcsv($handle);

            while (($line = fgetcsv($handle)) !== false) {
                if ($line === [null] || count($line) < 5) {
                    continue;
                }

                [$code, $name, $areaCode, $provinceCodeRow, $cityCode] = $line;
                $this->streetsByProvince[$provinceCodeRow][] = [
                    'code' => $code,
                    'name' => $name,
                    'areaCode' => $areaCode,
                    'cityCode' => $cityCode,
                ];
            }

            fclose($handle);
        }

        return $this->streetsByProvince[$provinceCode] ?? [];
    }

    /**
     * @param array{code: string, name: string, children?: array} $province
     * @return array<int, array{code: string, name: string, level: string, level_num: int, parent_code: ?string, merge_path: array<int, string>}>
     */
    protected function flattenTree(array $province): array
    {
        $rows = [[
            'code' => $province['code'],
            'name' => $province['name'],
            'level' => 'province',
            'level_num' => 2,
            'parent_code' => null,
            'merge_path' => [$province['name']],
        ]];

        foreach ($province['children'] ?? [] as $city) {
            $cityPath = [$province['name'], $city['name']];

            $rows[] = [
                'code' => $city['code'],
                'name' => $city['name'],
                'level' => 'city',
                'level_num' => 3,
                'parent_code' => $province['code'],
                'merge_path' => $cityPath,
            ];

            foreach ($city['children'] ?? [] as $district) {
                $rows[] = [
                    'code' => $district['code'],
                    'name' => $district['name'],
                    'level' => 'district',
                    'level_num' => 4,
                    'parent_code' => $city['code'],
                    'merge_path' => array_merge($cityPath, [$district['name']]),
                ];
            }
        }

        return $rows;
    }

    protected function cityNameByCode(array $province, ?string $cityCode): ?string
    {
        foreach ($province['children'] ?? [] as $city) {
            if ($city['code'] === $cityCode) {
                return $city['name'];
            }
        }

        return null;
    }

    protected function districtNameByCode(array $province, ?string $districtCode): ?string
    {
        foreach ($province['children'] ?? [] as $city) {
            foreach ($city['children'] ?? [] as $district) {
                if ($district['code'] === $districtCode) {
                    return $district['name'];
                }
            }
        }

        return null;
    }

    protected function download(string $file): string
    {
        $path = $this->cachePath($file);
        File::ensureDirectoryExists(dirname($path));

        $url = rtrim($this->baseUrl ?: (string) env('ADDRESS_IMPORT_BASE_URL') ?: static::DEFAULT_BASE_URL, '/') . '/' . $file;

        $response = Http::withHeaders(['User-Agent' => 'laravel-address-import/1.0'])
            ->timeout(90)
            ->get($url);

        if ($response->failed()) {
            throw new RuntimeException("下载 {$file} 失败: HTTP {$response->status()}");
        }

        File::put($path, $response->body());

        return file_get_contents($path);
    }

    protected function cachePath(string $file): string
    {
        return storage_path('app/address-import/' . $file);
    }
}
