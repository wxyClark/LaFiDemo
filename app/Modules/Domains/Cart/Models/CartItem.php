<?php

declare(strict_types=1);

namespace App\Modules\Domains\Cart\Models;

use App\Modules\Domains\Cart\Database\Factories\CartItemFactory;
use App\Modules\Domains\Catalog\Models\Sku;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    use HasFactory;

    protected $table = 'cart_items';

    protected static function newFactory(): CartItemFactory
    {
        return CartItemFactory::new();
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class, 'sku_id');
    }

    protected $fillable = [
        'cart_id',
        'sku_id',
        'qty',
        'selected',
    ];

    protected $casts = [
        'qty' => 'integer',
        'selected' => 'boolean',
    ];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class, 'cart_id');
    }
}
