<?php

namespace App\Modules\Catalog\Domain;

enum ProductStatus: string
{
    case Active = 'active';
    case Disabled = 'disabled';
}
