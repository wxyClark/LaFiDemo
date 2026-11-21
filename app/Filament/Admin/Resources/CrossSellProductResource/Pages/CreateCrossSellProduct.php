<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CrossSellProductResource\Pages;

use App\Filament\Admin\Resources\CrossSellProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCrossSellProduct extends CreateRecord
{
    protected static string $resource = CrossSellProductResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
