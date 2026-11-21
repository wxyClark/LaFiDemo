<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CrossSellProductResource\Pages;

use App\Filament\Admin\Resources\CrossSellProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCrossSellProduct extends EditRecord
{
    protected static string $resource = CrossSellProductResource::class;

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
