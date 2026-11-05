<?php

namespace App\Modules\Catalog\Domain;

enum VariantStatus: string
{
    case Active = 'active';
    case Disabled = 'disabled';
}
