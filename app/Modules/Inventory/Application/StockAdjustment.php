<?php

namespace App\Modules\Inventory\Application;

use App\Modules\Inventory\Domain\StockMovementType;
use InvalidArgumentException;

/**
 * 库存调整用例输入（应用层 DTO，不依赖传输/基础设施对象——
 * docs/rules/application-flow-and-data-access.md §3）。
 *
 * quantity 为绝对数量（正整数）；方向由 movement 表达（in 增加 / out 扣减）。
 * 幂等键作用域 = 租户 + 业务事件键（data-and-cache.md §2），
 * 由调用方（订单、盘点、退货入口）按业务事件生成，本类只做格式校验。
 */
final readonly class StockAdjustment
{
    public function __construct(
        public int $variantId,
        public int $quantity,
        public StockMovementType $movement,
        public string $idempotencyKey,
        public string $reason,
        public string $operatorType = 'system',
        public ?int $operatorId = null,
        public ?string $referenceType = null,
        public ?int $referenceId = null,
    ) {
        if ($variantId <= 0) {
            throw new InvalidArgumentException('variantId must be a positive integer.');
        }

        if ($quantity <= 0) {
            throw new InvalidArgumentException('quantity must be a positive integer (movement direction is separate).');
        }

        if (trim($idempotencyKey) === '' || mb_strlen($idempotencyKey) > 128) {
            throw new InvalidArgumentException('idempotencyKey must be 1-128 characters.');
        }

        if (trim($reason) === '') {
            throw new InvalidArgumentException('reason is required for auditable stock adjustments.');
        }
    }
}
