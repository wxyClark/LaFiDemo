<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ApiTestCaseResource\Pages;

use App\Filament\Admin\Resources\ApiTestCaseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditApiTestCase extends EditRecord
{
    protected static string $resource = ApiTestCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
