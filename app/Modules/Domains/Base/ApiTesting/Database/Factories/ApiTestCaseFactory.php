<?php

declare(strict_types=1);

namespace App\Modules\Domains\Base\ApiTesting\Database\Factories;

use App\Modules\Domains\Base\ApiTesting\Models\ApiEnvironment;
use App\Modules\Domains\Base\ApiTesting\Models\ApiInterface;
use App\Modules\Domains\Base\ApiTesting\Models\ApiTestCase;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApiTestCaseFactory extends Factory
{
    protected $model = ApiTestCase::class;

    public function definition(): array
    {
        return [
            'interface_id' => ApiInterface::factory(),
            'environment_id' => ApiEnvironment::factory(),
            'name' => fake()->unique()->words(3, true),
            'headers' => null,
            'query_params' => null,
            'body' => null,
            'expected_status' => 200,
            'expected_data' => null,
            'expected_response_time' => null,
            'description' => fake()->sentence(),
            'sort_order' => 0,
        ];
    }
}
