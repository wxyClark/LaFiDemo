<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\XinfutongApprovalResource\Pages;

use App\Filament\Admin\Resources\XinfutongApprovalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditXinfutongApproval extends EditRecord
{
    protected static string $resource = XinfutongApprovalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
