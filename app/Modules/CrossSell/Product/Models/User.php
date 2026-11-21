<?php

declare(strict_types=1);

namespace App\Modules\CrossSell\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * 用户模型（CrossSell SaaS）
 */
class User extends Authenticatable implements JWTSubject
{
    use HasFactory;
    use Notifiable;
    use SoftDeletes;

    protected $table = 'cross_sell_users';

    protected $fillable = [
        'tenant_id',
        'department_id',
        'name',
        'email',
        'phone',
        'avatar',
        'password',
        'email_verified_at',
        'status',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'last_login_at' => 'datetime',
    ];

    /**
     * 关联租户
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * 关联部门
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * 关联角色
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'cross_sell_user_roles');
    }

    /**
     * 检查是否有指定权限
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->roles->contains('name', 'admin')) {
            return true;
        }

        return $this->roles->contains('permissions', $permission);
    }

    /**
     * 检查是否有指定角色
     */
    public function hasRole(string $role): bool
    {
        return $this->roles->contains('name', $role);
    }

    /**
     * Get the identifier that will be stored in the JWT claim's subject.
     */
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    /**
     * Return a key-value array, containing any custom claims to be added to the JWT.
     */
    public function getJWTCustomClaims(): array
    {
        return [
            'tenant_id' => $this->tenant_id,
            'email' => $this->email,
        ];
    }
}
