<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CrossSellProductResource\Pages;

use App\Filament\Admin\Resources\CrossSellProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCrossSellProducts extends ListRecords
{
    protected static string $resource = CrossSellProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
