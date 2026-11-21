<?php

declare(strict_types=1);

namespace App\Filament\Admin\Concerns;

use App\Modules\Domains\Auth\Models\Admin;
use App\Modules\Exports\QueryExport;
use App\Modules\Jobs\ExportDataJob;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

trait HasExportAction
{
    public bool $exporting = false;

    public string $exportMessage = '';

    public ?string $downloadUrl = null;

    abstract public function getExportQuery(): Builder;

    abstract public function getExportColumns(): array;

    abstract public function getExportLabel(): string;

    protected function getExportDirectory(): string
    {
        return 'exports/data';
    }

    // ── 便捷方法（供 Blade / Livewire wire:click 调用）──────

    public function exportCsv(): void
    {
        $this->startExport('csv');
    }

    public function exportExcel(): void
    {
        $this->startExport('xlsx');
    }

    // ── Filament ListRecords header actions ─────────────────

    protected function getExportHeaderActions(): array
    {
        return [
            Action::make('exportCsv')
                ->label('导出 CSV')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action(fn () => $this->startExport('csv')),

            Action::make('exportExcel')
                ->label('导出 Excel')
                ->icon('heroicon-o-table-cells')
                ->action(fn () => $this->startExport('xlsx')),
        ];
    }

    // ── 核心导出逻辑 ──────────────────────────────────────

    protected function startExport(string $format): void
    {
        $query = $this->getExportQuery();
        $totalRows = (clone $query)->count();

        if ($totalRows === 0) {
            Notification::make()
                ->title('没有符合条件的数据')
                ->warning()
                ->send();

            return;
        }

        $export = new QueryExport(
            $query,
            $this->getExportColumns(),
            $this->getExportLabel()
        );

        if ($totalRows > 100_000) {
            /** @var Admin $user */
            $user = Auth::guard('admin')->user();
            ExportDataJob::dispatch($export, $format, $user->id, $this->getExportDirectory());

            $this->exporting = true;
            $this->exportMessage = '数据量较大（'.number_format($totalRows).'条），正在后台处理...';
            $this->downloadUrl = null;

            Notification::make()
                ->title('导出任务已提交')
                ->body('数据量较大（'.number_format($totalRows).'条），正在后台处理...')
                ->info()
                ->send();
        } else {
            /** @var Admin $user */
            $user = Auth::guard('admin')->user();
            $token = Str::random(32);
            $base = $query->toBase();
            Cache::put("sync_export_{$user->id}:{$token}", [
                'sql' => $base->toSql(),
                'bindings' => $base->getBindings(),
                'model' => get_class($query->getModel()),
                'eager' => array_keys($query->getEagerLoads()),
                'columns' => $this->getExportColumns(),
                'label' => $this->getExportLabel(),
                'format' => $format,
            ], now()->addMinutes(5));

            $this->dispatch('startSyncDownload', url: route('admin.api.export.sync'), token: $token);
        }
    }

    // ── 异步导出状态轮询（供自定义页面使用）────────────────

    public function checkExportStatus(): void
    {
        /** @var Admin $user */
        $user = Auth::guard('admin')->user();
        $notification = Cache::get("export_notification_{$user->id}");

        if (! $notification || $notification['type'] !== 'export') {
            return;
        }

        $this->exportMessage = $notification['message'];

        if ($notification['success']) {
            $this->exporting = false;
            $this->downloadUrl = $notification['file_path'] ?? null;
        } else {
            $this->exporting = false;
        }

        Cache::forget("export_notification_{$user->id}");
    }

    public function getDownloadUrl(): ?string
    {
        if (! $this->downloadUrl) {
            return null;
        }

        return route('admin.api.export.download', ['filePath' => $this->downloadUrl]);
    }
}
