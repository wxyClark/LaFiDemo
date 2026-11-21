<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CrossSellInventoryResource\Pages;

use App\Filament\Admin\Resources\CrossSellInventoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCrossSellInventory extends EditRecord
{
    protected static string $resource = CrossSellInventoryResource::class;

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
