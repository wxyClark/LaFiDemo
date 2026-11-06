<?php

use App\Modules\Catalog\Application\UpdateProductStatus;
use App\Modules\Catalog\Application\UpdateVariantStatus;
use App\Modules\Catalog\Domain\ProductStatus;
use App\Modules\Catalog\Domain\VariantStatus;
use App\Modules\Catalog\Infrastructure\Product;
use App\Modules\Catalog\Infrastructure\ProductVariant;
use App\Modules\Tenancy\Application\CurrentTenant;
use App\Modules\Tenancy\Application\TenantContext;
use App\Modules\Tenancy\Domain\Tenant;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** Catalog 状态用例：经 TenantContext 限定租户范围（AGENTS.md §1）。 */
test('the product status use case updates only the current tenant product', function (): void {
    $tenant = Tenant::factory()->create();
    $product = Product::factory()->forTenant($tenant)->create();

    app(CurrentTenant::class)->set(TenantContext::forTenant($tenant->id));

    $updated = app(UpdateProductStatus::class)((int) $product->id, ProductStatus::Disabled, '盘点');

    expect($updated->fresh()->status)->toBe(ProductStatus::Disabled);
});

test('the product status use case rejects a product outside the tenant', function (): void {
    $tenant = Tenant::factory()->create();
    $other = Tenant::factory()->create();
    $product = Product::factory()->forTenant($other)->create();

    app(CurrentTenant::class)->set(TenantContext::forTenant($tenant->id));

    expect(fn () => app(UpdateProductStatus::class)((int) $product->id, ProductStatus::Disabled, '盘点'))
        ->toThrow(ModelNotFoundException::class);
});

test('the variant status use case updates only the current tenant variant', function (): void {
    $tenant = Tenant::factory()->create();
    $variant = ProductVariant::factory()->forTenant($tenant)->create();

    app(CurrentTenant::class)->set(TenantContext::forTenant($tenant->id));

    $updated = app(UpdateVariantStatus::class)((int) $variant->id, VariantStatus::Disabled, '缺货');

    expect($updated->fresh()->status)->toBe(VariantStatus::Disabled);
});
