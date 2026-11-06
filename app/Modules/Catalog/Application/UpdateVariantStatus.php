<?php

namespace App\Modules\Catalog\Application;

use App\Modules\Catalog\Domain\VariantStatus;
use App\Modules\Catalog\Infrastructure\ProductVariant;
use App\Modules\Tenancy\Application\CurrentTenant;
use Illuminate\Support\Facades\Log;

/**
 * 更新变体状态（Catalog 模块公开写入口）。
 *
 * 租户面板资源调用；租户范围经 TenantContext 限定，并校验变体归属
 * 当前租户（AGENTS.md §1 租户隔离；ADR-0001 e 条：写入口在模块内）。
 */
final class UpdateVariantStatus
{
    public function __construct(private readonly CurrentTenant $currentTenant) {}

    public function __invoke(int $variantId, VariantStatus $newStatus, string $reason): ProductVariant
    {
        $tenantId = $this->currentTenant->id();

        /** @var ProductVariant $variant */
        $variant = ProductVariant::query()
            ->where('tenant_id', $tenantId)
            ->findOrFail($variantId);

        $from = $variant->status;

        if ($from !== $newStatus) {
            $variant->status = $newStatus;
            $variant->save();

            Log::info('catalog.variant.status.changed', [
                'tenant_id' => $tenantId,
                'variant_id' => $variant->id,
                'from' => $from->value,
                'to' => $newStatus->value,
                'reason' => $reason,
            ]);
        }

        return $variant;
    }
}
