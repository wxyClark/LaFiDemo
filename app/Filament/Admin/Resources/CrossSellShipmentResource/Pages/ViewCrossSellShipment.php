<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CrossSellShipmentResource\Pages;

use App\Filament\Admin\Resources\CrossSellShipmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCrossSellShipment extends ViewRecord
{
    protected static string $resource = CrossSellShipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
