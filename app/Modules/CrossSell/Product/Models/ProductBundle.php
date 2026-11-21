<?php

declare(strict_types=1);

namespace App\Modules\CrossSell\Product\Models;

use App\Modules\CrossSell\Database\Factories\ProductBundleFactory;
use App\Modules\CrossSell\Product\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductBundle extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected static function newFactory(): ProductBundleFactory
    {
        return ProductBundleFactory::new();
    }

    protected $table = 'product_bundles';

    protected $fillable = [
        'name',
        'price',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(BundleItem::class, 'bundle_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ProductStatus::ACTIVE->value);
    }

    public function totalSkuPrice(): float
    {
        return (float) $this->items->load('sku')
            ->sum(fn (BundleItem $item) => (float) ($item->sku->price ?? 0) * $item->qty);
    }
}
