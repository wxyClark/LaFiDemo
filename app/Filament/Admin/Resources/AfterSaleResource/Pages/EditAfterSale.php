<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\AfterSaleResource\Pages;

use App\Filament\Admin\Resources\AfterSaleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAfterSale extends EditRecord
{
    protected static string $resource = AfterSaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
