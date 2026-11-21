<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\BusinessLogResource\Pages;

use App\Filament\Admin\Concerns\HasExportAction;
use App\Filament\Admin\Resources\BusinessLogResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListBusinessLogs extends ListRecords
{
    use HasExportAction;

    protected static string $resource = BusinessLogResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('全部'),
            'info' => Tab::make('信息')
                ->query(fn ($query) => $query->where('level', 'info')),
            'warning' => Tab::make('警告')
                ->query(fn ($query) => $query->where('level', 'warning')),
            'error' => Tab::make('错误')
                ->query(fn ($query) => $query->where('level', 'error')),
            'critical' => Tab::make('严重')
                ->query(fn ($query) => $query->where('level', 'critical')),
        ];
    }

    public function getExportQuery(): Builder
    {
        return $this->getResource()::getModel()::query();
    }

    public function getExportColumns(): array
    {
        return [
            'id' => 'ID',
            'request_id' => '请求 ID',
            'level' => '级别',
            'channel' => '通道',
            'message' => '消息',
            'file' => '文件',
            'created_at' => '时间',
        ];
    }

    public function getExportLabel(): string
    {
        return '业务日志';
    }

    protected function getExportDirectory(): string
    {
        return 'exports/business-logs';
    }
}
