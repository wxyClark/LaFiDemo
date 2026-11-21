<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ApiTestResultResource\Pages;

use App\Filament\Admin\Concerns\HasExportAction;
use App\Filament\Admin\Resources\ApiTestResultResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListApiTestResults extends ListRecords
{
    use HasExportAction;

    protected static string $resource = ApiTestResultResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('全部'),
            'passed' => Tab::make('通过')
                ->query(fn ($query) => $query->where('status', 'pass')),
            'failed' => Tab::make('失败')
                ->query(fn ($query) => $query->where('status', 'fail')),
            'errors' => Tab::make('错误')
                ->query(fn ($query) => $query->where('status', 'error')),
        ];
    }

    public function getExportQuery(): Builder
    {
        return $this->getResource()::getModel()::query()
            ->with(['testCase', 'interface', 'environment']);
    }

    public function getExportColumns(): array
    {
        return [
            'id' => 'ID',
            'status' => '状态',
            'request_method' => '请求方法',
            'response_status' => '状态码',
            'response_time' => '响应时间(ms)',
            'executed_at' => '执行时间',
        ];
    }

    public function getExportLabel(): string
    {
        return '测试结果';
    }

    protected function getExportDirectory(): string
    {
        return 'exports/api-test-results';
    }
}
