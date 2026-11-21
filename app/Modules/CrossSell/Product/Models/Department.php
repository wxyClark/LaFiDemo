<?php

declare(strict_types=1);

namespace App\Modules\CrossSell\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 部门模型
 */
class Department extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'cross_sell_departments';

    protected $fillable = [
        'tenant_id',
        'name',
        'parent_id',
        'manager_id',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    /**
     * 关联租户
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * 关联上级部门
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'parent_id');
    }

    /**
     * 关联子部门
     */
    public function children(): HasMany
    {
        return $this->hasMany(Department::class, 'parent_id');
    }

    /**
     * 关联部门成员
     */
    public function members(): HasMany
    {
        return $this->hasMany(User::class, 'department_id');
    }
}
