<?php

declare(strict_types=1);

namespace App\Modules\CrossSell\Fulfillment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class VirtualCode extends Model
{
    protected $table = 'virtual_codes';

    protected $fillable = [
        'fulfillment_id',
        'code',
        'password',
        'delivered_at',
    ];

    protected $casts = [
        'delivered_at' => 'datetime',
    ];

    protected $hidden = [
        'password',
    ];

    public function fulfillment(): BelongsTo
    {
        return $this->belongsTo(Fulfillment::class, 'fulfillment_id');
    }

    public function setPasswordAttribute(string $plain): void
    {
        $this->attributes['password'] = Crypt::encryptString($plain);
    }

    public function plainPassword(): string
    {
        return Crypt::decryptString($this->attributes['password']);
    }
}
