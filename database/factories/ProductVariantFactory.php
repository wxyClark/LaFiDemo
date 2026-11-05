<?php

namespace Database\Factories;

use App\Modules\Catalog\Infrastructure\Product;
use App\Modules\Catalog\Infrastructure\ProductVariant;
use App\Modules\Tenancy\Domain\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // 同一 Tenant 实例驱动 tenant_id 与关联 Product，保证 variant 与其
        // 商品归属同一租户（blueprint §3.2 同租户关联）
        $tenant = Tenant::factory()->create();

        return [
            'tenant_id' => $tenant->getKey(),
            'product_id' => Product::factory()->for($tenant)->create()->getKey(),
            'sku' => fake()->unique()->bothify('SKU-######'),
            'status' => 'active',
            'attributes_json' => null,
            'barcode' => null,
        ];
    }

    /**
     * 指定租户创建 variant（同租户驱动其 product）。
     */
    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (): array => [
            'tenant_id' => $tenant->id,
            'product_id' => Product::factory()->for($tenant)->create()->getKey(),
        ]);
    }
}
