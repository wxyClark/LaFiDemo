<?php

namespace App\Modules\Tenancy\Application;

use App\Modules\Tenancy\Domain\Tenant;
use App\Modules\Tenancy\Domain\TenantStatus;
use Illuminate\Support\Facades\Log;

/**
 * 更新租户启用状态（Tenancy 模块公开写入口；平台运营场景）。
 *
 * 平台管理员经 Platform Panel 调用；授权由 TenantPolicy::disable 的
 * 平台分支放行（AGENTS.md §1：隐藏入口不构成授权；入口 + Policy 双层）。
 * 高危操作（停用/启用租户）须附原因并审计——本基线以结构化日志记录
 * operator/tenant/from-to/reason，审计表随审计模块落地后补
 * （architecture.md §7 高风险操作要求）。
 *
 * 成员关系/RBAC 落地后：停用租户同时须失效其成员关系与用户面板入口
 * （由 ResolveTenantContext 的 isEnabled() 检查承担，见 0007 迁移注释）。
 */
final class UpdateTenantStatus
{
    public function __invoke(int $tenantId, TenantStatus $newStatus, string $reason, int $operatorId): Tenant
    {
        /** @var Tenant $tenant */
        $tenant = Tenant::query()->findOrFail($tenantId);

        $from = $tenant->status;

        if ($from === $newStatus) {
            return $tenant;
        }

        $tenant->status = $newStatus;
        $tenant->save();

        Log::info('tenant.status.changed', [
            'tenant_id' => $tenant->id,
            'from' => $from->value,
            'to' => $newStatus->value,
            'reason' => $reason,
            'operator_id' => $operatorId,
            'operator_type' => 'platform_admin',
        ]);

        return $tenant;
    }
}
