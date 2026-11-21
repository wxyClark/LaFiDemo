<?php

declare(strict_types=1);

namespace App\Modules\Domains\Base\ApiTesting\Database\Factories;

use App\Modules\Domains\Base\ApiTesting\Enums\HttpMethod;
use App\Modules\Domains\Base\ApiTesting\Models\ApiFunction;
use App\Modules\Domains\Base\ApiTesting\Models\ApiInterface;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApiInterfaceFactory extends Factory
{
    protected $model = ApiInterface::class;

    public function definition(): array
    {
        return [
            'function_id' => ApiFunction::factory(),
            'name' => fake()->unique()->words(2, true),
            'method' => HttpMethod::GET->value,
            'path' => '/api/'.fake()->slug(2),
            'headers' => null,
            'body_type' => null,
            'body_schema' => null,
            'description' => fake()->sentence(),
            'sort_order' => 0,
        ];
    }
}
