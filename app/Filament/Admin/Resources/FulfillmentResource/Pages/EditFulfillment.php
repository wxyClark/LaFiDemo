<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\FulfillmentResource\Pages;

use App\Filament\Admin\Resources\FulfillmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFulfillment extends EditRecord
{
    protected static string $resource = FulfillmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
