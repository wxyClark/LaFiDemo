<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ApiInterfaceResource\Pages;

use App\Filament\Admin\Resources\ApiInterfaceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateApiInterface extends CreateRecord
{
    protected static string $resource = ApiInterfaceResource::class;
}
