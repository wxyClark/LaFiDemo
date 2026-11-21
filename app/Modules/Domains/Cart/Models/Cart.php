<?php

declare(strict_types=1);

namespace App\Modules\Domains\Cart\Models;

use App\Modules\Domains\Cart\Database\Factories\CartFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    use HasFactory;

    protected $table = 'carts';

    protected static function newFactory(): CartFactory
    {
        return CartFactory::new();
    }

    protected $fillable = [
        'user_id',
    ];

    protected $casts = [
        'user_id' => 'integer',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class, 'cart_id');
    }

    public function selectedItems(): HasMany
    {
        return $this->items()->where('selected', true);
    }
}
