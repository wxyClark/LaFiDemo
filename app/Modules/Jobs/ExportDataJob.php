<?php

declare(strict_types=1);

namespace App\Modules\Jobs;

use App\Modules\Exports\QueryExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ExportDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    /**
     * @param  QueryExport  $export  导出实例
     * @param  string  $format  csv|xlsx
     * @param  int  $userId  触发用户 ID
     * @param  string  $directory  存储子目录 (e.g. 'exports/addresses')
     */
    public function __construct(
        protected QueryExport $export,
        protected string $format,
        protected int $userId,
        protected string $directory = 'exports/data',
    ) {
        $this->onQueue('exports');
    }

    public function handle(): void
    {
        $totalRows = $this->export->getTotalRows();

        if ($totalRows === 0) {
            $this->notifyUser('导出失败：没有符合条件的数据', false);

            return;
        }

        $label = $this->export->getLabel();
        $timestamp = now()->format('Ymd_His');
        $filename = "{$label}_{$timestamp}.{$this->format}";
        $filePath = storage_path("app/{$this->directory}/{$filename}");

        Storage::makeDirectory($this->directory);

        try {
            if ($this->format === 'csv') {
                $this->export->exportToCsv($filePath);
            } else {
                $this->export->exportToExcel($filePath);
            }

            $fileSize = round(filesize($filePath) / 1024, 2);

            $this->notifyUser(
                "导出完成：{$totalRows} 条数据，文件大小 {$fileSize}KB",
                true,
                "{$this->directory}/{$filename}"
            );
        } catch (\Exception $e) {
            $this->notifyUser("导出失败：{$e->getMessage()}", false);
            throw $e;
        }
    }

    protected function notifyUser(string $message, bool $success, string $filePath = ''): void
    {
        cache()->put(
            "export_notification_{$this->userId}",
            [
                'type' => 'export',
                'message' => $message,
                'success' => $success,
                'file_path' => $filePath,
                'created_at' => now()->toISOString(),
            ],
            now()->addHours(24)
        );
    }
}
