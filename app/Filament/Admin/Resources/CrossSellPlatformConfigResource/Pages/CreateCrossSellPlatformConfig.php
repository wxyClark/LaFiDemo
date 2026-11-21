<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CrossSellPlatformConfigResource\Pages;

use App\Filament\Admin\Resources\CrossSellPlatformConfigResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCrossSellPlatformConfig extends CreateRecord
{
    protected static string $resource = CrossSellPlatformConfigResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
