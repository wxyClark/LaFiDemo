<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\XinfutongEmployeeResource\Pages;

use App\Filament\Admin\Concerns\HasExportAction;
use App\Filament\Admin\Resources\XinfutongEmployeeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListXinfutongEmployees extends ListRecords
{
    use HasExportAction;

    protected static string $resource = XinfutongEmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return array_merge(
            [
                Actions\CreateAction::make(),
                Actions\Action::make('sync')
                    ->label('同步数据')
                    ->icon('heroicon-o-arrow-path')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('同步薪福通员工数据')
                    ->modalDescription('将从薪福通 API 拉取最新的员工信息并同步到本地数据库。')
                    ->action(fn () => XinfutongEmployeeResource::syncFromXinfutong()),
            ],
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
            'xinfutong_id' => '薪福通 ID',
            'employee_no' => '工号',
            'name' => '姓名',
            'gender' => '性别',
            'phone' => '手机号',
            'email' => '邮箱',
            'department' => '部门',
            'position' => '职位',
            'hire_date' => '入职日期',
            'status' => '状态',
            'last_synced_at' => '最后同步时间',
        ];
    }

    public function getExportLabel(): string
    {
        return '薪福通员工';
    }

    protected function getExportDirectory(): string
    {
        return 'exports/xinfutong-employees';
    }
}
