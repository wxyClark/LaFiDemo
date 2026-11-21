<?php

declare(strict_types=1);

namespace App\Modules\CrossSell\Order\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 订单明细模型
 */
class OrderItem extends Model
{
    use HasFactory;

    protected $table = 'cross_sell_order_items';

    protected $fillable = [
        'order_id',
        'sku_id',
        'product_title',
        'sku_code',
        'attributes',
        'image',
        'quantity',
        'unit_price',
        'total_price',
        'currency',
    ];

    protected $casts = [
        'attributes' => 'array',
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    /**
     * 关联订单
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * 关联SKU
     */
    public function sku(): BelongsTo
    {
        return $this->belongsTo(SKU::class);
    }
}
