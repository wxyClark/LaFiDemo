<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ApiEnvironmentResource\Pages;

use App\Filament\Admin\Resources\ApiEnvironmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateApiEnvironment extends CreateRecord
{
    protected static string $resource = ApiEnvironmentResource::class;
}
