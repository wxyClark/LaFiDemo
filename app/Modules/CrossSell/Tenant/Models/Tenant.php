<?php

declare(strict_types=1);

namespace App\Modules\CrossSell\Tenant\Models;

use App\Modules\CrossSell\Product\Models\User;
use App\Modules\CrossSell\Tenant\Enums\TenantPlan;
use App\Modules\CrossSell\Tenant\Enums\TenantStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 租户模型
 *
 * 管理SaaS平台的租户信息，包括套餐、试用期、状态等
 */
class Tenant extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'cross_sell_tenants';

    protected $fillable = [
        'name',
        'slug',
        'email',
        'phone',
        'plan',
        'trial_ends_at',
        'plan_starts_at',
        'plan_expires_at',
        'status',
        'settings',
    ];

    protected $hidden = [
        'settings',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'plan_starts_at' => 'datetime',
        'plan_expires_at' => 'datetime',
        'plan' => TenantPlan::class,
        'status' => TenantStatus::class,
        'settings' => 'array',
    ];

    /**
     * 关联用户
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * 关联部门
     */
    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    /**
     * 关联角色
     */
    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    /**
     * 关联仓库
     */
    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class);
    }

    /**
     * 关联平台配置
     */
    public function platformConfigs(): HasMany
    {
        return $this->hasMany(PlatformConfig::class);
    }

    /**
     * 检查是否在试用期
     */
    public function isOnTrial(): bool
    {
        return $this->plan === TenantPlan::Free
            && $this->trial_ends_at
            && $this->trial_ends_at->isFuture();
    }

    /**
     * 检查试用期是否已过
     */
    public function isTrialExpired(): bool
    {
        return $this->plan === TenantPlan::Free
            && $this->trial_ends_at
            && $this->trial_ends_at->isPast();
    }

    /**
     * 获取默认仓库
     */
    public function getDefaultWarehouse(): ?Warehouse
    {
        /** @var Warehouse|null $warehouse */
        $warehouse = $this->warehouses()->where('is_default', true)->first();

        return $warehouse;
    }
}
