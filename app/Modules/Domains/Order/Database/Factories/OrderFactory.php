<?php

declare(strict_types=1);

namespace App\Modules\Domains\Order\Database\Factories;

use App\Modules\Domains\Order\Enums\OrderStatus;
use App\Modules\Domains\Order\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'order_no' => 'NO'.fake()->unique()->numerify('############'),
            'user_id' => fake()->numberBetween(1, 1000),
            'status' => OrderStatus::PENDING->value,
            'total_amount' => fake()->randomFloat(2, 10, 999),
            'freight' => 0,
        ];
    }
}
