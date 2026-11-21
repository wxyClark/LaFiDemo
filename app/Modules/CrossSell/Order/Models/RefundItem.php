<?php

declare(strict_types=1);

namespace App\Modules\CrossSell\Order\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 退款明细模型
 */
class RefundItem extends Model
{
    use HasFactory;

    protected $table = 'cross_sell_refund_items';

    protected $fillable = [
        'refund_id',
        'order_item_id',
        'quantity',
        'amount',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'amount' => 'decimal:2',
    ];

    /**
     * 关联退款单
     */
    public function refund(): BelongsTo
    {
        return $this->belongsTo(Refund::class);
    }

    /**
     * 关联订单明细
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
