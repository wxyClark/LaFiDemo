<?php

declare(strict_types=1);

namespace App\Modules\Domains\Payment\Database\Factories;

use App\Modules\Domains\Payment\Enums\PaymentType;
use App\Modules\Domains\Payment\Models\PaymentRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentRecordFactory extends Factory
{
    protected $model = PaymentRecord::class;

    public function definition(): array
    {
        return [
            'user_id' => fake()->numberBetween(1, 1000),
            'order_id' => null,
            'type' => PaymentType::RECHARGE->value,
            'amount' => fake()->randomFloat(2, 1, 500),
            'gateway' => 'balance',
            'status' => 'success',
        ];
    }
}
