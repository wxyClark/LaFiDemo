<?php

declare(strict_types=1);

namespace App\Modules\CrossSell\Product\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BundleItem extends Model
{
    protected $table = 'bundle_items';

    protected $fillable = [
        'bundle_id',
        'sku_id',
        'qty',
    ];

    protected $casts = [
        'qty' => 'integer',
    ];

    public function bundle(): BelongsTo
    {
        return $this->belongsTo(ProductBundle::class, 'bundle_id');
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(SKU::class, 'sku_id');
    }
}
