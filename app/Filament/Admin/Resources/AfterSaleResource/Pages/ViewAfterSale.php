<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\AfterSaleResource\Pages;

use App\Filament\Admin\Resources\AfterSaleResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAfterSale extends ViewRecord
{
    protected static string $resource = AfterSaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
