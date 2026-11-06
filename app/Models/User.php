<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Modules\Tenancy\Domain\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property string $name
 * @property string $email
 * @property string $password
 * @property UserRole $role
 * @property int|null $tenant_id
 */
#[Fillable(['name', 'email', 'password', 'role', 'tenant_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'tenant_id' => 'int',
        ];
    }

    /**
     * 面板访问授权（FilamentUser 契约，安全边界）。
     *
     * 双 Panel 基线：平台角色仅可进 Platform Panel；租户角色仅可进
     * Tenant Panel 且必须已绑定租户。隐藏入口不构成授权，故除本方法外，
     * 各面板入口还由 ResolveTenantContext 中间件做可信租户解析
     * （AGENTS.md §1）。RBAC/membership 落地后本方法改为查 memberships
     * 与角色权限表，users.role/tenant_id 退役。
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'platform' => $this->role === UserRole::Platform,
            'tenant' => $this->role === UserRole::Tenant && $this->tenant_id !== null,
            default => false,
        };
    }
}
