<?php

namespace App\Modules\Tenancy\Domain;

enum TenantStatus: string
{
    case Active = 'active';
    case Disabled = 'disabled';
}
