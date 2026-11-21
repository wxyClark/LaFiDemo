<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ApiTestCaseResource\Pages;

use App\Filament\Admin\Concerns\HasExportAction;
use App\Filament\Admin\Resources\ApiTestCaseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListApiTestCases extends ListRecords
{
    use HasExportAction;

    protected static string $resource = ApiTestCaseResource::class;

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
            ->with(['interface.function.module', 'environment']);
    }

    public function getExportColumns(): array
    {
        return [
            'id' => 'ID',
            'name' => '用例名称',
            'expected_status' => '预期状态码',
            'sort_order' => '排序',
            'created_at' => '创建时间',
        ];
    }

    public function getExportLabel(): string
    {
        return '测试用例';
    }

    protected function getExportDirectory(): string
    {
        return 'exports/api-test-cases';
    }
}
