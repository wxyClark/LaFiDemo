<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ApiModuleResource\Pages;

use App\Filament\Admin\Resources\ApiModuleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateApiModule extends CreateRecord
{
    protected static string $resource = ApiModuleResource::class;
}
