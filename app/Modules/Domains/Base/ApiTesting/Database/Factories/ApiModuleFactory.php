<?php

declare(strict_types=1);

namespace App\Modules\Domains\Base\ApiTesting\Database\Factories;

use App\Modules\Domains\Base\ApiTesting\Models\ApiModule;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApiModuleFactory extends Factory
{
    protected $model = ApiModule::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->sentence(),
            'sort_order' => 0,
        ];
    }
}
