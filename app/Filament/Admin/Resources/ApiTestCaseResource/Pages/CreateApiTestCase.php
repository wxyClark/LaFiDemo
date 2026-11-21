<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ApiTestCaseResource\Pages;

use App\Filament\Admin\Resources\ApiTestCaseResource;
use Filament\Resources\Pages\CreateRecord;

class CreateApiTestCase extends CreateRecord
{
    protected static string $resource = ApiTestCaseResource::class;
}
