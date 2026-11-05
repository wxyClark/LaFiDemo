<?php

namespace App\Modules\Inventory\Application;

/**
 * 库存调整用例结果（应用层 DTO）。
 *
 * duplicated=true 表示本次调用命中的是既有幂等记录（同租户同业务事件键的
 * 首次执行结果，重放不再产生副作用）；quantityBefore/quantityAfter 为首次
 * 执行时的快照（幂等重放返回的是"已有结果"，不是重放时刻的实时余额——
 * 实时余额经查询接口读取）。
 */
final readonly class StockAdjustmentResult
{
    public function __construct(
        public int $variantId,
        public int $quantityBefore,
        public int $quantityAfter,
        public bool $duplicated,
    ) {}
}
