<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CrossSellPlatformConfigResource\Pages;

use App\Filament\Admin\Resources\CrossSellPlatformConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCrossSellPlatformConfigs extends ListRecords
{
    protected static string $resource = CrossSellPlatformConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
