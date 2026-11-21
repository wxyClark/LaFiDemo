<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CrossSellOrderResource\Pages;

use App\Filament\Admin\Resources\CrossSellOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCrossSellOrder extends ViewRecord
{
    protected static string $resource = CrossSellOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
