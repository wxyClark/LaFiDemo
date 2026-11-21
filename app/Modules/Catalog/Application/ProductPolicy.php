<?php

namespace App\Modules\Catalog\Application;

use App\Models\User;
use App\Modules\Domains\Catalog\Models\Product;
use App\Modules\Tenancy\Application\CurrentTenant;
use App\Modules\Tenancy\Domain\UserRole;

/**
 * 商品资源授权（Catalog 模块；ADR-0001 c 条：模块自有资源 Policy 放模块内）。
 *
 * products 表为多面板共享（Admin / Tenant 都挂本 Policy），故方法签名不强制
 * 认证主体类型；仅租户用户（App\Models\User）可读/改本租户商品，其余主体一律拒绝。
 * 租户面板场景：租户用户可读/改本租户商品；创建/删除基线禁用
 * （写操作经 Catalog Application 用例）。记录级校验租户归属（防御纵深，
 * 与资源 getEloquentQuery 过滤和用例 CurrentTenant 限定一致）。
 */
class ProductPolicy
{
    /**
     * 仅租户角色可进入读/改路径（平台/管理员主体走各自面板授权）。
     */
    private function isTenantActor(object $user): bool
    {
        return $user instanceof User && $user->role === UserRole::Tenant;
    }

    public function viewAny(object $user): bool
    {
        return $this->isTenantActor($user);
    }

    public function view(object $user, Product $product): bool
    {
        return $this->isTenantActor($user)
            && $product->tenant_id === app(CurrentTenant::class)->id();
    }

    public function update(object $user, Product $product): bool
    {
        return $this->isTenantActor($user)
            && $product->tenant_id === app(CurrentTenant::class)->id();
    }

    public function create(object $user): bool
    {
        return false;
    }

    public function delete(object $user, Product $product): bool
    {
        return false;
    }
}
