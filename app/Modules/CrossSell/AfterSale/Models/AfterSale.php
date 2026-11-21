<?php

declare(strict_types=1);

namespace App\Modules\CrossSell\AfterSale\Models;

use App\Modules\CrossSell\Enums\AfterSaleStatus;
use App\Modules\CrossSell\Enums\AfterSaleType;
use App\Modules\CrossSell\Order\Models\Order;
use App\Modules\Domains\AfterSale\Database\Factories\AfterSaleFactory;
use App\Modules\Domains\User\Models\Customer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AfterSale extends Model
{
    use HasFactory;

    protected $table = 'after_sales';

    protected static function newFactory(): AfterSaleFactory
    {
        return AfterSaleFactory::new();
    }

    protected $fillable = [
        'order_id',
        'user_id',
        'type',
        'status',
        'refund_amount',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'user_id' => 'integer',
        'refund_amount' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'user_id');
    }

    public function isReturn(): bool
    {
        return $this->type === AfterSaleType::RETURN->value;
    }

    public function complete(): void
    {
        $this->status = AfterSaleStatus::COMPLETED->value;
        $this->save();
    }
}
