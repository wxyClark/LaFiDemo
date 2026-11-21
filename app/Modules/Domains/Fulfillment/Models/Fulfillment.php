<?php

declare(strict_types=1);

namespace App\Modules\Domains\Fulfillment\Models;

use App\Modules\Domains\Fulfillment\Database\Factories\FulfillmentFactory;
use App\Modules\Domains\Fulfillment\Enums\FulfillmentStatus;
use App\Modules\Domains\Fulfillment\Enums\FulfillmentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Fulfillment extends Model
{
    use HasFactory;

    protected $table = 'fulfillments';

    protected static function newFactory(): FulfillmentFactory
    {
        return FulfillmentFactory::new();
    }

    protected $fillable = [
        'order_id',
        'type',
        'status',
        'delivered_at',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'delivered_at' => 'datetime',
    ];

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'fulfillment_id');
    }

    public function virtualCodes(): HasMany
    {
        return $this->hasMany(VirtualCode::class, 'fulfillment_id');
    }

    public function shipment(): HasOne
    {
        return $this->hasOne(Shipment::class, 'fulfillment_id');
    }

    public function isVirtual(): bool
    {
        return $this->type === FulfillmentType::VIRTUAL->value;
    }

    public function markDelivered(): void
    {
        $this->status = FulfillmentStatus::DELIVERED->value;
        $this->delivered_at = now();
        $this->save();
    }
}
