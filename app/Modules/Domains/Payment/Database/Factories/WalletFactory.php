<?php

declare(strict_types=1);

namespace App\Modules\Domains\Payment\Database\Factories;

use App\Modules\Domains\Payment\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

class WalletFactory extends Factory
{
    protected $model = Wallet::class;

    public function definition(): array
    {
        return [
            'user_id' => fake()->unique()->numberBetween(1, 100000),
            'balance' => fake()->randomFloat(2, 0, 1000),
        ];
    }
}
