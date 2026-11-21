<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\AdminNotificationResource\Pages;

use App\Filament\Admin\Resources\AdminNotificationResource;
use Filament\Resources\Pages\ViewRecord;

class ViewAdminNotification extends ViewRecord
{
    protected static string $resource = AdminNotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
