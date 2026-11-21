<?php

declare(strict_types=1);

namespace App\Modules\Domains\Base\Logging\Database\Factories;

use App\Modules\Domains\Base\Logging\Models\RequestLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class RequestLogFactory extends Factory
{
    protected $model = RequestLog::class;

    public function definition(): array
    {
        return [
            'request_id' => $this->faker->uuid(),
            'method' => $this->faker->randomElement(['GET', 'POST', 'PUT', 'DELETE']),
            'path' => $this->faker->url(),
            'response_status' => $this->faker->randomElement([200, 201, 400, 404, 500]),
            'ip_address' => $this->faker->ipv4(),
            'response_time' => $this->faker->numberBetween(10, 5000),
        ];
    }
}
