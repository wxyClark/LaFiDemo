<?php

namespace App\Modules\Tenancy\Application;

use App\Models\User;
use App\Modules\Tenancy\Domain\Tenant;
use App\Modules\Tenancy\Domain\UserRole;

/**
 * 租户（平台侧资源）授权基线（ADR-0001 c 条：跨租户/平台的 Policy 归 Tenancy 模块；
 * testing-security.md §3：默认拒绝访问，最小权限）。
 *
 * 基线阶段：平台管理员（role=platform）可管理租户；其余一律拒绝。
 * 成员关系/RBAC 落地后：本分支改为查 memberships + 角色权限表，并按
 * 操作粒度收窄（viewAny/view/update/disable 各自独立权限点），
 * users.role 退役（见 0007 迁移注释与 UserRole 枚举注释）。
 */
class TenantPolicy
{
    /**
     * 查看租户列表：仅平台管理员（平台运营后台）。
     */
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Platform;
    }

    /**
     * 查看单个租户：仅平台管理员。
     */
    public function view(User $user, Tenant $tenant): bool
    {
        return $user->role === UserRole::Platform;
    }

    /**
     * 编辑租户：仅平台管理员。
     */
    public function update(User $user, Tenant $tenant): bool
    {
        return $user->role === UserRole::Platform;
    }

    /**
     * 停用/启用租户（平台高危操作）：仅平台管理员；放行时须附原因与审计
     * （UpdateTenantStatus 用例落结构化日志；architecture.md §7 高风险操作要求）。
     */
    public function disable(User $user, Tenant $tenant): bool
    {
        return $user->role === UserRole::Platform;
    }
}
