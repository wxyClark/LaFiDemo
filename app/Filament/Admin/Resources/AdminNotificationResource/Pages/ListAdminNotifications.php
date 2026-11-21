<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\AdminNotificationResource\Pages;

use App\Filament\Admin\Resources\AdminNotificationResource;
use Filament\Resources\Pages\ListRecords;

class ListAdminNotifications extends ListRecords
{
    protected static string $resource = AdminNotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
