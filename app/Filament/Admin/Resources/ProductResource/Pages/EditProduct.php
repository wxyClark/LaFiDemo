<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ProductResource\Pages;

use App\Filament\Admin\Resources\ProductResource;
use App\Modules\Domains\Base\Logging\Models\AdminAuditLog;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

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
            description: "更新商品: {$record->name}",
        );
    }
}
