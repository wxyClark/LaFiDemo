<?php

declare(strict_types=1);

namespace App\Modules\Domains\Base\ApiTesting\Database\Factories;

use App\Modules\Domains\Base\ApiTesting\Enums\TestStatus;
use App\Modules\Domains\Base\ApiTesting\Models\ApiEnvironment;
use App\Modules\Domains\Base\ApiTesting\Models\ApiInterface;
use App\Modules\Domains\Base\ApiTesting\Models\ApiTestCase;
use App\Modules\Domains\Base\ApiTesting\Models\ApiTestResult;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApiTestResultFactory extends Factory
{
    protected $model = ApiTestResult::class;

    public function definition(): array
    {
        return [
            'test_case_id' => ApiTestCase::factory(),
            'interface_id' => ApiInterface::factory(),
            'environment_id' => ApiEnvironment::factory(),
            'status' => TestStatus::PASS->value,
            'request_url' => fake()->url(),
            'request_method' => 'GET',
            'request_headers' => null,
            'request_body' => null,
            'response_status' => 200,
            'response_body' => null,
            'response_time' => $this->faker->numberBetween(10, 5000),
            'assertions' => null,
            'error_message' => null,
        ];
    }
}
