<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CrossSellShipmentResource\Pages;

use App\Filament\Admin\Resources\CrossSellShipmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCrossSellShipment extends EditRecord
{
    protected static string $resource = CrossSellShipmentResource::class;

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
