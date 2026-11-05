<?php

namespace App\Modules\Inventory\Domain;

enum StockMovementType: string
{
    case In = 'in';
    case Out = 'out';
}
