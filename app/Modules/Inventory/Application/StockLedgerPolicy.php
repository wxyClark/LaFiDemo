<?php

namespace App\Modules\Inventory\Application;

use App\Models\User;
use App\Modules\Inventory\Infrastructure\StockLedger;
use App\Modules\Tenancy\Application\CurrentTenant;
use App\Modules\Tenancy\Domain\UserRole;

/**
 * 库存流水资源授权（Inventory 模块；ADR-0001 c 条）。
 *
 * 只追加审计事实表：只读（无 create/update/delete）。租户用户可读
 * 本租户流水（记录级校验租户归属，与 getEloquentQuery 过滤一致）。
 */
class StockLedgerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Tenant;
    }

    public function view(User $user, StockLedger $ledger): bool
    {
        return $user->role === UserRole::Tenant
            && $ledger->tenant_id === app(CurrentTenant::class)->id();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, StockLedger $ledger): bool
    {
        return false;
    }

    public function delete(User $user, StockLedger $ledger): bool
    {
        return false;
    }
}
