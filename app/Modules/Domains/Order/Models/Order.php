<?php

declare(strict_types=1);

namespace App\Modules\Domains\Order\Models;

use App\Modules\Domains\Order\Database\Factories\OrderFactory;
use App\Modules\Domains\Order\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected static function newFactory(): OrderFactory
    {
        return OrderFactory::new();
    }

    protected $table = 'orders';

    protected $fillable = [
        'order_no',
        'user_id',
        'status',
        'total_amount',
        'freight',
        'paid_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'total_amount' => 'decimal:2',
        'freight' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function statusEnum(): OrderStatus
    {
        return OrderStatus::from($this->status);
    }

    public function transitionTo(OrderStatus $next): void
    {
        if (! $this->statusEnum()->canTransitionTo($next)) {
            throw new \RuntimeException("订单状态不可从 {$this->status} 迁移到 {$next->value}");
        }

        $this->status = $next->value;

        if ($next === OrderStatus::PAID) {
            $this->paid_at = now();
        }

        $this->save();
    }
}
