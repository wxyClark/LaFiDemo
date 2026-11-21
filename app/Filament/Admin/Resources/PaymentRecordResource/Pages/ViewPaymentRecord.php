<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PaymentRecordResource\Pages;

use App\Filament\Admin\Resources\PaymentRecordResource;
use Filament\Resources\Pages\ViewRecord;

class ViewPaymentRecord extends ViewRecord
{
    protected static string $resource = PaymentRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
