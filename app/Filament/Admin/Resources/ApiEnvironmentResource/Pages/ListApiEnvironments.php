<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ApiEnvironmentResource\Pages;

use App\Filament\Admin\Concerns\HasExportAction;
use App\Filament\Admin\Resources\ApiEnvironmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListApiEnvironments extends ListRecords
{
    use HasExportAction;

    protected static string $resource = ApiEnvironmentResource::class;

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
            'name' => '环境名称',
            'base_url' => 'Base URL',
            'auth_type' => '认证方式',
            'is_default' => '默认环境',
            'created_at' => '创建时间',
        ];
    }

    public function getExportLabel(): string
    {
        return '测试环境';
    }

    protected function getExportDirectory(): string
    {
        return 'exports/api-environments';
    }
}
