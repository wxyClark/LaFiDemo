<?php

namespace App\Modules\Inventory\Application;

use App\Models\User;
use App\Modules\Inventory\Infrastructure\Stock;
use App\Modules\Tenancy\Application\CurrentTenant;
use App\Modules\Tenancy\Domain\UserRole;

/**
 * 库存余额资源授权（Inventory 模块；ADR-0001 c 条）。
 *
 * 租户面板场景：租户用户可读本租户库存；库存调整（adjust）经 AdjustStock
 * 用例（用例内含跨租户写拒绝 + 幂等 + 审计）。库存为只读投影，
 * 无 create/update/delete 直接写入口（禁止绕过用例改表）。
 */
class StockPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Tenant;
    }

    public function view(User $user, Stock $stock): bool
    {
        return $user->role === UserRole::Tenant
            && $stock->tenant_id === app(CurrentTenant::class)->id();
    }

    /**
     * 库存调整动作授权：租户用户可调整本租户库存（用例内部再做
     * 变体归属校验 + 条件更新，AGENTS.md §4）。
     */
    public function adjust(User $user, Stock $stock): bool
    {
        return $user->role === UserRole::Tenant
            && $stock->tenant_id === app(CurrentTenant::class)->id();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Stock $stock): bool
    {
        return false;
    }

    public function delete(User $user, Stock $stock): bool
    {
        return false;
    }
}
