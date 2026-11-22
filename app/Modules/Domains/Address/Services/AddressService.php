<?php

declare(strict_types=1);

namespace App\Modules\Domains\Address\Services;

use App\Modules\Domains\Address\Models\Address;
use App\Modules\Domains\Address\Support\CanonicalCode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AddressService
{
    protected int $cacheTtl;

    public function __construct()
    {
        $this->cacheTtl = config('app.address_cache_ttl', 3600);
    }

    /**
     * 构建地址查询（页面显示和导出共用）
     *
     * @param  array{parent_id?: int, level?: string, keyword?: string}  $filters
     */
    public function buildQuery(array $filters = []): Builder
    {
        $query = Address::query()->with('parent');

        if (! empty($filters['parent_id'])) {
            $query->where('parent_id', $filters['parent_id']);
        }

        if (! empty($filters['level'])) {
            $query->where('level', $filters['level']);
        }

        if (! empty($filters['keyword'])) {
            $keyword = $filters['keyword'];
            $query->where(function (Builder $q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('code', 'like', "%{$keyword}%")
                    ->orWhere('pinyin', 'like', "%{$keyword}%");
            });
        }

        return $query;
    }

    public function getAllAddresses(): Collection
    {
        return Cache::remember('addresses.all', $this->cacheTtl, function () {
            return Address::with('children')->orderBy('level_num')->orderBy('sort')->get();
        });
    }

    public function getChildrenByParentId(?int $parentId): Collection
    {
        return Cache::remember("addresses.parent.{$parentId}", $this->cacheTtl, function () use ($parentId) {
            return Address::where('parent_id', $parentId)
                ->orderBy('id')
                ->get();
        });
    }

    public function getByLevel(string $level): Collection
    {
        return Cache::remember("addresses.level.{$level}", $this->cacheTtl, function () use ($level) {
            return Address::where('level', $level)
                ->orderBy('sort')
                ->orderBy('name')
                ->get();
        });
    }

    public function findByCode(string $code): ?Address
    {
        return Cache::remember("addresses.code.{$code}", $this->cacheTtl, function () use ($code) {
            return Address::where('code', $code)
                ->orWhere('area_code', $code)
                ->first();
        });
    }

    public function getAddressTree(?int $parentId = null): Collection
    {
        $all = Address::query()
            ->orderBy('level_num')
            ->orderBy('sort')
            ->orderBy('name')
            ->get(['id', 'parent_id', 'name', 'code', 'level', 'level_num']);

        $childrenMap = $all->groupBy('parent_id');

        return $this->buildTree($childrenMap, $parentId);
    }

    protected function buildTree(Collection $childrenMap, ?int $parentId): Collection
    {
        return $childrenMap->get($parentId, collect())->map(function (Address $address) use ($childrenMap) {
            return [
                'id' => $address->id,
                'name' => $address->name,
                'code' => $address->code,
                'level' => $address->level,
                'level_num' => $address->level_num,
                'children' => $this->buildTree($childrenMap, $address->id),
            ];
        });
    }

    /**
     * 导入地址数据。
     *
     * @param  array<int, array<string, mixed>>  $data
     * @param  bool  $replace  true=清空全表重建；false=按 code 增量合并（已存在则判断是否需要更新）
     */
    public function importAddresses(array $data, string $source = 'pcas-file', bool $replace = true): int
    {
        if ($replace) {
            return $this->replaceImport($data, $source);
        }

        [$inserted, $updated, $skipped] = $this->mergeImport($data, $source);

        return $inserted + $updated + $skipped;
    }

    /**
     * 增量合并并返回统计。
     *
     * @param  array<int, array<string, mixed>>  $data
     * @return array{0:int,1:int,2:int} [新增, 更新, 已存在无需更新]
     */
    public function mergeAddresses(array $data, string $source = 'pcas-file'): array
    {
        return $this->mergeImport($data, $source);
    }

    /**
     * 清空全表后重建（整体替换）。
     *
     * @param  array<int, array<string, mixed>>  $data
     */
    protected function replaceImport(array $data, string $source): int
    {
        Address::query()->withTrashed()->forceDelete();
        $this->clearCache();

        // 按层级分组导入（父级 level_num 恒小于子级），保证插入子级时其父级 code -> id 已就绪，
        // 避免同一分块内父子互相引用导致 parent_id 丢失。
        $byLevel = collect($data)->groupBy('level_num')->sortKeys();
        $codeToId = [];
        $count = 0;

        foreach ($byLevel as $levelRows) {
            foreach ($levelRows->chunk(1000) as $chunk) {
                $codes = $chunk->pluck('code')->toArray();
                $rows = [];

                foreach ($chunk as $item) {
                    $parentId = null;
                    if (! empty($item['parent_code']) && isset($codeToId[$item['parent_code']])) {
                        $parentId = $codeToId[$item['parent_code']];
                    }

                    $rows[] = [
                        'parent_id' => $parentId,
                        'name' => $item['name'],
                        'code' => $item['code'],
                        'area_code' => CanonicalCode::normalize($item['code'], $item['level_num']),
                        'level' => $item['level'],
                        'level_num' => $item['level_num'],
                        'pinyin' => $item['pinyin'] ?? null,
                        'merge_path' => isset($item['merge_path']) ? json_encode($item['merge_path']) : null,
                        'sort' => $item['sort'] ?? 0,
                        'source' => $source,
                        'synced_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                DB::table((new Address)->getTable())->insert($rows);

                // 查询本批次插入的 id，回填 code -> id 映射
                $inserted = DB::table((new Address)->getTable())
                    ->whereIn('code', $codes)
                    ->pluck('id', 'code')
                    ->toArray();

                foreach ($inserted as $code => $id) {
                    $codeToId[$code] = $id;
                }

                $count += count($rows);
            }
        }

        return $count;
    }

    /**
     * 增量合并：按 code 判定是否已存在，存在则比较关键字段决定是否需要更新，不存在则插入。
     *
     * @param  array<int, array<string, mixed>>  $data
     * @return array{0:int,1:int,2:int} [新增, 更新, 已存在无需更新]
     */
    protected function mergeImport(array $data, string $source): array
    {
        $this->clearCache();

        $table = (new Address)->getTable();
        $existing = Address::query()
            ->select(['id', 'code', 'parent_id', 'name', 'level', 'level_num', 'source'])
            ->get()
            ->keyBy('code');

        $codeToId = [];
        foreach ($existing as $code => $row) {
            $codeToId[$code] = $row->id;
        }

        // 按层级分组，父级先处理，保证子级能解析到 parent_id
        $byLevel = collect($data)->groupBy('level_num')->sortKeys();
        $inserted = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($byLevel as $levelRows) {
            foreach ($levelRows->chunk(1000) as $chunk) {
                $insertRows = [];
                $updateRows = [];

                foreach ($chunk as $item) {
                    $parentId = null;
                    $parentCode = $item['parent_code'] ?? null;
                    if (! empty($parentCode) && isset($codeToId[$parentCode])) {
                        $parentId = $codeToId[$parentCode];
                    }

                    $attrs = [
                        'parent_id' => $parentId,
                        'name' => $item['name'],
                        'code' => $item['code'],
                        'area_code' => CanonicalCode::normalize($item['code'], $item['level_num']),
                        'level' => $item['level'],
                        'level_num' => $item['level_num'],
                        'pinyin' => $item['pinyin'] ?? null,
                        'merge_path' => isset($item['merge_path']) ? json_encode($item['merge_path']) : null,
                        'sort' => $item['sort'] ?? 0,
                        'source' => $source,
                        'synced_at' => now(),
                    ];

                    $row = $existing->get($item['code']);
                    if ($row === null) {
                        $insertRows[] = $attrs + ['created_at' => now(), 'updated_at' => now()];

                        continue;
                    }

                    $codeToId[$item['code']] = $row->id;

                    $changed = $row->name !== $attrs['name']
                        || $row->level !== $attrs['level']
                        || (int) $row->level_num !== (int) $attrs['level_num']
                        || (string) $row->source !== (string) $attrs['source']
                        || (int) $row->parent_id !== (int) ($parentId ?? 0);

                    if ($changed) {
                        $updateRows[] = ['id' => $row->id] + $attrs + ['updated_at' => now()];
                    } else {
                        $skipped++;
                    }
                }

                if ($insertRows !== []) {
                    DB::table($table)->insert($insertRows);

                    DB::table($table)
                        ->whereIn('code', array_column($insertRows, 'code'))
                        ->pluck('id', 'code')
                        ->each(function ($id, $code) use (&$codeToId) {
                            $codeToId[$code] = $id;
                        });

                    $inserted += count($insertRows);
                }

                foreach ($updateRows as $u) {
                    DB::table($table)->where('id', $u['id'])->update($u);
                    $updated++;
                }
            }
        }

        return [$inserted, $updated, $skipped];
    }

    public function clearCache(): void
    {
        $keys = [
            'addresses.all',
            'address:public:stats',
            'address:public:provinces',
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }
}
