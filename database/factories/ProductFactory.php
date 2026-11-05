<?php

namespace Database\Factories;

use App\Modules\Catalog\Infrastructure\Product;
use App\Modules\Tenancy\Domain\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'slug' => fake()->unique()->slug(2, '-'),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'status' => 'active',
        ];
    }
}
