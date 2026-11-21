<?php

declare(strict_types=1);

namespace App\Modules\CrossSell\Order\Models;

use App\Modules\CrossSell\Order\Enums\OrderStatus;
use App\Modules\CrossSell\Tenant\Models\Tenant;
use App\Modules\CrossSell\Warehouse\Enums\PlatformType;
use App\Domains\Notification\Events\OrderStatusChanged;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 订单模型
 */
class Order extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'cross_sell_orders';

    protected $fillable = [
        'tenant_id',
        'order_no',
        'platform_order_id',
        'platform',
        'status',
        'buyer_name',
        'buyer_email',
        'buyer_phone',
        'shipping_name',
        'shipping_phone',
        'shipping_address',
        'shipping_country',
        'subtotal',
        'shipping_fee',
        'tax',
        'discount',
        'total',
        'currency',
        'total_usd',
        'payment_method',
        'payment_status',
        'paid_at',
        'note',
        'customer_note',
        'platform_data',
    ];

    protected $casts = [
        'status' => OrderStatus::class,
        'platform' => PlatformType::class,
        'shipping_address' => 'array',
        'subtotal' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'total_usd' => 'decimal:2',
        'paid_at' => 'datetime',
        'platform_data' => 'array',
    ];

    /**
     * 关联租户
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * 关联订单明细
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * 关联退款单
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    /**
     * 关联发货单
     */
    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    /**
     * 确认订单
     */
    public function confirm(): bool
    {
        if ($this->status !== OrderStatus::Pending) {
            return false;
        }

        $oldStatus = $this->status->value;
        $this->update(['status' => OrderStatus::Confirmed]);
        OrderStatusChanged::dispatch($this, $oldStatus, OrderStatus::Confirmed->value);

        return true;
    }

    /**
     * 发货
     */
    public function ship(): bool
    {
        if ($this->status !== OrderStatus::Processing) {
            return false;
        }

        $oldStatus = $this->status->value;
        $this->update(['status' => OrderStatus::Shipped]);
        OrderStatusChanged::dispatch($this, $oldStatus, OrderStatus::Shipped->value);

        return true;
    }

    /**
     * 完成订单
     */
    public function complete(): bool
    {
        if ($this->status !== OrderStatus::Delivered) {
            return false;
        }

        $oldStatus = $this->status->value;
        $this->update(['status' => OrderStatus::Completed]);
        OrderStatusChanged::dispatch($this, $oldStatus, OrderStatus::Completed->value);

        return true;
    }

    /**
     * 取消订单
     */
    public function cancel(): bool
    {
        if (in_array($this->status, [OrderStatus::Completed, OrderStatus::Cancelled, OrderStatus::Refunded])) {
            return false;
        }

        $oldStatus = $this->status->value;
        $this->update(['status' => OrderStatus::Cancelled]);
        OrderStatusChanged::dispatch($this, $oldStatus, OrderStatus::Cancelled->value);

        return true;
    }
}
