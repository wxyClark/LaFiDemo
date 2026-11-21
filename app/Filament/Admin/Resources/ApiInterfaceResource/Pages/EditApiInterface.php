<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ApiInterfaceResource\Pages;

use App\Filament\Admin\Resources\ApiInterfaceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditApiInterface extends EditRecord
{
    protected static string $resource = ApiInterfaceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
