<?php

declare(strict_types=1);

namespace App\Modules\CrossSell\Fulfillment\Models;

use App\Modules\CrossSell\Fulfillment\Enums\ShipmentStatus;
use App\Modules\CrossSell\Order\Models\Order;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 发货单模型
 */
class Shipment extends Model
{
    use HasFactory;

    protected $table = 'cross_sell_shipments';

    protected $fillable = [
        'tenant_id',
        'order_id',
        'logistics_channel_id',
        'shipment_no',
        'tracking_no',
        'status',
        'carrier',
        'warehouse_id',
        'weight',
        'shipping_cost',
        'currency',
        'shipped_at',
        'delivered_at',
        'tracking_data',
        'last_tracking_at',
    ];

    protected $casts = [
        'status' => ShipmentStatus::class,
        'weight' => 'decimal:3',
        'shipping_cost' => 'decimal:2',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'tracking_data' => 'array',
        'last_tracking_at' => 'datetime',
    ];

    /**
     * 关联租户
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * 关联订单
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * 关联物流渠道
     */
    public function logisticsChannel(): BelongsTo
    {
        return $this->belongsTo(LogisticsChannel::class);
    }

    /**
     * 关联仓库
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * 关联发货明细
     */
    public function items(): HasMany
    {
        return $this->hasMany(ShipmentItem::class);
    }
}
