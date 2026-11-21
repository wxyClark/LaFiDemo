<?php

declare(strict_types=1);

namespace App\Modules\Domains\Catalog\Database\Factories;

use App\Modules\Domains\Catalog\Enums\ProductStatus;
use App\Modules\Domains\Catalog\Enums\ProductType;
use App\Modules\Domains\Catalog\Models\Product;
use App\Modules\Tenancy\Domain\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'tenant_id' => null,
            'category_id' => null,
            'type' => fake()->randomElement([ProductType::ENTITY, ProductType::VIRTUAL])->value,
            'status' => ProductStatus::ACTIVE->value,
            'description' => fake()->sentence(),
        ];
    }

    /**
     * 归属指定租户（租户面板资源与记录级授权的路径）。
     */
    public function forTenant(Tenant $tenant): static
    {
        return $this->state(['tenant_id' => $tenant->getKey()]);
    }
}
