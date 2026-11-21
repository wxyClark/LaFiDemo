<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\XinfutongAccountResource\Pages;

use App\Filament\Admin\Resources\XinfutongAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditXinfutongAccount extends EditRecord
{
    protected static string $resource = XinfutongAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
