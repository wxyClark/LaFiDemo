<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\AfterSaleResource\Pages;

use App\Filament\Admin\Resources\AfterSaleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAfterSales extends ListRecords
{
    protected static string $resource = AfterSaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
