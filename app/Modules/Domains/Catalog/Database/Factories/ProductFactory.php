<?php

declare(strict_types=1);

namespace App\Modules\Domains\Catalog\Database\Factories;

use App\Modules\Domains\Catalog\Enums\ProductStatus;
use App\Modules\Domains\Catalog\Enums\ProductType;
use App\Modules\Domains\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'category_id' => null,
            'type' => fake()->randomElement([ProductType::ENTITY, ProductType::VIRTUAL])->value,
            'status' => ProductStatus::ACTIVE->value,
            'description' => fake()->sentence(),
        ];
    }
}
