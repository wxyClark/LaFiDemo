<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CrossSellOrderResource\Pages;

use App\Filament\Admin\Resources\CrossSellOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCrossSellOrder extends EditRecord
{
    protected static string $resource = CrossSellOrderResource::class;

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
