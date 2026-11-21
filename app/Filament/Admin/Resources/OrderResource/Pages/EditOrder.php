<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\OrderResource\Pages;

use App\Filament\Admin\Resources\OrderResource;
use App\Modules\Domains\Base\Logging\Models\AdminAuditLog;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

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
        /** @var Model $record */
        $record = $this->record;

        AdminAuditLog::record(
            action: 'updated',
            subject: $record,
            oldValues: $record->getChanges(),
            newValues: $record->getAttributes(),
            description: "更新订单: {$record->order_no}",
        );
    }
}
