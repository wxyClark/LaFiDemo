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
    /**
     * 模块模型不在 App\Models 下（ADR-0001 a/g 条），显式指定所属模型。
     */
    public function modelName(): string
    {
        return Product::class;
    }

    /**
     * @return array<string, mixed>
     */
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

    /**
     * 指定租户创建商品（同租户驱动）。
     */
    public function forTenant(Tenant $tenant): static
    {
        return $this->state(['tenant_id' => $tenant->getKey()]);
    }
}
