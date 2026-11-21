<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\LoggingResource\Pages;

use App\Filament\Admin\Concerns\HasExportAction;
use App\Filament\Admin\Resources\LoggingResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListLogging extends ListRecords
{
    use HasExportAction;

    protected static string $resource = LoggingResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('全部'),
            'errors' => Tab::make('异常请求')
                ->query(fn ($query) => $query->where('response_status', '>=', 400)
                    ->orWhereNotNull('exception_class')),
            'slow' => Tab::make('慢请求')
                ->query(fn ($query) => $query->where('response_time', '>', 1000)),
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
            'method' => '请求方法',
            'path' => '路径',
            'user_name' => '用户',
            'ip_address' => 'IP 地址',
            'client_type' => '客户端',
            'response_status' => '状态码',
            'response_time' => '响应时间(ms)',
            'created_at' => '时间',
        ];
    }

    public function getExportLabel(): string
    {
        return '请求日志';
    }

    protected function getExportDirectory(): string
    {
        return 'exports/request-logs';
    }
}
