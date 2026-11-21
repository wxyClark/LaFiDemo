<?php

declare(strict_types=1);

namespace App\Modules\CrossSell\Fulfillment\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 发货明细模型
 */
class ShipmentItem extends Model
{
    use HasFactory;

    protected $table = 'cross_sell_shipment_items';

    protected $fillable = [
        'shipment_id',
        'order_item_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    /**
     * 关联发货单
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    /**
     * 关联订单明细
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
