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

    /**
     * 表名显式声明（stock_ledger，与 0006 迁移一致；
     * Eloquent 默认复数推导为 stock_ledgers，必须显式声明）
     */
    protected $table = 'stock_ledger';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'int',
            'tenant_id' => 'int',
            'variant_id' => 'int',
            'movement_type' => StockMovementType::class,
            'quantity_delta' => 'int',
            'quantity_before' => 'int',
            'quantity_after' => 'int',
            'operator_id' => 'int',
            'reference_id' => 'int',
        ];
    }
}
