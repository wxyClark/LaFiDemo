<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\OrderResource\Pages;

use App\Filament\Admin\Resources\OrderResource;
use App\Modules\Domains\Logging\Models\AdminAuditLog;
use Filament\Resources\Pages\CreateRecord;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected function afterCreate(): void
    {
        AdminAuditLog::record(
            action: 'created',
            subject: $this->record,
            newValues: ['order_no' => $this->record->order_no],
            description: "创建订单: {$this->record->order_no}",
        );
    }
}
