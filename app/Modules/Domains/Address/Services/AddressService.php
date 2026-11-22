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

    public function importAddresses(array $data, string $source = 'pcas-file'): int
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
