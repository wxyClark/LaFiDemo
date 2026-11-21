<?php

declare(strict_types=1);

namespace App\Modules\Domains\Base\Logging\Database\Factories;

use App\Modules\Domains\Base\Logging\Models\BusinessLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class BusinessLogFactory extends Factory
{
    protected $model = BusinessLog::class;

    public function definition(): array
    {
        return [
            'request_id' => $this->faker->uuid(),
            'level' => $this->faker->randomElement(['info', 'warning', 'error']),
            'channel' => $this->faker->randomElement(['default', 'order', 'payment']),
            'message' => $this->faker->sentence(),
        ];
    }
}
