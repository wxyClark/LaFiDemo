<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CustomerResource;

use App\Modules\Domains\User\Models\Customer;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class CustomerExporter extends Exporter
{
    protected static ?string $model = Customer::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),
            ExportColumn::make('name')->label('姓名'),
            ExportColumn::make('email')->label('邮箱'),
            ExportColumn::make('phone')->label('手机号'),
            ExportColumn::make('created_at')->label('注册时间'),
        ];
    }

    public static function getDefaultFormats(): array
    {
        return ['csv', 'xlsx'];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = '您的客户数据导出已完成。';
        $body .= '共导出 '.$export->successful_rows.' 条记录。';

        return $body;
    }
}
