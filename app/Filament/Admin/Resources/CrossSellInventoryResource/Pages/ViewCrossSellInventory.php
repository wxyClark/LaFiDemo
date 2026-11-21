<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CrossSellInventoryResource\Pages;

use App\Filament\Admin\Resources\CrossSellInventoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCrossSellInventory extends ViewRecord
{
    protected static string $resource = CrossSellInventoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
