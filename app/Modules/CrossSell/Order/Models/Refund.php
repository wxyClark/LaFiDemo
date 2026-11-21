<?php

declare(strict_types=1);

namespace App\Modules\CrossSell\Order\Models;

use App\Modules\CrossSell\Order\Enums\RefundStatus;
use App\Modules\CrossSell\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 退款单模型
 */
class Refund extends Model
{
    use HasFactory;

    protected $table = 'cross_sell_refunds';

    protected $fillable = [
        'tenant_id',
        'order_id',
        'refund_no',
        'reason',
        'amount',
        'currency',
        'amount_usd',
        'refund_method',
        'status',
        'refunded_at',
        'attachments',
        'note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_usd' => 'decimal:2',
        'status' => RefundStatus::class,
        'refunded_at' => 'datetime',
        'attachments' => 'array',
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
     * 关联退款明细
     */
    public function items(): HasMany
    {
        return $this->hasMany(RefundItem::class);
    }
}
