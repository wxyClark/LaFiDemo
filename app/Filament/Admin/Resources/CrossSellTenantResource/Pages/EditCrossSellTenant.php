<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CrossSellTenantResource\Pages;

use App\Filament\Admin\Resources\CrossSellTenantResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCrossSellTenant extends EditRecord
{
    protected static string $resource = CrossSellTenantResource::class;

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
