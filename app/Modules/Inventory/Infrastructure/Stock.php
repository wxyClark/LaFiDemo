<?php

namespace App\Modules\Inventory\Infrastructure;

use Database\Factories\StockFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 库存余额读模型（Inventory 模块；blueprint §5.3 inventory_balances 基线，
 * 表名取任务基线口径 stock）。
 *
 * 数量一律 UNSIGNED INT（SKU 为整件库存单位，禁止浮点）。
 * 本模型是投影：正确性由应用用例的条件更新/行锁与只追加流水 stock_ledger
 * 保障，禁止绕过用例直接改写本表（ADR-0001 e 条：跨模块写入只走
 * Inventory Application 公开接口）。
 */
#[Fillable(['tenant_id', 'variant_id', 'on_hand', 'version'])]
#[UseFactory(StockFactory::class)]
class Stock extends Model
{
    use HasFactory;

    /**
     * 表名显式声明（stock 为单数基线表名，防止 Eloquent 默认复数推导歧义）
     */
    protected $table = 'stock';

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
            'on_hand' => 'int',
            'version' => 'int',
        ];
    }
}
