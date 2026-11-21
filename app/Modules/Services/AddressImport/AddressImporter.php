<?php

declare(strict_types=1);

namespace App\Modules\Services\AddressImport;

use App\Modules\Services\AddressImport\Providers\AddressProvider;
use App\Modules\Services\AddressService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Overtrue\Pinyin\Pinyin;
use RuntimeException;

/**
 * 行政区划导入器
 *
 * - 分页: 按省级单位逐页 upsert
 * - 断点续传: 每完成一页写一次 state.json（原子写），中断重跑自动跳过已完成单位
 * - 幂等: 以民政部标准码 area_code 为唯一键 upsert，支持多平台来源共存
 */
class AddressImporter
{
    protected ?Pinyin $pinyin = null;

    /** @var array<string, string> */
    protected array $pinyinCache = [];

    /** @var array<string, int> 本单元内 原始code -> id */
    protected array $codeToId = [];

    public function __construct(protected AddressProvider $provider, protected ?AddressService $addressService = null) {}

    /**
     * @return array{imported: int, skipped: int, rows: int}
     */
    public function import(?string $onlyUnit = null, bool $flush = false): array
    {
        if ($flush) {
            DB::table('addresses')->truncate();
            File::delete($this->statePath());
        }

        $revision = $this->provider->revision();

        $state = $this->loadState();

        if (($state['provider'] ?? null) !== $this->provider->name() || ($state['revision'] ?? null) !== $revision) {
            $state['completed'] = [];
            Log::info('addresses:init 数据源/修订号变化，重置断点', [
                'provider' => $this->provider->name(),
                'revision' => $revision,
            ]);
        }

        $units = $this->provider->units();

        if ($onlyUnit !== null) {
            $units = array_values(array_filter($units, fn (array $u) => $u['key'] === $onlyUnit));

            if ($units === []) {
                throw new RuntimeException("单元 {$onlyUnit} 不存在");
            }
        }

        $totalRows = 0;
        $imported = 0;

        foreach ($units as $index => $unit) {
            $key = $unit['key'];

            if (isset($state['completed'][$key]) && $onlyUnit === null) {
                $state['skipped'] = ($state['skipped'] ?? 0) + 1;

                continue;
            }

            $rows = $this->prepareRows($this->provider->rowsForUnit($key));

            DB::transaction(function () use ($rows) {
                $this->upsertByLevel($rows);
            });

            $state['completed'][$key] = count($rows);
            $totalRows += count($rows);
            $imported++;

            $this->saveState($state, $revision);

            Log::info(
                "addresses:init 单元完成 {$key} {$unit['name']} (".count($rows).' rows) '.($index + 1).'/'.count($units)
            );
        }

        $this->addressService()->clearCache();

        return [
            'imported' => $imported,
            'skipped' => $state['skipped'] ?? 0,
            'rows' => $totalRows,
        ];
    }

    /**
     * 补全拼音/归一化 area_code/来源标记，并按层级排序
     *
     * @param  array<int, array{code: string, name: string, level: string, level_num: int, parent_code: ?string, merge_path: array<int, string>}>  $rows
     * @return array<int, array<string, mixed>>
     */
    protected function prepareRows(array $rows): array
    {
        $source = $this->provider->name();
        $syncedAt = now();
        $prepared = [];

        foreach ($rows as $row) {
            $pinyin = null;

            if ($row['level_num'] <= 4) {
                $name = $row['name'];
                $pinyinCacheKey = $name;
                $pinyin = $this->pinyinCache[$pinyinCacheKey] ??= $this->pinyin()->permalink($name, '');
            }

            $prepared[] = [
                'code' => $row['code'],
                'area_code' => CanonicalCode::normalize($row['code'], $row['level_num']),
                'name' => $row['name'],
                'level' => $row['level'],
                'level_num' => $row['level_num'],
                'parent_code' => $row['parent_code'],
                'pinyin' => $pinyin,
                'merge_path' => json_encode($row['merge_path'], JSON_UNESCAPED_UNICODE),
                'source' => $source,
                'synced_at' => $syncedAt,
                'sort' => 0,
            ];
        }

        usort($prepared, fn (array $a, array $b) => $a['level_num'] <=> $b['level_num']);

        return $prepared;
    }

    /**
     * 按层级 upsert：父级先入库，建立 code->id 映射供子级解析 parent_id
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function upsertByLevel(array $rows): void
    {
        $this->codeToId = [];

        $byLevel = [];

        foreach ($rows as $row) {
            $byLevel[$row['level_num']][] = $row;
        }

        ksort($byLevel);

        foreach ($byLevel as $levelNum => $levelRows) {
            $batch = [];

            foreach ($levelRows as $row) {
                $parentCode = $row['parent_code'] ?? null;
                $parentId = $parentCode !== null ? ($this->codeToId[$parentCode] ?? null) : null;

                $batch[] = [
                    'parent_id' => $parentId,
                    'name' => $row['name'],
                    'code' => $row['code'],
                    'area_code' => $row['area_code'],
                    'level' => $row['level'],
                    'level_num' => $levelNum,
                    'pinyin' => $row['pinyin'],
                    'merge_path' => $row['merge_path'],
                    'source' => $row['source'],
                    'synced_at' => $row['synced_at'],
                    'sort' => $row['sort'],
                    'created_at' => now(),
                    'updated_at' => now(),
                    'deleted_at' => null,
                ];
            }

            $codes = array_column($batch, 'code');

            foreach (array_chunk($batch, 50) as $chunk) {
                DB::table('addresses')->upsert(
                    $chunk,
                    uniqueBy: ['area_code'],
                    update: [
                        'parent_id', 'name', 'code', 'level', 'level_num', 'pinyin',
                        'merge_path', 'source', 'synced_at', 'updated_at', 'deleted_at',
                    ],
                );
            }

            $inserted = DB::table('addresses')
                ->whereIn('code', $codes)
                ->pluck('id', 'code')
                ->toArray();

            foreach ($inserted as $code => $id) {
                $this->codeToId[$code] = $id;
            }

            $batch = [];
        }
    }

    protected function pinyin(): Pinyin
    {
        return $this->pinyin ??= app(Pinyin::class);
    }

    protected function addressService(): AddressService
    {
        return $this->addressService ??= app(AddressService::class);
    }

    protected function statePath(): string
    {
        return storage_path('app/address-import/state.json');
    }

    /**
     * @return array{provider?: string, revision?: string, completed?: array<string, int>, skipped?: int}
     */
    protected function loadState(): array
    {
        $path = $this->statePath();

        if (! File::exists($path)) {
            return ['completed' => [], 'skipped' => 0];
        }

        $state = json_decode(File::get($path), true);

        return is_array($state) ? $state : ['completed' => [], 'skipped' => 0];
    }

    /**
     * @param  array{completed: array<string, int>}  $state
     */
    protected function saveState(array $state, string $revision): void
    {
        $state['provider'] = $this->provider->name();
        $state['revision'] = $revision;
        $state['updated_at'] = now()->toIso8601String();

        File::ensureDirectoryExists(dirname($this->statePath()));

        $tmp = $this->statePath().'.tmp';
        file_put_contents($tmp, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        rename($tmp, $this->statePath());
    }
}
