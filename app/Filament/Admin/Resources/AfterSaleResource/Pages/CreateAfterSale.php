<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\AfterSaleResource\Pages;

use App\Filament\Admin\Resources\AfterSaleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAfterSale extends CreateRecord
{
    protected static string $resource = AfterSaleResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
