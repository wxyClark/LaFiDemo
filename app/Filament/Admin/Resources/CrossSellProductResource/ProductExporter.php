<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CrossSellProductResource;

use App\Modules\CrossSell\Product\Models\Product;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class ProductExporter extends Exporter
{
    protected static ?string $model = Product::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),
            ExportColumn::make('title')->label('商品标题'),
            ExportColumn::make('brand')->label('品牌'),
            ExportColumn::make('category_id')->label('分类ID'),
            ExportColumn::make('status')->label('状态'),
            ExportColumn::make('main_image')->label('主图'),
            ExportColumn::make('source_url')->label('来源链接'),
            ExportColumn::make('tags')->label('标签'),
            ExportColumn::make('created_at')->label('创建时间'),
        ];
    }

    public static function getDefaultFormats(): array
    {
        return ['csv', 'xlsx'];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = '您的商品数据导出已完成。';
        $body .= '共导出 '.$export->successful_rows.' 条记录。';

        return $body;
    }
}
