<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CrossSellTenantResource\Pages;

use App\Filament\Admin\Resources\CrossSellTenantResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCrossSellTenant extends ViewRecord
{
    protected static string $resource = CrossSellTenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
