<?php

declare(strict_types=1);

namespace App\Modules\Domains\Fulfillment\Database\Factories;

use App\Modules\Domains\Fulfillment\Enums\FulfillmentType;
use App\Modules\Domains\Fulfillment\Models\Fulfillment;
use App\Modules\Domains\Order\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class FulfillmentFactory extends Factory
{
    protected $model = Fulfillment::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'type' => fake()->randomElement([FulfillmentType::ENTITY, FulfillmentType::VIRTUAL])->value,
            'status' => 'pending',
        ];
    }
}
