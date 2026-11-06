<?php

use App\Filament\Tenant\Resources\ProductResource;
use App\Filament\Tenant\Resources\VariantResource;
use App\Models\User;
use App\Modules\Catalog\Infrastructure\Product;
use App\Modules\Catalog\Infrastructure\ProductVariant;
use App\Modules\Tenancy\Application\CurrentTenant;
use App\Modules\Tenancy\Application\TenantContext;
use App\Modules\Tenancy\Domain\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** 租户面板资源范围（AGENTS.md §1 租户隔离）：资源查询按 CurrentTenant 过滤，
 *  只返回本租户记录（变体工厂会连带创建商品，故用包含/排除断言而非全量相等）。 */
test('tenant resources scope their queries to the resolved tenant', function (): void {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    $productA = Product::factory()->forTenant($tenantA)->create();
    $productB = Product::factory()->forTenant($tenantB)->create();

    $variantA = ProductVariant::factory()->forTenant($tenantA)->create();
    $variantB = ProductVariant::factory()->forTenant($tenantB)->create();

    app(CurrentTenant::class)->set(TenantContext::forTenant($tenantA->id));

    $productIds = ProductResource::getEloquentQuery()->pluck('id')->all();
    $variantIds = VariantResource::getEloquentQuery()->pluck('id')->all();

    expect(in_array($productA->id, $productIds, true))->toBeTrue()
        ->and(in_array($productB->id, $productIds, true))->toBeFalse()
        ->and(in_array($variantA->id, $variantIds, true))->toBeTrue()
        ->and(in_array($variantB->id, $variantIds, true))->toBeFalse();
});

/** 租户面板资源授权（Policy 记录级租户校验，防御纵深）。 */
test('tenant policies deny cross-tenant record access', function (): void {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    $productB = Product::factory()->forTenant($tenantB)->create();

    $tenantUser = User::factory()->tenantUser($tenantA)->create();

    app(CurrentTenant::class)->set(TenantContext::forTenant($tenantA->id));

    expect($tenantUser->can('view', $productB))->toBeFalse()
        ->and($tenantUser->can('update', $productB))->toBeFalse();
});
