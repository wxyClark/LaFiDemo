<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CrossSellPlatformConfigResource\Pages;

use App\Filament\Admin\Resources\CrossSellPlatformConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCrossSellPlatformConfig extends EditRecord
{
    protected static string $resource = CrossSellPlatformConfigResource::class;

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
