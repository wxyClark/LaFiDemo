<?php

namespace App\Modules\Dev\Traits;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * 流式 xlsx 导出（依赖 openspout/openspout，已在 composer.json 声明）。
 */
trait ExportTrait
{
    /**
     * 新建 xlsx 写入器，流式写入 storage/app/tmp
     */
    public function xlsxWriter(string $fileName): Writer
    {
        $directory = storage_path('app/tmp');
        File::ensureDirectoryExists($directory);

        $writer = new Writer;
        $writer->openToFile($directory.DIRECTORY_SEPARATOR.$fileName);

        return $writer;
    }

    /**
     * 写入表头（列名即表头）
     */
    public function xlsxHeader(Writer $writer, array $columns): void
    {
        $writer->addRow(Row::fromValues(array_map('strval', $columns)));
    }

    /**
     * 写入数据行
     */
    public function xlsxRow(Writer $writer, array $row): void
    {
        $writer->addRow(Row::fromValues(array_map('strval', array_values($row))));
    }

    /**
     * 下载已生成的 xlsx（发送后由框架清理临时文件）
     */
    public function xlsxToDown(string $fileName): BinaryFileResponse
    {
        return response()->download(storage_path('app/tmp/'.$fileName), Str::slug($fileName, '-'));
    }

    /**
     * 导出用唯一文件基名
     */
    public function exportFileName(string $ext): string
    {
        return Str::uuid()->toString().'-'.now()->format('Ymd-His').$ext;
    }
}
