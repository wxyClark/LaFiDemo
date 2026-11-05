<?php

use App\Modules\Catalog\Application\ProductVariantQueries;
use App\Modules\Catalog\Infrastructure\ProductVariant;
use App\Modules\Inventory\Application\AdjustStock;
use App\Modules\Inventory\Application\CrossTenantStockAdjustmentException;
use App\Modules\Inventory\Application\InsufficientStockException;
use App\Modules\Inventory\Application\StockAdjustment;
use App\Modules\Inventory\Application\StockAdjustmentResult;
use App\Modules\Inventory\Domain\StockMovementType;
use App\Modules\Inventory\Infrastructure\Stock;
use App\Modules\Inventory\Infrastructure\StockLedger;
use App\Modules\Tenancy\Application\TenantContext;
use App\Modules\Tenancy\Domain\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function adjustStock(): AdjustStock
{
    return new AdjustStock(new ProductVariantQueries);
}

function inbound(int $variantId, int $quantity, string $key, string $reason = 'supplier delivery', ?int $operatorId = null): StockAdjustment
{
    return new StockAdjustment(
        variantId: $variantId,
        quantity: $quantity,
        movement: StockMovementType::In,
        idempotencyKey: $key,
        reason: $reason,
        operatorType: 'system',
        operatorId: $operatorId,
    );
}

function outbound(int $variantId, int $quantity, string $key, string $reason = 'order fulfilment', ?int $operatorId = null, ?string $referenceType = null, ?int $referenceId = null): StockAdjustment
{
    return new StockAdjustment(
        variantId: $variantId,
        quantity: $quantity,
        movement: StockMovementType::Out,
        idempotencyKey: $key,
        reason: $reason,
        operatorType: 'system',
        operatorId: $operatorId,
        referenceType: $referenceType,
        referenceId: $referenceId,
    );
}

function stockOf(int $tenantId, int $variantId): ?Stock
{
    return Stock::where('tenant_id', $tenantId)->where('variant_id', $variantId)->first();
}

/** 成功调整：入库首建 stock 行并追加审计流水（矩阵 1）。 */
test('a successful inbound adjustment creates the stock row and appends an audited ledger entry', function (): void {
    $tenant = Tenant::factory()->create();
    $variant = ProductVariant::factory()->forTenant($tenant)->create();
    $service = adjustStock();

    $result = $service->adjust(
        TenantContext::forTenant($tenant->id),
        inbound($variant->id, 10, 'evt-stock-001', 'supplier delivery', operatorId: 7),
    );

    expect($result)
        ->toBeInstanceOf(StockAdjustmentResult::class)
        ->and([$result->duplicated, $result->quantityBefore, $result->quantityAfter])
        ->toEqual([false, 0, 10]);

    $stock = stockOf($tenant->id, $variant->id);
    expect($stock)->not->toBeNull()
        ->and((int) $stock->on_hand)->toBe(10)
        ->and((int) $stock->version)->toBe(1);

    $ledger = StockLedger::where('tenant_id', $tenant->id)->where('variant_id', $variant->id)->get();
    expect($ledger)->toHaveCount(1)
        ->and([
            (int) $ledger->first()->quantity_delta,
            $ledger->first()->operator_type,
            (int) $ledger->first()->operator_id,
            $ledger->first()->reason,
            $ledger->first()->idempotency_key,
        ])->toEqual([10, 'system', 7, 'supplier delivery', 'evt-stock-001']);

    // 二次入库叠加（不同业务事件键）
    $second = $service->adjust(
        TenantContext::forTenant($tenant->id),
        inbound($variant->id, 5, 'evt-stock-002'),
    );
    expect($second->quantityBefore)->toBe(10)
        ->and($second->quantityAfter)->toBe(15)
        ->and((int) stockOf($tenant->id, $variant->id)->on_hand)->toBe(15);
});

/** 成功调整：出库扣减（矩阵 1）。 */
test('a successful outbound adjustment deducts available stock', function (): void {
    $tenant = Tenant::factory()->create();
    $variant = ProductVariant::factory()->forTenant($tenant)->create();
    $stock = Stock::factory()->forVariant($tenant, $variant)->available(20)->create();
    $service = adjustStock();

    $result = $service->adjust(
        TenantContext::forTenant($tenant->id),
        outbound($variant->id, 8, 'evt-out-001'),
    );

    expect($result->duplicated)->toBeFalse()
        ->and($result->quantityBefore)->toBe(20)
        ->and($result->quantityAfter)->toBe(12)
        ->and((int) $stock->refresh()->on_hand)->toBe(12)
        ->and(StockLedger::where('tenant_id', $tenant->id)->where('idempotency_key', 'evt-out-001')->first())
        ->not->toBeNull();
});

/** 缺货拒绝：出库超过可用量被拒绝，事务回滚，无孤立流水（矩阵 2/7）。 */
test('an outbound adjustment exceeding available stock is rejected and rolled back without a ledger entry', function (): void {
    $tenant = Tenant::factory()->create();
    $variant = ProductVariant::factory()->forTenant($tenant)->create();
    Stock::factory()->forVariant($tenant, $variant)->available(4)->create();
    $service = adjustStock();

    $attempts = fn () => $service->adjust(
        TenantContext::forTenant($tenant->id),
        outbound($variant->id, 5, 'evt-out-short'),
    );

    expect($attempts)->toThrow(InsufficientStockException::class)
        ->and((int) stockOf($tenant->id, $variant->id)->on_hand)->toBe(4, 'stock balance must be unchanged after rejection')
        ->and(StockLedger::count())->toBe(0, 'no orphan ledger entry may survive a rolled-back adjustment');
});

/** 缺货拒绝：variant 尚无库存行（on_hand=0）时出库被拒。 */
test('outbound adjustment is rejected when the variant has no stock row yet', function (): void {
    $tenant = Tenant::factory()->create();
    $variant = ProductVariant::factory()->forTenant($tenant)->create();
    $service = adjustStock();

    $attempts = fn () => $service->adjust(
        TenantContext::forTenant($tenant->id),
        outbound($variant->id, 1, 'evt-out-no-stock'),
    );

    expect($attempts)->toThrow(InsufficientStockException::class)
        ->and(StockLedger::count())->toBe(0, 'no ledger entry may survive a rolled-back adjustment');
});

/** 跨租户拒绝（读维度）：其他租户的 variant 不属于当前租户（矩阵 3）。 */
test('reading another tenant variant is rejected (read dimension)', function (): void {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $variantOfA = ProductVariant::factory()->forTenant($tenantA)->create();
    $queries = new ProductVariantQueries;

    expect($queries->variantBelongsTo($tenantB->id, $variantOfA->id))->toBeFalse()
        ->and($queries->variantBelongsTo($tenantA->id, $variantOfA->id))->toBeTrue();
});

/** 跨租户拒绝（导出/列表维度）：导出投影只返回当前租户数据（矩阵 3）。 */
test('the export projection of variants returns only the requesting tenant data (export dimension)', function (): void {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $variantOfA = ProductVariant::factory()->forTenant($tenantA)->create();
    ProductVariant::factory()->forTenant($tenantA)->create();
    $variantsOfB = ProductVariant::factory()->forTenant($tenantB)->create();
    $queries = new ProductVariantQueries;

    $exportForA = $queries->variantsOf($tenantA->id);
    $exportForB = $queries->variantsOf($tenantB->id);

    $variantsOfAIds = $exportForA->pluck('id')->all();
    $variantsOfBIds = $exportForB->pluck('id')->all();

    expect(in_array($variantOfA->id, $variantsOfAIds, true))->toBeTrue('tenant A export must contain its own variant')
        ->and(in_array($variantsOfB->id, $variantsOfAIds, true))->toBeFalse('tenant A export must not leak tenant B variants')
        ->and(in_array($variantsOfB->id, $variantsOfBIds, true))->toBeTrue('tenant B export must contain its own variant')
        ->and(in_array($variantOfA->id, $variantsOfBIds, true))->toBeFalse('tenant B export must not leak tenant A variants');
});

/** 跨租户拒绝（写维度）：调整其他租户的 variant 被拒绝且双方数据不变（矩阵 3）。 */
test('writing stock of another tenant variant is rejected (write dimension)', function (): void {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $variantOfA = ProductVariant::factory()->forTenant($tenantA)->create();
    Stock::factory()->forVariant($tenantA, $variantOfA)->available(3)->create();
    $service = adjustStock();

    $attempts = fn () => $service->adjust(
        TenantContext::forTenant($tenantB->id),
        outbound($variantOfA->id, 1, 'evt-cross-tenant'),
    );

    expect($attempts)->toThrow(CrossTenantStockAdjustmentException::class)
        ->and((int) stockOf($tenantA->id, $variantOfA->id)->on_hand)->toBe(3, 'tenant A balance must not change')
        ->and(StockLedger::count())->toBe(0, 'no ledger entry may be written across tenants');
});

/** 幂等重放：同租户同业务事件键二次提交不重复扣减（矩阵 4）。 */
test('replaying the same idempotency key does not deduct twice', function (): void {
    $tenant = Tenant::factory()->create();
    $variant = ProductVariant::factory()->forTenant($tenant)->create();
    Stock::factory()->forVariant($tenant, $variant)->available(10)->create();
    $service = adjustStock();

    $first = $service->adjust(
        TenantContext::forTenant($tenant->id),
        outbound($variant->id, 6, 'evt-idem-001', 'order #42 fulfilment', referenceType: 'order'),
    );
    $replay = $service->adjust(
        TenantContext::forTenant($tenant->id),
        outbound($variant->id, 6, 'evt-idem-001', 'order #42 fulfilment'),
    );

    expect($first->duplicated)->toBeFalse()
        ->and($replay->duplicated)->toBeTrue()
        ->and($replay->quantityBefore)->toBe(10)
        ->and($replay->quantityAfter)->toBe(4)
        ->and((int) stockOf($tenant->id, $variant->id)->on_hand)->toBe(4, 'balance must reflect a single deduction')
        ->and(StockLedger::where('idempotency_key', 'evt-idem-001')->count())->toBe(1, 'ledger must stay append-only');
});

/** 幂等键作用域 = 租户 + 业务事件键：其他租户同键不受影响（矩阵 4）。 */
test('the same idempotency key in another tenant is independent', function (): void {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $variantOfA = ProductVariant::factory()->forTenant($tenantA)->create();
    $variantOfB = ProductVariant::factory()->forTenant($tenantB)->create();
    Stock::factory()->forVariant($tenantA, $variantOfA)->available(10)->create();
    Stock::factory()->forVariant($tenantB, $variantOfB)->available(10)->create();
    $service = adjustStock();

    $service->adjust(
        TenantContext::forTenant($tenantA->id),
        outbound($variantOfA->id, 3, 'evt-shared-key', 'order #1'),
    );
    $resultB = $service->adjust(
        TenantContext::forTenant($tenantB->id),
        outbound($variantOfB->id, 3, 'evt-shared-key', 'order #2'),
    );

    expect($resultB->duplicated)->toBeFalse()
        ->and((int) stockOf($tenantA->id, $variantOfA->id)->on_hand)->toBe(7)
        ->and((int) stockOf($tenantB->id, $variantOfB->id)->on_hand)->toBe(7)
        ->and(StockLedger::where('idempotency_key', 'evt-shared-key')->count())->toBe(2, 'one ledger entry per tenant scope');
});

/** 事务回滚无部分变更：出库拒绝路径后库存与流水均无残留（矩阵 7，补充断言）。 */
test('a failed adjustment leaves no partial stock or ledger changes (rollback invariants)', function (): void {
    $tenant = Tenant::factory()->create();
    $variant = ProductVariant::factory()->forTenant($tenant)->create();
    $stock = Stock::factory()->forVariant($tenant, $variant)->available(2)->create();
    $service = adjustStock();

    $attempts = function () use ($service, $tenant, $variant): void {
        $service->adjust(
            TenantContext::forTenant($tenant->id),
            outbound($variant->id, 99, 'evt-rollback'),
        );
    };

    expect($attempts)->toThrow(InsufficientStockException::class)
        ->and((int) $stock->refresh()->on_hand)->toBe(2)
        ->and((int) $stock->refresh()->version)->toBe(0, 'version must not advance on a rejected adjustment')
        ->and(StockLedger::count())->toBe(0);
});
