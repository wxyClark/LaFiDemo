<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\FulfillmentResource\Pages;

use App\Filament\Admin\Resources\FulfillmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFulfillment extends CreateRecord
{
    protected static string $resource = FulfillmentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
