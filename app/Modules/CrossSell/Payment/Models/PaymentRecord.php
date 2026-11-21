<?php

declare(strict_types=1);

namespace App\Modules\CrossSell\Payment\Models;

use App\Models\Customer;
use App\Modules\CrossSell\Database\Factories\PaymentRecordFactory;
use App\Modules\CrossSell\Enums\PaymentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentRecord extends Model
{
    use HasFactory;

    protected $table = 'payment_records';

    protected static function newFactory(): PaymentRecordFactory
    {
        return PaymentRecordFactory::new();
    }

    protected $fillable = [
        'user_id',
        'order_id',
        'type',
        'amount',
        'gateway',
        'status',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'order_id' => 'integer',
        'amount' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'user_id');
    }

    public function isRefund(): bool
    {
        return $this->type === PaymentType::REFUND->value;
    }
}
