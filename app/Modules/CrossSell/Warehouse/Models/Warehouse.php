<?php

declare(strict_types=1);

namespace App\Modules\CrossSell\Warehouse\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 仓库模型
 */
class Warehouse extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'cross_sell_warehouses';

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'address',
        'country',
        'city',
        'contact_name',
        'contact_phone',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    /**
     * 关联租户
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * 关联库存
     */
    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }
}
