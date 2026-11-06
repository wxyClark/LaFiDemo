<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\PlatformPanelProvider;
use App\Providers\Filament\TenantPanelProvider;

return [
    AppServiceProvider::class,
    PlatformPanelProvider::class,
    TenantPanelProvider::class,
];
