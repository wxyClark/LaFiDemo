<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CrossSellTenantResource\Pages;

use App\Filament\Admin\Resources\CrossSellTenantResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCrossSellTenant extends CreateRecord
{
    protected static string $resource = CrossSellTenantResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
