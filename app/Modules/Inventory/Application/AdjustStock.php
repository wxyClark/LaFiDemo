<?php

namespace App\Modules\Inventory\Application;

use App\Modules\Catalog\Application\ProductVariantQueries;
use App\Modules\Inventory\Domain\StockMovementType;
use App\Modules\Inventory\Infrastructure\Stock;
use App\Modules\Inventory\Infrastructure\StockLedger;
use App\Modules\Tenancy\Application\TenantContext;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\QueryException;
use RuntimeException;
use Throwable;

/**
 * 库存调整应用服务（Inventory 模块公开写入口；ADR-0001 e 条：跨模块写入
 * 只能调用本用例，不得直接操作 stock / stock_ledger 表）。
 *
 * 正确性机制（data-and-cache.md §1/§2、AGENTS.md §4）：
 * - 单库事务内条件更新（UNIQUE 行 + WHERE on_hand >= ? 条件扣减），
 *   检查受影响行数；Redis 锁不作为正确性机制（仅作可选的去重协调）；
 * - 只追加 stock_ledger 流水，幂等键作用域 = 租户 + 业务事件键，
 *   UNIQUE (tenant_id, idempotency_key) 为数据库兜底防重；
 * - 每次调整记录 who（operator）/when（created_at，UTC）/why（reason）/
 *   source ref（reference_type/reference_id），可审计。
 *
 * 引擎说明：条件更新与唯一约束为 MySQL/SQLite 通用语法；真实并发
 * （行锁、不超卖）语义以 CI 的 MySQL 结果为放行依据
 * （docs/rules/testing-security.md "测试与迁移的数据库引擎一致性"）。
 */
final class AdjustStock
{
    public function __construct(private readonly ProductVariantQueries $variants) {}

    public function adjust(TenantContext $tenant, StockAdjustment $input): StockAdjustmentResult
    {
        $tenantId = $tenant->id();

        // 跨租户写拒绝（矩阵第 3 项）：variant 归属校验走 Catalog 公开
        // 只读投影，不直接查询 Catalog 内部模型（ADR-0001 e 条）
        if (! $this->variants->variantBelongsTo($tenantId, $input->variantId)) {
            throw new CrossTenantStockAdjustmentException($tenantId, $input->variantId);
        }

        // 并发唯一键冲突（同键竞争、首建 stock 行竞争）有限重试：
        // 整个用例幂等（重试即回到幂等命中检查），上限 3 次
        $attempt = 0;

        do {
            try {
                return $this->runInTransaction($tenantId, $input);
            } catch (QueryException $exception) {
                // 唯一约束冲突（SQLSTATE 23000，MySQL 1062 / SQLite UNIQUE）：
                // 幂等键或 stock 行首建竞争，回滚后重试
                if (self::isUniqueConstraintViolation($exception)) {
                    $attempt++;

                    continue;
                }

                throw $exception;
            }
        } while ($attempt < 3);

        throw new RuntimeException('Stock adjustment failed after retrying unique-conflict races.');
    }

    private function runInTransaction(int $tenantId, StockAdjustment $input): StockAdjustmentResult
    {
        return $this->connection()->transaction(function (ConnectionInterface $db) use ($tenantId, $input): StockAdjustmentResult {
            // 1. 幂等命中（读路径）：同租户同业务事件键已有流水 → 返回首次结果，
            //    不重复扣减/增加（矩阵第 4 项：重复请求幂等）
            $existing = $this->findLedgerEntry($tenantId, $input->idempotencyKey);

            if ($existing !== null) {
                return new StockAdjustmentResult(
                    $input->variantId,
                    (int) $existing->quantity_before,
                    (int) $existing->quantity_after,
                    true,
                );
            }

            // 2. 定位/首建 stock 行（首建竞争由外层唯一键重试兜底）
            $stock = $this->findOrCreateStockRow($tenantId, $input->variantId);
            $before = (int) $stock->on_hand;

            // 3. 条件更新（行级原子扣减，UNIQUE 行 + 库存条件；
            //    引擎无关：不使用 SELECT ... FOR UPDATE）
            if ($input->movement === StockMovementType::Out) {
                $affected = $stock->newQuery()
                    ->whereKey($stock->getKey())
                    ->where('tenant_id', $tenantId)
                    ->where('on_hand', '>=', $input->quantity)
                    ->update([
                        'on_hand' => $db->raw('on_hand - '.$input->quantity),
                        'version' => $db->raw('version + 1'),
                    ]);

                // 受影响 0 行 = 余额不足（矩阵第 2 项：状态不可转换/输入非法）
                if ($affected === 0) {
                    throw new InsufficientStockException($before, $input->quantity);
                }

                $after = $before - $input->quantity;
            } else {
                $affected = $stock->newQuery()
                    ->whereKey($stock->getKey())
                    ->where('tenant_id', $tenantId)
                    ->update([
                        'on_hand' => $db->raw('on_hand + '.$input->quantity),
                        'version' => $db->raw('version + 1'),
                    ]);

                if ($affected === 0) {
                    throw new RuntimeException('Stock row disappeared during in-movement update.');
                }

                $after = $before + $input->quantity;
            }

            // 4. 只追加流水（含幂等键与审计字段；唯一键为数据库兜底）
            $this->appendLedgerEntry($tenantId, $input, $before, $after);

            return new StockAdjustmentResult($input->variantId, $before, $after, false);
        });
    }

    private function connection(): ConnectionInterface
    {
        return Stock::query()->getConnection();
    }

    /**
     * 引擎无关的唯一约束冲突判定：SQLSTATE 23000（MySQL 1062 / SQLite UNIQUE
     * constraint failed 均为 23000），不依赖驱动特有的错误码常量。
     */
    private static function isUniqueConstraintViolation(Throwable $exception): bool
    {
        $pdoException = $exception instanceof QueryException ? ($exception->getPrevious() ?? null) : null;

        if ($pdoException instanceof \PDOException && $pdoException->errorInfo !== null) {
            return str_starts_with((string) $pdoException->errorInfo[0], '23000');
        }

        return false;
    }

    private function findLedgerEntry(int $tenantId, string $idempotencyKey): ?StockLedger
    {
        return StockLedger::query()
            ->where('tenant_id', $tenantId)
            ->where('idempotency_key', $idempotencyKey)
            ->first();
    }

    private function findOrCreateStockRow(int $tenantId, int $variantId): Stock
    {
        $stock = Stock::query()
            ->where('tenant_id', $tenantId)
            ->where('variant_id', $variantId)
            ->first();

        if ($stock !== null) {
            return $stock;
        }

        return Stock::query()->create([
            'tenant_id' => $tenantId,
            'variant_id' => $variantId,
            'on_hand' => 0,
            'version' => 0,
        ]);
    }

    private function appendLedgerEntry(int $tenantId, StockAdjustment $input, int $before, int $after): void
    {
        StockLedger::query()->create([
            'tenant_id' => $tenantId,
            'variant_id' => $input->variantId,
            'movement_type' => $input->movement->value,
            'quantity_delta' => $input->movement === StockMovementType::In
                ? $input->quantity
                : -$input->quantity,
            'quantity_before' => $before,
            'quantity_after' => $after,
            'idempotency_key' => $input->idempotencyKey,
            'reference_type' => $input->referenceType,
            'reference_id' => $input->referenceId,
            'operator_type' => $input->operatorType,
            'operator_id' => $input->operatorId,
            'reason' => $input->reason,
        ]);
    }
}
