<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\BusinessLogResource\Pages;

use App\Filament\Admin\Resources\BusinessLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewBusinessLog extends ViewRecord
{
    protected static string $resource = BusinessLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
