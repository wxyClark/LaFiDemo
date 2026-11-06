<?php

namespace App\Modules\Tenancy\Http\Middleware;

use App\Models\User;
use App\Modules\Tenancy\Application\CurrentTenant;
use App\Modules\Tenancy\Application\TenantContext;
use App\Modules\Tenancy\Domain\Tenant;
use App\Modules\Tenancy\Domain\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 租户面板租户上下文中间件（Tenant Panel 认证后执行）。
 *
 * 可信解析链路（AGENTS.md §1）：
 *   认证用户（web guard）→ role 必须为 tenant 且有 tenant_id
 *   → 加载租户并校验存在 + Active → CurrentTenant::set()
 * 任何一步不满足即 403 拒绝（隐藏入口不构成授权，AGENTS.md §1）。
 *
 * 成员关系落地后：改从 tenant_memberships 有效成员关系解析
 * （TenantContextResolver 注释约定），此中间件保留为面板入口守卫。
 */
class ResolveTenantContext
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        if ($user->role !== UserRole::Tenant || $user->tenant_id === null) {
            abort(403);
        }

        /** @var Tenant|null $tenant */
        $tenant = Tenant::query()->find($user->tenant_id);

        if ($tenant === null || ! $tenant->isEnabled()) {
            abort(403);
        }

        $currentTenant = app(CurrentTenant::class);
        $currentTenant->set(TenantContext::forTenant((int) $user->tenant_id));

        $response = $next($request);

        $currentTenant->clear();

        return $response;
    }
}
