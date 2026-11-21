<?php

declare(strict_types=1);

namespace App\Modules\CrossSell\Warehouse\Models;

use App\Modules\CrossSell\Product\Models\SKU;
use App\Modules\CrossSell\Tenant\Models\Tenant;
use App\Domains\Notification\Events\LowStockDetected;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 库存模型
 */
class Inventory extends Model
{
    use HasFactory;

    protected $table = 'cross_sell_inventories';

    protected $fillable = [
        'tenant_id',
        'sku_id',
        'warehouse_id',
        'quantity',
        'reserved_quantity',
        'available_quantity',
        'min_stock',
        'max_stock',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'reserved_quantity' => 'integer',
        'available_quantity' => 'integer',
        'min_stock' => 'integer',
        'max_stock' => 'integer',
    ];

    /**
     * 关联租户
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * 关联SKU
     */
    public function sku(): BelongsTo
    {
        return $this->belongsTo(SKU::class);
    }

    /**
     * 关联仓库
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * 扣减库存
     */
    public function deduct(int $quantity): bool
    {
        if ($this->available_quantity < $quantity) {
            return false;
        }

        $this->decrement('quantity', $quantity);
        $this->decrement('available_quantity', $quantity);

        $this->checkLowStock();

        return true;
    }

    /**
     * 恢复库存
     */
    public function restore(int $quantity): bool
    {
        $this->increment('quantity', $quantity);
        $this->increment('available_quantity', $quantity);

        return true;
    }

    /**
     * 检查是否低库存
     */
    public function isLowStock(): bool
    {
        return $this->quantity <= $this->min_stock;
    }

    /**
     * 检查并触发低库存预警
     */
    protected function checkLowStock(): void
    {
        if ($this->isLowStock()) {
            LowStockDetected::dispatch(
                sku: $this->sku,
                currentStock: $this->quantity,
                threshold: $this->min_stock,
            );
        }
    }
}
