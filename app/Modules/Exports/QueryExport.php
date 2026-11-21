<?php

declare(strict_types=1);

namespace App\Modules\Exports;

use App\Modules\Domains\Address\Enums\AddressLevel;
use Illuminate\Database\Eloquent\Builder;

class QueryExport
{
    protected Builder $query;

    protected array $columns;

    protected string $label;

    /**
     * @param  Builder  $query  Eloquent query builder
     * @param  array  $columns  ['db_column' => '导出表头']，保持顺序
     * @param  string  $label  导出文件名前缀
     */
    public function __construct(Builder $query, array $columns, string $label = '数据')
    {
        $this->query = $query;
        $this->columns = $columns;
        $this->label = $label;
    }

    /**
     * 导出为可缓存的数组（不序列化 Builder/PDO）
     */
    public function toArray(): array
    {
        $base = $this->query->toBase();
        $modelClass = get_class($this->query->getModel());
        $eagerLoads = array_keys($this->query->getEagerLoads());

        return [
            'model' => $modelClass,
            'eager' => $eagerLoads,
            'sql' => $base->toRawSql(),
            'bindings' => $base->getBindings(),
            'columns' => $this->columns,
            'label' => $this->label,
        ];
    }

    /**
     * 从缓存数组重建查询
     */
    public static function fromArray(array $data): self
    {
        $modelClass = $data['model'];
        $query = $modelClass::query();

        if (! empty($data['eager'])) {
            $query->with($data['eager']);
        }

        return new self($query, $data['columns'], $data['label']);
    }

    public function getTotalRows(): int
    {
        return (clone $this->query)->count();
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function streamCsv(callable $rowCallback): void
    {
        $chunkSize = 1000;
        $offset = 0;

        do {
            $rows = (clone $this->query)
                ->orderBy('id')
                ->offset($offset)
                ->limit($chunkSize)
                ->get();

            foreach ($rows as $row) {
                $rowCallback($this->formatRow($row));
            }

            $count = $rows->count();
            $offset += $chunkSize;
        } while ($count === $chunkSize);
    }

    public function exportToCsv(string $filePath): void
    {
        $handle = fopen($filePath, 'w');
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, array_values($this->columns));

        $this->streamCsv(function (array $row) use ($handle) {
            fputcsv($handle, $row);
        });

        fclose($handle);
    }

    public function exportToExcel(string $filePath): void
    {
        $zip = new \ZipArchive;
        if ($zip->open($filePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("无法创建 Excel 文件: {$filePath}");
        }

        $zip->addFromString('[Content_Types].xml', $this->getContentTypesXml());
        $zip->addFromString('_rels/.rels', $this->getRelsXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->getWorkbookRelsXml());
        $zip->addFromString('xl/styles.xml', $this->getStylesXml());
        $zip->addFromString('xl/workbook.xml', $this->getWorkbookXml($this->label));
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->getSheetXml());

        $zip->close();
    }

    protected function formatRow($row): array
    {
        $data = [];
        foreach (array_keys($this->columns) as $column) {
            $value = $row->{$column} ?? '';
            if ($column === 'level' && is_string($value)) {
                $value = AddressLevel::toLabel($value);
            } elseif (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            }
            $data[] = $value;
        }

        return $data;
    }

    protected function getSheetXml(): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <sheetData>';

        $xml .= '<row r="1">';
        $colIndex = 0;
        foreach (array_values($this->columns) as $header) {
            $cellRef = $this->getColumnLetter($colIndex).'1';
            $xml .= '<c r="'.$cellRef.'" t="inlineStr"><is><t>'.htmlspecialchars($header).'</t></is></c>';
            $colIndex++;
        }
        $xml .= '</row>';

        $rowNum = 2;
        $chunkSize = 1000;
        $offset = 0;

        do {
            $rows = (clone $this->query)
                ->orderBy('id')
                ->offset($offset)
                ->limit($chunkSize)
                ->get();

            foreach ($rows as $row) {
                $xml .= '<row r="'.$rowNum.'">';
                $data = $this->formatRow($row);
                $colIndex = 0;
                foreach ($data as $value) {
                    $cellRef = $this->getColumnLetter($colIndex).$rowNum;
                    $strValue = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value;
                    $xml .= '<c r="'.$cellRef.'" t="inlineStr"><is><t>'.htmlspecialchars($strValue).'</t></is></c>';
                    $colIndex++;
                }
                $xml .= '</row>';
                $rowNum++;
            }

            $count = $rows->count();
            $offset += $chunkSize;
        } while ($count === $chunkSize);

        $xml .= '</sheetData>
</worksheet>';

        return $xml;
    }

    protected function getColumnLetter(int $index): string
    {
        $letter = '';
        while ($index >= 0) {
            $letter = chr(65 + ($index % 26)).$letter;
            $index = intdiv($index, 26) - 1;
        }

        return $letter;
    }

    protected function getContentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
    <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
    <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>';
    }

    protected function getRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>';
    }

    protected function getWorkbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>';
    }

    protected function getWorkbookXml(string $sheetName): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"
          xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <sheets>
        <sheet name="'.htmlspecialchars($sheetName).'" sheetId="1" r:id="rId1"/>
    </sheets>
</workbook>';
    }

    protected function getStylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <fonts count="2">
        <font><b/></font>
        <font/>
    </fonts>
    <fills count="2">
        <fill><patternFill patternType="none"/></fill>
        <fill><patternFill patternType="gray125"/></fill>
    </fills>
    <borders count="1">
        <border><left/><right/><top/><bottom/><diagonal/></border>
    </borders>
    <cellStyleXfs count="1">
        <xf/>
    </cellStyleXfs>
    <cellXfs count="2">
        <xf/>
        <xf fontId="0" applyFont="1"/>
    </cellXfs>
</styleSheet>';
    }
}
