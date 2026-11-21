<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\FulfillmentResource\Pages;

use App\Filament\Admin\Resources\FulfillmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFulfillments extends ListRecords
{
    protected static string $resource = FulfillmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
