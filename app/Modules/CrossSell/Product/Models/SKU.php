<?php

declare(strict_types=1);

namespace App\Modules\CrossSell\Product\Models;

use App\Modules\CrossSell\Tenant\Models\Tenant;
use App\Modules\CrossSell\Warehouse\Models\Inventory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * SKU模型
 */
class SKU extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'cross_sell_skus';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'sku_code',
        'barcode',
        'name',
        'price',
        'price_currency',
        'price_usd',
        'cost',
        'cost_currency',
        'cost_usd',
        'stock',
        'reserved_stock',
        'available_stock',
        'attributes',
        'status',
        'image',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'price_usd' => 'decimal:2',
        'cost' => 'decimal:2',
        'cost_usd' => 'decimal:2',
        'stock' => 'integer',
        'reserved_stock' => 'integer',
        'available_stock' => 'integer',
        'attributes' => 'array',
    ];

    /**
     * 关联租户
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * 关联商品
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * 关联翻译
     */
    public function translations(): HasMany
    {
        return $this->hasMany(SKUTranslation::class);
    }

    /**
     * 关联库存
     */
    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * 扣减库存
     */
    public function deductStock(int $quantity): bool
    {
        if ($this->available_stock < $quantity) {
            return false;
        }

        $this->decrement('stock', $quantity);
        $this->decrement('available_stock', $quantity);

        return true;
    }

    /**
     * 恢复库存
     */
    public function restoreStock(int $quantity): bool
    {
        $this->increment('stock', $quantity);
        $this->increment('available_stock', $quantity);

        return true;
    }
}
