<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\XinfutongSyncLogResource\Pages;

use App\Filament\Admin\Concerns\HasExportAction;
use App\Filament\Admin\Resources\XinfutongSyncLogResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListXinfutongSyncLogs extends ListRecords
{
    use HasExportAction;

    protected static string $resource = XinfutongSyncLogResource::class;

    protected function getHeaderActions(): array
    {
        return $this->getExportHeaderActions();
    }

    public function getExportQuery(): Builder
    {
        return $this->getResource()::getModel()::query();
    }

    public function getExportColumns(): array
    {
        return [
            'id' => 'ID',
            'sync_type' => '同步类型',
            'status' => '状态',
            'records_fetched' => '拉取数',
            'records_created' => '新增数',
            'records_updated' => '更新数',
            'error_message' => '错误信息',
            'started_at' => '开始时间',
            'completed_at' => '完成时间',
        ];
    }

    public function getExportLabel(): string
    {
        return '薪福通同步日志';
    }

    protected function getExportDirectory(): string
    {
        return 'exports/xinfutong-sync-logs';
    }
}
