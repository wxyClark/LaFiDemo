<?php

namespace App\Modules\Tenancy\Application;

use App\Models\User;
use App\Modules\Tenancy\Domain\Tenant;
use Illuminate\Validation\UnauthorizedException;

/**
 * 租户（平台侧资源）授权基线（ADR-0001 c 条：跨租户/平台的 Policy 归 Tenancy 模块；
 * testing-security.md §3：默认拒绝访问，最小权限）。
 *
 * 基线阶段尚无角色/membership 模型：所有动作默认拒绝。Filament 页面/按钮的
 * 隐藏不构成授权——跨租户管理操作落地时必须经由本 Policy 的显式放行 +
 * 独立平台权限 + 审计记录（AGENTS.md §1、architecture.md §4）。
 */
class TenantPolicy
{
    /**
     * 查看租户列表（平台运营场景）：基线默认拒绝，待 RBAC 落地。
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * 查看单个租户：基线默认拒绝，待 RBAC/membership 落地。
     */
    public function view(User $user, Tenant $tenant): bool
    {
        return false;
    }

    /**
     * 编辑租户：基线默认拒绝，待 RBAC 落地。
     */
    public function update(User $user, Tenant $tenant): bool
    {
        return false;
    }

    /**
     * 停用/启用租户（平台高危操作）：默认拒绝；放行时必须附原因与审计
     * （architecture.md §7 高风险操作要求）。
     *
     * @throws UnauthorizedException 基线阶段直接以授权异常表达拒绝，
     *                               避免"放行分支"被误实现为无审计通道。
     */
    public function disable(User $user, Tenant $tenant): void
    {
        throw new UnauthorizedException('Tenant status change requires platform authorization (not yet implemented).');
    }
}
