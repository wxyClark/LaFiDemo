<?php

namespace App\Modules\Catalog\Application;

use App\Models\User;
use App\Modules\Catalog\Infrastructure\Product;
use App\Modules\Tenancy\Application\CurrentTenant;
use App\Modules\Tenancy\Domain\UserRole;

/**
 * 商品资源授权（Catalog 模块；ADR-0001 c 条：模块自有资源 Policy 放模块内）。
 *
 * 租户面板场景：租户用户可读/改本租户商品；创建/删除基线禁用
 * （写操作经 Catalog Application 用例）。记录级校验租户归属（防御纵深，
 * 与资源 getEloquentQuery 过滤和用例 CurrentTenant 限定一致）。
 */
class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Tenant;
    }

    public function view(User $user, Product $product): bool
    {
        return $user->role === UserRole::Tenant
            && $product->tenant_id === app(CurrentTenant::class)->id();
    }

    public function update(User $user, Product $product): bool
    {
        return $user->role === UserRole::Tenant
            && $product->tenant_id === app(CurrentTenant::class)->id();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function delete(User $user, Product $product): bool
    {
        return false;
    }
}
