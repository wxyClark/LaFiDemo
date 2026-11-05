<?php

namespace App\Modules\Inventory\Infrastructure;

use App\Modules\Inventory\Domain\StockMovementType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * 只追加库存流水账本（Inventory 模块；blueprint §5.3 inventory_movements 基线，
 * 表名取任务基线口径 stock_ledger）。
 *
 * 审计事实：只追加、不可更新/删除（data-and-cache.md §1/§2）。
 * 每次调整必须含来源、原因、幂等标识与操作者——由应用用例
 * （Inventory Application）统一写入，禁止入口直接写本表。
 */
#[Fillable([
    'tenant_id',
    'variant_id',
    'movement_type',
    'quantity_delta',
    'quantity_before',
    'quantity_after',
    'idempotency_key',
    'reference_type',
    'reference_id',
    'operator_type',
    'operator_id',
    'reason',
])]
class StockLedger extends Model
{
    /**
     * 审计事实表：不维护 updated_at，仅 created_at（UTC）
     */
    public const UPDATED_AT = null;

    public int $id;

    public int $tenant_id;

    public int $variant_id;

    public StockMovementType $movement_type;

    public int $quantity_delta;

    public int $quantity_before;

    public int $quantity_after;

    public string $idempotency_key;

    public ?string $reference_type;

    public ?int $reference_id;

    public string $operator_type;

    public ?int $operator_id;

    public string $reason;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'movement_type' => StockMovementType::class,
            'quantity_delta' => 'int',
            'quantity_before' => 'int',
            'quantity_after' => 'int',
            'tenant_id' => 'int',
            'variant_id' => 'int',
            'operator_id' => 'int',
            'reference_id' => 'int',
        ];
    }
}
