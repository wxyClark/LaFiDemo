<?php

namespace Database\Factories;

use App\Modules\Catalog\Infrastructure\ProductVariant;
use App\Modules\Inventory\Infrastructure\Stock;
use App\Modules\Tenancy\Domain\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Stock>
 */
class StockFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // 同一 Tenant 实例驱动 tenant_id 与 variant_id，保证库存行与其
        // SKU 归属同一租户
        $tenant = Tenant::factory()->create();

        return [
            'tenant_id' => $tenant->getKey(),
            'variant_id' => ProductVariant::factory()->forTenant($tenant)->create()->getKey(),
            'on_hand' => 0,
            'version' => 0,
        ];
    }

    /**
     * 指定租户与 variant 创建库存行（必须同租户）。
     */
    public function forVariant(Tenant $tenant, ProductVariant $variant): static
    {
        return $this->state([
            'tenant_id' => $tenant->getKey(),
            'variant_id' => $variant->getKey(),
        ]);
    }

    public function available(int $onHand): static
    {
        return $this->state(['on_hand' => $onHand]);
    }
}
