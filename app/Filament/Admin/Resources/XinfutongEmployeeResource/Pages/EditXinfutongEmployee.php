<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\XinfutongEmployeeResource\Pages;

use App\Filament\Admin\Resources\XinfutongEmployeeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditXinfutongEmployee extends EditRecord
{
    protected static string $resource = XinfutongEmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
