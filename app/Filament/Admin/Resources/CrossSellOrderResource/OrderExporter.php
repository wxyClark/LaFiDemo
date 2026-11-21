<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CrossSellOrderResource;

use App\Modules\CrossSell\Order\Models\Order;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class OrderExporter extends Exporter
{
    protected static ?string $model = Order::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),
            ExportColumn::make('order_no')->label('订单号'),
            ExportColumn::make('platform_order_id')->label('平台订单号'),
            ExportColumn::make('platform')->label('平台'),
            ExportColumn::make('status')->label('状态'),
            ExportColumn::make('buyer_name')->label('买家'),
            ExportColumn::make('buyer_phone')->label('买家电话'),
            ExportColumn::make('shipping_address')->label('收货地址'),
            ExportColumn::make('subtotal')->label('商品金额'),
            ExportColumn::make('shipping_fee')->label('运费'),
            ExportColumn::make('tax')->label('税费'),
            ExportColumn::make('created_at')->label('创建时间'),
        ];
    }

    public static function getDefaultFormats(): array
    {
        return ['csv', 'xlsx'];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = '您的订单数据导出已完成。';
        $body .= '共导出 '.$export->successful_rows.' 条记录。';

        return $body;
    }
}
