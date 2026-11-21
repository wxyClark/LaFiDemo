<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CrossSellPlatformConfigResource\Pages;

use App\Filament\Admin\Resources\CrossSellPlatformConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCrossSellPlatformConfig extends ViewRecord
{
    protected static string $resource = CrossSellPlatformConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
