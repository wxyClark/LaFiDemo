<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CrossSellOrderResource\Pages;

use App\Filament\Admin\Resources\CrossSellOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCrossSellOrders extends ListRecords
{
    protected static string $resource = CrossSellOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
