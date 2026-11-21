<?php

declare(strict_types=1);

namespace App\Modules\Domains\Catalog\Database\Factories;

use App\Modules\Domains\Catalog\Enums\ProductStatus;
use App\Modules\Domains\Catalog\Models\ProductBundle;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductBundleFactory extends Factory
{
    protected $model = ProductBundle::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true).'套餐',
            'price' => fake()->randomFloat(2, 10, 999),
            'status' => ProductStatus::ACTIVE->value,
        ];
    }
}
