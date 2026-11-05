<?php

namespace App\Modules\Inventory\Application;

use RuntimeException;

/**
 * 跨租户库存调整被拒绝（矩阵第 3 项：对象越权/跨租户访问）。
 * 目标 variant 不属于当前可信 TenantContext 的租户。
 */
final class CrossTenantStockAdjustmentException extends RuntimeException
{
    public function __construct(
        public readonly int $contextTenantId,
        public readonly int $variantId,
    ) {
        parent::__construct(sprintf('Variant %d does not belong to tenant %d.', $variantId, $contextTenantId));
    }
}
