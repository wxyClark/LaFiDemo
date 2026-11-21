<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CrossSellProductResource\Pages;

use App\Filament\Admin\Resources\CrossSellProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCrossSellProduct extends ViewRecord
{
    protected static string $resource = CrossSellProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
