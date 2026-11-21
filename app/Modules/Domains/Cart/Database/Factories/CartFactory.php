<?php

declare(strict_types=1);

namespace App\Modules\Domains\Cart\Database\Factories;

use App\Modules\Domains\Cart\Models\Cart;
use Illuminate\Database\Eloquent\Factories\Factory;

class CartFactory extends Factory
{
    protected $model = Cart::class;

    public function definition(): array
    {
        return [
            'user_id' => fake()->numberBetween(1, 1000),
        ];
    }
}
