<?php

namespace App\Modules\Catalog\Application;

use App\Modules\Domains\Catalog\Enums\ProductStatus;
use App\Modules\Domains\Catalog\Models\Product;
use App\Modules\Tenancy\Application\CurrentTenant;
use App\Modules\Tenancy\Application\TenantContext;
use Illuminate\Support\Facades\Log;

/**
 * 更新商品状态（Catalog 模块公开写入口）。
 *
 * 租户面板资源调用；租户范围经 TenantContext 限定，并校验商品归属
 * 当前租户（AGENTS.md §1 租户隔离；ADR-0001 e 条：写入口在模块内）。
 */
final class UpdateProductStatus
{
    public function __construct(private readonly CurrentTenant $currentTenant) {}

    public function __invoke(int $productId, ProductStatus $newStatus, string $reason): Product
    {
        $tenantId = $this->currentTenant->id();

        /** @var Product $product */
        $product = Product::query()
            ->where('tenant_id', $tenantId)
            ->findOrFail($productId);

        $from = (string) $product->status;

        if ($from !== $newStatus->value) {
            $product->status = $newStatus->value;
            $product->save();

            Log::info('catalog.product.status.changed', [
                'tenant_id' => $tenantId,
                'product_id' => $product->id,
                'from' => $from,
                'to' => $newStatus->value,
                'reason' => $reason,
            ]);
        }

        return $product;
    }
}
