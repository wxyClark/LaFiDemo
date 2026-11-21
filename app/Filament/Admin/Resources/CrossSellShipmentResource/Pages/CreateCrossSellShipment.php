<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CrossSellShipmentResource\Pages;

use App\Filament\Admin\Resources\CrossSellShipmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCrossSellShipment extends CreateRecord
{
    protected static string $resource = CrossSellShipmentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
