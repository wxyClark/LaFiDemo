<?php

declare(strict_types=1);

namespace App\Modules\Domains\Fulfillment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shipment extends Model
{
    protected $table = 'shipments';

    protected $fillable = [
        'fulfillment_id',
        'carrier',
        'tracking_no',
    ];

    public function fulfillment(): BelongsTo
    {
        return $this->belongsTo(Fulfillment::class, 'fulfillment_id');
    }
}
