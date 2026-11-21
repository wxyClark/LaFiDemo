<?php

declare(strict_types=1);

namespace App\Modules\Domains\AfterSale\Database\Factories;

use App\Modules\Domains\AfterSale\Enums\AfterSaleType;
use App\Modules\Domains\AfterSale\Models\AfterSale;
use App\Modules\Domains\Order\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class AfterSaleFactory extends Factory
{
    protected $model = AfterSale::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'user_id' => fake()->numberBetween(1, 1000),
            'type' => AfterSaleType::REFUND->value,
            'status' => 'pending',
            'refund_amount' => fake()->randomFloat(2, 1, 200),
        ];
    }
}
