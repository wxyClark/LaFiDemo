<?php

declare(strict_types=1);

namespace App\Modules\Domains\Base\ApiTesting\Database\Factories;

use App\Modules\Domains\Base\ApiTesting\Models\ApiFunction;
use App\Modules\Domains\Base\ApiTesting\Models\ApiModule;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApiFunctionFactory extends Factory
{
    protected $model = ApiFunction::class;

    public function definition(): array
    {
        return [
            'module_id' => ApiModule::factory(),
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->sentence(),
            'sort_order' => 0,
        ];
    }
}
