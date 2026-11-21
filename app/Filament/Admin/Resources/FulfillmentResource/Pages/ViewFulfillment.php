<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\FulfillmentResource\Pages;

use App\Filament\Admin\Resources\FulfillmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewFulfillment extends ViewRecord
{
    protected static string $resource = FulfillmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
