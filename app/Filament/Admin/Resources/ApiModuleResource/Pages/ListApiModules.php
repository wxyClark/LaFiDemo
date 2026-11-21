<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ApiModuleResource\Pages;

use App\Filament\Admin\Concerns\HasExportAction;
use App\Filament\Admin\Resources\ApiModuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListApiModules extends ListRecords
{
    use HasExportAction;

    protected static string $resource = ApiModuleResource::class;

    protected function getHeaderActions(): array
    {
        return array_merge(
            [Actions\CreateAction::make()],
            $this->getExportHeaderActions(),
        );
    }

    public function getExportQuery(): Builder
    {
        return $this->getResource()::getModel()::query();
    }

    public function getExportColumns(): array
    {
        return [
            'id' => 'ID',
            'name' => '模块名称',
            'description' => '描述',
            'sort_order' => '排序',
            'created_at' => '创建时间',
        ];
    }

    public function getExportLabel(): string
    {
        return '接口模块';
    }

    protected function getExportDirectory(): string
    {
        return 'exports/api-modules';
    }
}
