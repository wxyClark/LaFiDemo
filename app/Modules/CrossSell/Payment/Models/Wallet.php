<?php

declare(strict_types=1);

namespace App\Modules\CrossSell\Payment\Models;

use App\Modules\Domains\Payment\Database\Factories\WalletFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use HasFactory;

    protected $table = 'wallets';

    protected static function newFactory(): WalletFactory
    {
        return WalletFactory::new();
    }

    protected $fillable = [
        'user_id',
        'balance',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'balance' => 'decimal:2',
    ];

    public function records(): HasMany
    {
        return $this->hasMany(PaymentRecord::class, 'user_id', 'user_id');
    }
}
