<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\OrderResource\Pages;

use App\Filament\Admin\Resources\OrderResource;
use App\Modules\Domains\Logging\Models\AdminAuditLog;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        AdminAuditLog::record(
            action: 'updated',
            subject: $this->record,
            oldValues: $this->record->getChanges(),
            newValues: $this->record->getAttributes(),
            description: "更新订单: {$this->record->order_no}",
        );
    }
}
