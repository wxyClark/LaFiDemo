<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\LoggingResource\Pages;

use App\Filament\Admin\Resources\LoggingResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewLogging extends ViewRecord
{
    protected static string $resource = LoggingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
