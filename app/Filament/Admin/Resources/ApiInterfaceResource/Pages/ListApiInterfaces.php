<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ApiInterfaceResource\Pages;

use App\Filament\Admin\Concerns\HasExportAction;
use App\Filament\Admin\Resources\ApiInterfaceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListApiInterfaces extends ListRecords
{
    use HasExportAction;

    protected static string $resource = ApiInterfaceResource::class;

    protected function getHeaderActions(): array
    {
        return array_merge(
            [Actions\CreateAction::make()],
            $this->getExportHeaderActions(),
        );
    }

    public function getExportQuery(): Builder
    {
        return $this->getResource()::getModel()::query()
            ->with(['function.module']);
    }

    public function getExportColumns(): array
    {
        return [
            'id' => 'ID',
            'name' => '接口名称',
            'method' => '请求方法',
            'path' => '路径',
            'auth_required' => '需要认证',
            'sort_order' => '排序',
        ];
    }

    public function getExportLabel(): string
    {
        return '接口列表';
    }

    protected function getExportDirectory(): string
    {
        return 'exports/api-interfaces';
    }
}
