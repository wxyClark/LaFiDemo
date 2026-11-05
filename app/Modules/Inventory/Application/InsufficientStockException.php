<?php

namespace App\Modules\Inventory\Application;

use RuntimeException;

/**
 * 库存不足，出库调整被拒绝（AGENTS.md 矩阵第 2 项：状态不可转换）。
 * 抛出即回滚事务，不产生任何库存/流水变更。
 */
final class InsufficientStockException extends RuntimeException
{
    public function __construct(
        public readonly int $available,
        public readonly int $requested,
    ) {
        parent::__construct(sprintf('Insufficient stock: available=%d, requested=%d', $available, $requested));
    }
}
