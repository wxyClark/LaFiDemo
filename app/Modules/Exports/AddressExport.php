<?php

declare(strict_types=1);

namespace App\Modules\Exports;

use App\Modules\Models\Enums\AddressLevel;
use App\Modules\Services\AddressService;

class AddressExport extends QueryExport
{
    public function __construct(array $filters = [])
    {
        $service = app(AddressService::class);
        $query = $service->buildQuery($filters);

        $columns = [
            'id' => 'ID',
            'name' => '名称',
            'code' => '行政区划代码',
            'level' => '层级',
            'level_num' => '层级编号',
            'parent_name' => '上级地址',
            'pinyin' => '拼音',
            'merge_path' => '合并路径',
            'sort' => '排序',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
        ];

        parent::__construct($query, $columns, '地址数据');
    }

    protected function formatRow($row): array
    {
        return [
            $row->id,
            $row->name,
            $row->code,
            $this->getLevelLabel($row->level),
            $row->level_num,
            $row->parent->name ?? '',
            $row->pinyin ?? '',
            $row->full_path,
            $row->sort ?? 0,
            $row->created_at?->format('Y-m-d H:i:s') ?? '',
            $row->updated_at?->format('Y-m-d H:i:s') ?? '',
        ];
    }

    protected function getLevelLabel(string $level): string
    {
        return AddressLevel::toLabel($level);
    }
}
