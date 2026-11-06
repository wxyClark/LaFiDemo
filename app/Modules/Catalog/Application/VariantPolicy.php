<?php

namespace App\Modules\Catalog\Application;

use App\Models\User;
use App\Modules\Catalog\Infrastructure\ProductVariant;
use App\Modules\Tenancy\Application\CurrentTenant;
use App\Modules\Tenancy\Domain\UserRole;

/**
 * 变体资源授权（Catalog 模块；ADR-0001 c 条）。
 *
 * 租户面板场景：租户用户可读/改本租户变体；创建/删除基线禁用
 * （写操作经 Catalog Application 用例）。记录级校验租户归属。
 */
class VariantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Tenant;
    }

    public function view(User $user, ProductVariant $variant): bool
    {
        return $user->role === UserRole::Tenant
            && $variant->tenant_id === app(CurrentTenant::class)->id();
    }

    public function update(User $user, ProductVariant $variant): bool
    {
        return $user->role === UserRole::Tenant
            && $variant->tenant_id === app(CurrentTenant::class)->id();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function delete(User $user, ProductVariant $variant): bool
    {
        return false;
    }
}
