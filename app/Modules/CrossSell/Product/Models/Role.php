<?php

declare(strict_types=1);

namespace App\Modules\CrossSell\Product\Models;

use App\Modules\CrossSell\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * 角色模型
 */
class Role extends Model
{
    use HasFactory;

    protected $table = 'cross_sell_roles';

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'permissions',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    /**
     * 关联租户
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * 关联用户
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'cross_sell_user_roles');
    }

    /**
     * 检查是否有指定权限
     */
    public function hasPermission(string $permission): bool
    {
        if (in_array('*', $this->permissions ?? [])) {
            return true;
        }

        return in_array($permission, $this->permissions ?? []);
    }
}
