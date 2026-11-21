<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CrossSellInventoryResource\Pages;

use App\Filament\Admin\Resources\CrossSellInventoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCrossSellInventory extends CreateRecord
{
    protected static string $resource = CrossSellInventoryResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
