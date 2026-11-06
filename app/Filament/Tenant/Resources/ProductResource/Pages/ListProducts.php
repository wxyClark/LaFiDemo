<?php

namespace App\Filament\Tenant\Resources\ProductResource\Pages;

use App\Filament\Tenant\Resources\ProductResource;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;
}
