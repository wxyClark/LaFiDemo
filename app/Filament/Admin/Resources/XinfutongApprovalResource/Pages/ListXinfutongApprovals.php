<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\XinfutongApprovalResource\Pages;

use App\Filament\Admin\Concerns\HasExportAction;
use App\Filament\Admin\Resources\XinfutongApprovalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListXinfutongApprovals extends ListRecords
{
    use HasExportAction;

    protected static string $resource = XinfutongApprovalResource::class;

    protected function getHeaderActions(): array
    {
        return array_merge(
            [
                Actions\CreateAction::make(),
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
            'approval_no' => '审批单号',
            'approval_type' => '审批类型',
            'title' => '标题',
            'applicant_name' => '申请人',
            'status' => '状态',
            'submitted_at' => '提交时间',
            'created_at' => '创建时间',
        ];
    }

    public function getExportLabel(): string
    {
        return '薪福通审批单';
    }

    protected function getExportDirectory(): string
    {
        return 'exports/xinfutong-approvals';
    }
}
