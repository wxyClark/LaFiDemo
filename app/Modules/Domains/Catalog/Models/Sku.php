<?php

declare(strict_types=1);

namespace App\Modules\Domains\Catalog\Models;

use App\Modules\Domains\Catalog\Database\Factories\SkuFactory;
use App\Modules\Domains\Catalog\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sku extends Model
{
    use HasFactory;

    protected $table = 'skus';

    protected static function newFactory(): SkuFactory
    {
        return SkuFactory::new();
    }

    protected $fillable = [
        'product_id',
        'specs',
        'price',
        'stock',
        'status',
    ];

    protected $casts = [
        'specs' => 'array',
        'price' => 'decimal:2',
        'stock' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ProductStatus::ACTIVE->value);
    }

    public function isAvailable(): bool
    {
        return $this->status === ProductStatus::ACTIVE->value && $this->stock > 0;
    }

    public function decrementStock(int $qty): void
    {
        if ($qty <= 0) {
            return;
        }

        $this->newQuery()->where('id', $this->id)->decrement('stock', $qty);
    }
}
