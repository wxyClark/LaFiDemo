<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CrossSellInventoryResource;

use App\Modules\Domains\CrossSell\Models\Inventory;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class InventoryExporter extends Exporter
{
    protected static ?string $model = Inventory::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),
            ExportColumn::make('sku.sku_code')->label('SKU编码'),
            ExportColumn::make('sku.product.title')->label('商品名称'),
            ExportColumn::make('warehouse.name')->label('仓库'),
            ExportColumn::make('quantity')->label('库存数量'),
            ExportColumn::make('reserved_quantity')->label('预留数量'),
            ExportColumn::make('available_quantity')->label('可用数量'),
            ExportColumn::make('min_stock')->label('最低库存'),
            ExportColumn::make('max_stock')->label('最高库存'),
            ExportColumn::make('created_at')->label('创建时间'),
        ];
    }

    public static function getDefaultFormats(): array
    {
        return ['csv', 'xlsx'];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = '您的库存数据导出已完成。';
        $body .= '共导出 '.$export->successful_rows.' 条记录。';

        return $body;
    }
}
