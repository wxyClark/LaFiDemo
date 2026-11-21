<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CrossSellOrderResource\Pages;

use App\Filament\Admin\Resources\CrossSellOrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCrossSellOrder extends CreateRecord
{
    protected static string $resource = CrossSellOrderResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
