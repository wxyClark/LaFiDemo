<?php

declare(strict_types=1);

namespace App\Modules\Domains\Catalog\Database\Factories;

use App\Modules\Domains\Catalog\Enums\ProductStatus;
use App\Modules\Domains\Catalog\Models\Product;
use App\Modules\Domains\Catalog\Models\Sku;
use Illuminate\Database\Eloquent\Factories\Factory;

class SkuFactory extends Factory
{
    protected $model = Sku::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'specs' => ['color' => fake()->colorName()],
            'price' => fake()->randomFloat(2, 1, 999),
            'stock' => fake()->numberBetween(0, 100),
            'status' => ProductStatus::ACTIVE->value,
        ];
    }
}
